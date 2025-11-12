<?php
// ****************************************************************
// * Dynamically collect tracks from /data/{user}/*.json.
// * Optionally, specify a "track" block in the root JSON:
// *  {
// *    "track": {
// *      "slug": "developer",
// *      "label": "Programming / Engineering",
// *      "icon": "fa-code",
// *      "description": "Hands-on engineering ...",
// *      "fallback": "demo/john_doe_prog.json"   // optional, relative to /data
// *    }
// *  }
// ****************************************************************
function load_user_tracks(string $user): array {
    $user     = strtolower(preg_replace('~[^a-z0-9_-]+~i', '', $user));
    if ($user === '') {
        $user = DEFAULT_USER;
    }
	
    $baseDir  = DATA_DIR . '/' . $user;

    if (!is_dir($baseDir)) {
        return [];
    }

    $tracks = [];

    foreach (glob($baseDir . '/*.json') as $path) {
        $json = json_decode(@file_get_contents($path), true);
        if (!is_array($json)) {
            continue;
        }

        $meta = is_array($json['track'] ?? null) ? $json['track'] : [];

        // slug
        $slug = $meta['slug'] ?? null;
        if (!$slug) {
            $fn   = basename($path);
            $slug = pathinfo($fn, PATHINFO_FILENAME);
        }
        $slug = strtolower(preg_replace('~[^a-z0-9_-]+~i', '', $slug));
        if ($slug === '') {
            continue;
        }

        // label / icon / description
        $label       = $meta['label'] ?? ($json['title'] ?? ucfirst($slug));
        $icon        = $meta['icon']  ?? 'fa-file-lines';
        $description = $meta['description'] ?? '';

        // fallback: только из JSON, относительный к /data
        $fallback = null;
        if (!empty($meta['fallback'])) {
            $rel   = ltrim((string)$meta['fallback'], '/');
            $fb    = DATA_DIR . '/' . $rel;
            if (is_file($fb)) {
                $fallback = $fb;
            }
        }

        $tracks[$slug] = [
            'user'        => $user,
            'slug'        => $slug,
            'file'        => $path,
            'fallback'    => $fallback,
            'label'       => $label,
            'icon'        => $icon,
            'description' => $description,
        ];
    }

    // stable order (by label)
    uasort($tracks, fn($a, $b) => strcmp($a['label'], $b['label']));

    return $tracks;
}