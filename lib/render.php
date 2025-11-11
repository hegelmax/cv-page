<?php
function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }

function tpl(string $name): string {
  static $memo = [];
  if (isset($memo[$name])) return $memo[$name];
  $path = __DIR__ . '/../templates/' . $name;
  return $memo[$name] = (file_exists($path) ? file_get_contents($path) : '');
}

function render_layout_page(string $inner, string $title): string {
  $tpl = tpl('layout.html');

  // Collecting absolute canonical
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
  $uri    = $_SERVER['REQUEST_URI'] ?? '/';
  $canonical = $scheme.'://'.$host.$uri;

  $tpl = str_replace('##CANONICAL##', htmlspecialchars($canonical, ENT_QUOTES), $tpl);
  $tpl = str_replace('##TITLE##', $title, $tpl);
  return str_replace('##CONTENT##', $inner, $tpl);
}

function build_image(?string $src): string {
  $src = trim((string)$src);
  if ($src === '') {
    return '';
  }
  return '<div class="image"><img src="'.h($src).'"></div>';
}


function render_chooser_inner(array $TRACKS, string $user = DEFAULT_USER): string {
  $tpl   = tpl('chooser.html');
  $cards = '';

  $isDefault = ($user === DEFAULT_USER);

  foreach ($TRACKS as $key => $t) {
    $slug  = (string)$key;
    $label = $t['label'] ?? ucfirst($slug);
    $icon  = $t['icon']  ?? 'fa-file-lines';
    $desc  = trim((string)($t['description'] ?? ''));

    // /slug for the default user and /user/slug for others
    $href = $isDefault
      ? '/' . h($slug)
      : '/' . h($user) . '/' . h($slug);

    $cards .= "<a class='card' href=\"{$href}\">"
            . "<i class='fa-solid " . h($icon) . "'></i>"
            . "<div><h3>" . h($label) . "</h3>";
    $cards .= ($desc !== '' ? "<p>" . h($desc) . "</p>" : '');
    $cards .= "</div></a>";
  }

  return str_replace('##CARDS##', $cards, $tpl);
}


function render_resume_inner(string $user, string $track, array $meta, array $json, string $templatePath, array $TRACKS): string {
  $mapping   = build_mapping($json);
  $bodyTpl   = file_get_contents($templatePath) ?: '';
  $body      = preg_replace_callback('/##([A-Z0-9_]+)##/', fn($m) => $mapping[$m[1]] ?? '', $bodyTpl);

  // 2) Topbar + track selection segment
  $topbarTpl  = tpl('topbar.html');
  $trackCount = count($TRACKS);

  // Add a class modifier to the topbar so that CSS understands "little" vs. "lot"
  $modifier = '';
  if ($trackCount > 3) {
    $modifier = ' topbar--many';
  } elseif ($trackCount > 1) {
    $modifier = ' topbar--few';
  }

  if ($modifier !== '') {
    $topbarTpl = str_replace('class="topbar"', 'class="topbar'.$modifier.'"', $topbarTpl);
  }

  $segmentHtml = '';

  $isDefault  = ($user === DEFAULT_USER);
  $keyPrefix  = $isDefault ? '' : ($user . '/');   // key for AJAX (without leading /)

  if ($trackCount > 1) {
    // 2.1. Buttons
    $buttonsHtml = '';
    foreach ($TRACKS as $key => $t) {
      $slug  = (string)$key;
      $label = $t['label'] ?? ucfirst($slug);
      $short = $t['short'] ?? (explode(' ', trim($label))[0] ?: $label);
      $icon  = $t['icon']  ?? 'fa-file-lines';

      // /slug или /user/slug
      $href = $isDefault
        ? '/' . h($slug)
        : '/' . h($user) . '/' . h($slug);

      $buttonsHtml .=
        "<a class='btn ".($slug === $track ? "active" : "")."' href=\"{$href}\">"
        . "<i class='fa-solid " . h($icon) . "'></i>"
        . "<span class='short'>" . h($short) . "</span>"
        . "<span class='full'>"  . h($label) . "</span>"
        . "</a>";
    }

    // 2.2. Select
    $selectHtml = "<select id=\"track-select\" class=\"selector\">";
    foreach ($TRACKS as $key => $t) {
      $slug   = (string)$key;
      $label  = $t['label'] ?? ucfirst($slug);
      $selectedAttr = ($slug === $track ? " selected" : "");

      // key for JS: "developer" or "user/developer"
      $keyValue = $keyPrefix . $slug;

      $selectHtml .= "<option value=\"" . h($keyValue) . "\"{$selectedAttr}>"
                   . h($label)
                   . "</option>";
    }
    $selectHtml .= "</select>";

    $segmentHtml =
      "<div class=\"seg seg--buttons\" data-seg=\"buttons\">{$buttonsHtml}</div>" .
      "<div class=\"seg seg--select\" data-seg=\"select\">{$selectHtml}</div>";
  }

  // 3) Debug / demo badge if using fallback JSON
  $demoBadge = '';
  if (!empty($meta['usingDemo'])) {
    $demoBadge = '<button type="button" class="ghost" title="This view uses demo JSON fallback.">'
               . '<i class="fa-solid fa-triangle-exclamation"></i> Demo data</button>';
  }

  $segmentHtml .= $demoBadge;

  $topbar   = str_replace('##SEGMENT##', $segmentHtml, $topbarTpl);
  $tpl      = tpl('resume_inner.html');
  return str_replace( ['##TOPBAR##', '##BODY##'], [$topbar, $body], $tpl );
}


function section_if_not_empty(string $title, string $html): string {
  if (trim($html) === '') {
    return '';
  }
  return '<div class="section-title">'.h($title).'</div>' . $html;
}

function build_mapping(array $d): array {
  $exp      = blocks_experience($d['experience'] ?? []);
  $edu      = blocks_education($d['education'] ?? []);
  $ach      = blocks_generic($d['achievements'] ?? [], fn($a)=>'<div class="achiv">⭐ '.h($a['name']??'').'</div><div class="achiv-desc">'.($a['desc']??'').'</div>');
  $skills   = blocks_badges($d['skills']['list'] ?? []);
  $langs    = blocks_languages($d['languages'] ?? []);
  $publ     = blocks_generic($d['publications'] ?? [], fn($a)=>'<div class="publ"><i class="fa-solid fa-book"></i> '.h($a['what']??'').'</div><div class="publ-desc">('.h($a['where']??'').')</div>');
  $awards   = blocks_generic($d['awards'] ?? [], fn($a)=>'<div class="award">'.h($a['what']??'').'</div><div class="award-desc">('.h($a['where']??'').', '.h($a['when']??'').')</div>');
  $certs    = blocks_generic($d['certification'] ?? [], fn($c)=>'<div class="cert-title"><i class="fa-solid fa-certificate"></i> '.h($c['degree']??'').'</div><div class="cert-desc">'.h($c['institution']??'').'</div>');
  
  return [
    'PAGE_HEADER'         => strtoupper($d['name'] ?? ''),
    'PAGE_TITLE'          => $d['title'] ?? '',
    'CONTACT_EMAIL'       => $d['contact']['email'] ?? '',
    'CONTACT_WEBSITE_URL' => $d['contact']['website'] ?? '#',
    'CONTACT_WEBSITE_TEXT'=> $d['contact']['website'] ?? '',
    'CONTACT_PHONE'       => $d['contact']['phone'] ?? '',
    'CONTACT_LOCATION'    => $d['contact']['location'] ?? '',
    'SUMMARY'             => $d['summary'] ?? '',
    'IMAGE_BLOCK'         => build_image($d['image'] ?? ''),
    
    'EXPERIENCE_SECTION'   => section_if_not_empty('PROFESSIONAL EXPERIENCE', $exp),
    'EDUCATION_SECTION'    => section_if_not_empty('EDUCATION',               $edu),
    'ACHIEVEMENTS_SECTION' => section_if_not_empty('KEY ACHIEVEMENTS',        $ach),
    'SKILLS_SECTION'       => section_if_not_empty('SKILLS',                  $skills),
    'LANGUAGES_SECTION'    => section_if_not_empty('LANGUAGES',               $langs),
    'PUBLICATIONS_SECTION' => section_if_not_empty('SPEAKING / PUBLICATIONS', $publ),
    'AWARDS_SECTION'       => section_if_not_empty('AWARDS & RECOGNITIONS',   $awards),
    'CERTS_SECTION'        => section_if_not_empty('CERTIFICATION',           $certs)
  ];
}

function blocks_generic(array $items, callable $map): string { return implode('', array_map($map, $items)); }

function blocks_experience(array $exps): string {
  $out=''; foreach ($exps as $job){
    $out.='<div class="job"><h3><i class="fa-solid fa-briefcase"></i> '.h($job['title']??'').'</h3>';
    foreach(($job['companies']??[]) as $c){
      $out.='<div class="job_info"><div class="company"><span class="company_name">'.h($c['company']??'').'</span><span class="details"> <i class="fa-solid fa-location-dot"></i> '.h($c['location']??'').'</span></div><div class="details"><i class="fa-solid fa-calendar-days"></i> '.h($c['period']??'').'</div></div>';
    }
    $out.='<ul>';
    foreach(($job['highlights']??[]) as $hgl){ $out.='<li>'.h($hgl).'</li>'; }
    $out.='</ul>';
    if (!empty($job['projects']??[])) {
      $out.='<div class="project-title"><i class="fa-solid fa-layer-group"></i> Projects:</div>';
      foreach(($job['projects']??[]) as $p){
        $out.='<div class="project"><div class="project-name"><span class="details"><i class="fa-solid fa-circle-check"></i></span> '.h($p['name']??'').'</div><div class="project-desc">'.($p['description']??'').'</div>';
        foreach(($p['technologies']??[]) as $s){ $out.='<span class="badge">'.h($s).'</span>'; }
        $out.='</div>';
      }
    }
    $out.='</div>';
  } return $out;
}

function blocks_education(array $eds): string {
  $out=''; foreach ($eds as $ed){
    $out.='<div class="job education"><h3><i class="fa-solid fa-graduation-cap"></i> '.h($ed['degree']??'').'</h3><p class="company university">'.h($ed['institution']??'').'</p></div>';
  } return $out;
}

function blocks_badges(array $list): string { return implode('', array_map(fn($s)=>'<span class="badge">'.h($s).'</span>', $list)); }

function blocks_languages(array $langs): string {
  $out=''; foreach($langs as $lang){
    $dots = str_repeat('<i class="fa-solid fa-circle level"></i>', (int)($lang['level_num']??0))
          . str_repeat('<i class="fa-regular fa-circle level"></i>', max(0,5-(int)($lang['level_num']??0)));
    $out.='<div class="lang-badge">'.h($lang['name']??'').' <span>'.h($lang['level']??'').' '.$dots.'</span></div>';
  } return $out;
}
