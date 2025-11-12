<?php

// Определяем, куда пришёл запрос, например /api/get_users
$uriPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Базовый путь до index.php (обычно "/api" или "/api/")
$apiBase = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

// Получаем «хвост» после /api, например "get_users"
$route = substr($uriPath, strlen($apiBase));
$route = trim($route, '/'); // "get_users"

// Можно ещё учесть HTTP-метод, если хочешь
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$isPartial = strcasecmp($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '', 'fetch-partial') === 0;

// Роутер
switch ($route) {
	case 'analytics':
		require ROOT_DIR . '/analytics/index.php';
		exit;
		break;
		
	case 'studio':
		require ROOT_DIR . '/studio/index.php';
		exit;
		break;
	
	case 'studio/api':
		require ROOT_DIR . '/studio/api.php';
		exit;
		break;
	
	default:
		list($currentUser, $trackSlug) = resolve_route();
		$TRACKS        = load_user_tracks($currentUser);
		$trackCount    = count($TRACKS);
		
		if ($trackCount === 0 && $currentUser === DEFAULT_USER){
			header('Location: /'.DEMO_USER.'/'.$trackSlug);
			exit();
		}
		//continue with /index.php
}

// ****************************************************************
// * Route analysis:
// *
// *  /                    -> [default, '']
// *  /resume_slug         -> [default, resume_slug]
// *  /user_name           -> [user_name, '']  (if exists /data/user_name)
// *                          else [default, user_name]  (as a default user track)
// *  /user_name/resume    -> [user_name, resume]
// ****************************************************************
function resolve_route(): array {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $path = rtrim($path, '/');

    // root
    if ($path === '' || $path === '/') {
        return [DEFAULT_USER, ''];
    }

    $trimmed = ltrim($path, '/');
    $parts   = explode('/', $trimmed, 3);
    $first   = strtolower($parts[0] ?? '');
    $second  = strtolower($parts[1] ?? '');

    // one segment: either /user_name or /resume_slug for default
    if ($second === '' || $second === null) {
        if ($first !== '' && is_dir(DATA_DIR . '/' . $first)) {
            // /user_name -> select this user's track or the only one
            return [$first, ''];
        }

        // /resume_slug -> default user track
        return [DEFAULT_USER, $first];
    }

    // /user_name/resume_slug
    return [$first, $second];
}