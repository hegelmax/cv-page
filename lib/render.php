<?php
function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }

function tpl(string $name): string {
  static $memo = [];
  if (isset($memo[$name])) return $memo[$name];
  $path = __DIR__ . '/../templates/' . $name;
  return $memo[$name] = (file_exists($path) ? file_get_contents($path) : '');
}

function render_layout_page(string $inner): string {
  $tpl = tpl('layout.html');

  // Собираем абсолютный canonical
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
  $uri    = $_SERVER['REQUEST_URI'] ?? '/';
  $canonical = $scheme.'://'.$host.$uri;

  $tpl = str_replace('##CANONICAL##', htmlspecialchars($canonical, ENT_QUOTES), $tpl);
  return str_replace('##CONTENT##', $inner, $tpl);
}


function render_chooser_inner(array $TRACKS): string {
  $tpl = tpl('chooser.html');
  $cards = '';
  foreach ($TRACKS as $key=>$t) {
    $desc = ($key==='developer')
      ? 'Hands-on engineering: coding, architecture, systems, automation.'
      : 'Product/people leadership, analytics, delivery, stakeholder management.';
    $cards .= "<a class='card' href='/" . h($key) . "'>"
            . "<i class='fa-solid " . h($t['icon']) . "'></i>"
            . "<div><h3>".h($t['label'])."</h3><p>{$desc}</p></div>"
            . "</a>";
  }
  return str_replace('##CARDS##', $cards, $tpl);
}

function render_resume_inner(string $track, array $meta, array $json, string $templatePath, array $TRACKS): string {
  $mapping = build_mapping($json);
  $bodyTpl = file_get_contents($templatePath) ?: '';
  $body = preg_replace_callback('/##([A-Z0-9_]+)##/', fn($m) => $mapping[$m[1]] ?? '', $bodyTpl);

  $topbar = tpl('topbar.html');
  $btn = fn(string $key, array $t) =>
    "<a class='btn ".($key===$track?'active':'')."' href='/" . h($key) . "'>"
    . "<i class='fa-solid " . h($t['icon']) . "'></i> " . h(explode(' ', $t['label'])[0]) . "</a>";
  foreach ($TRACKS as $k=>$t) $topbar = str_replace("##BTN_{$k}##", $btn($k, $t), $topbar);

  $tpl = tpl('resume_inner.html');
  return str_replace(['##TOPBAR##','##BODY##'], [$topbar, $body], $tpl);
}

function build_mapping(array $d): array {
  return [
    'PAGE_HEADER'         => strtoupper($d['name'] ?? ''),
    'PAGE_TITLE'          => $d['title'] ?? '',
    'CONTACT_EMAIL'       => $d['contact']['email'] ?? '',
    'CONTACT_WEBSITE_URL' => $d['contact']['website'] ?? '#',
    'CONTACT_WEBSITE_TEXT'=> $d['contact']['website'] ?? '',
    'CONTACT_PHONE'       => $d['contact']['phone'] ?? '',
    'CONTACT_LOCATION'    => $d['contact']['location'] ?? '',
    'SUMMARY'             => $d['summary'] ?? '',
    'EXPERIENCE_BLOCK'    => blocks_experience($d['experience'] ?? []),
    'EDUCATION_BLOCK'     => blocks_education($d['education'] ?? []),
    'ACHIEVEMENTS_BLOCK'  => blocks_generic($d['achievements'] ?? [], fn($a)=>'<div class="achiv">⭐ '.h($a['name']??'').'</div><div class="achiv-desc">'.($a['desc']??'').'</div>'),
    'SKILLS_BLOCK'        => blocks_badges($d['skills']['list'] ?? []),
    'LANGUAGES_BLOCK'     => blocks_languages($d['languages'] ?? []),
    'PUBLICATIONS_BLOCK'  => blocks_generic($d['publications'] ?? [], fn($a)=>'<div class="publ"><i class="fa-solid fa-book"></i> '.h($a['what']??'').'</div><div class="publ-desc">('.h($a['where']??'').')</div>'),
    'AWARDS_BLOCK'        => blocks_generic($d['awards'] ?? [], fn($a)=>'<div class="award">'.h($a['what']??'').'</div><div class="award-desc">('.h($a['where']??'').', '.h($a['when']??'').')</div>'),
    'CERTS_BLOCK'         => blocks_generic($d['certification'] ?? [], fn($c)=>'<div class="cert-title"><i class="fa-solid fa-certificate"></i> '.h($c['degree']??'').'</div><div class="cert-desc">'.h($c['institution']??'').'</div>'),
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
    $out.='</ul><div class="project-title"><i class="fa-solid fa-layer-group"></i> Projects:</div>';
    foreach(($job['projects']??[]) as $p){
      $out.='<div class="project"><div class="project-name"><span class="details"><i class="fa-solid fa-circle-check"></i></span> '.h($p['name']??'').'</div><div class="project-desc">'.($p['description']??'').'</div>';
      foreach(($p['technologies']??[]) as $s){ $out.='<span class="badge">'.h($s).'</span>'; }
      $out.='</div>';
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
