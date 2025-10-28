<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
require __DIR__ . '/auth.php';

// session check
analytics_require_auth();

// Filter parameters
$days = max(1, min(90, (int)($_GET['days'] ?? 30)));
$path = trim($_GET['path'] ?? '');
$country = trim($_GET['country'] ?? '');

$where = ["ts >= :since"];
$params = [':since' => (int)( (microtime(true)*1000) - $days*86400*1000 )];

if ($path !== '') { $where[] = "path = :path"; $params[':path'] = $path; }
if ($country !== '') { $where[] = "country = :country"; $params[':country'] = strtoupper($country); }
$sqlWhere = implode(' AND ', $where);

// aggregates by day
$byDay = db()->prepare("
  SELECT date(ts/1000,'unixepoch') as d, COUNT(*) as c
  FROM visits WHERE $sqlWhere GROUP BY d ORDER BY d ASC
");
$byDay->execute($params);
$rowsDay = $byDay->fetchAll();

// top sources
$topRef = db()->prepare("
  SELECT COALESCE(NULLIF(ref,''),'(direct)') as r, COUNT(*) as c
  FROM visits WHERE $sqlWhere GROUP BY r ORDER BY c DESC LIMIT 12
");
$topRef->execute($params);
$rowsRef = $topRef->fetchAll();

// top countries (if CF)
$topCountry = db()->prepare("
  SELECT COALESCE(NULLIF(country,''),'?') as cc, COUNT(*) as c
  FROM visits WHERE $sqlWhere GROUP BY cc ORDER BY c DESC LIMIT 12
");
$topCountry->execute($params);
$rowsCountry = $topCountry->fetchAll();

// last 50
$last = db()->prepare("SELECT ts,url,ref,ip,country,lang,dpr,vp_w,vp_h,theme,type FROM visits WHERE $sqlWhere ORDER BY ts DESC LIMIT 50");
$last->execute($params);
$rowsLast = $last->fetchAll();

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }

?><!doctype html>
<html>
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Analytics</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/modern-normalize/2.0.0/modern-normalize.min.css"/>
  <style>
    body{font:14px/1.45 system-ui,Segoe UI,Roboto,Arial;padding:18px;background:#0b0f14;color:#e5e7eb}
    .wrap{max-width:1100px;margin:0 auto}
    h1{margin:8px 0 16px;font-size:20px}
    .grid{display:grid;grid-template-columns:1fr 1fr;gap:18px}
    .card{background:#0f1520;border:1px solid rgba(255,255,255,.08);border-radius:12px;padding:14px}
    canvas{width:100%;height:280px}
    table{width:100%;border-collapse:collapse}
    th,td{padding:8px 10px;border-bottom:1px solid rgba(255,255,255,.08);font-size:13px}
    th{color:#cfe3ff;text-align:left}
    .muted{color:#9aa4b2}
    .filters{display:flex;gap:10px;margin:10px 0 16px}
    input,select{background:#0c1118;border:1px solid rgba(255,255,255,.15);color:#e5e7eb;border-radius:8px;padding:8px 10px}
    button{background:#7cc0ff;color:#0a0e14;border:0;border-radius:8px;padding:8px 12px;cursor:pointer}
  </style>
</head>
<body>
<div class="wrap">
  <h1>Analytics (last <?= (int)$days; ?> days)</h1>
  
  <div style="margin:6px 0 12px;">
    <a href="/analytics/logout.php" style="color:#cfe3ff;">Logout</a>
  </div>
  
  <form class="filters" method="get">
    <label>Days <input type="number" name="days" value="<?= (int)$days; ?>" min="1" max="90"></label>
    <label>Path <input type="text" name="path" value="<?= h($path); ?>" placeholder="/developer"></label>
    <label>Country <input type="text" name="country" value="<?= h($country); ?>" placeholder="US"></label>
    <button type="submit">Apply</button>
  </form>

  <div class="grid">
    <div class="card"><canvas id="visitsChart"></canvas></div>
    <div class="card"><canvas id="refsChart"></canvas></div>
  </div>

  <div class="grid" style="margin-top:18px">
    <div class="card"><canvas id="countryChart"></canvas></div>
    <div class="card">
      <div class="muted">Top paths (<?= (int)$days; ?> days)</div>
      <table>
        <thead><tr><th>Path</th><th>Visits</th></tr></thead>
        <tbody>
          <?php
          $topPath = db()->prepare("SELECT path, COUNT(*) c FROM visits WHERE $sqlWhere GROUP BY path ORDER BY c DESC LIMIT 10");
          $topPath->execute($params);
          foreach ($topPath as $r) {
            echo '<tr><td>'.h($r['path']).'</td><td>'.(int)$r['c'].'</td></tr>';
          }
          ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card" style="margin-top:18px">
    <div class="muted">Last 50 hits</div>
    <table>
      <thead><tr>
        <th>Time</th><th>Type</th><th>URL</th><th>Ref</th><th>IP</th><th>CC</th><th>Lang</th><th>DPR</th><th>VP</th><th>Theme</th>
      </tr></thead>
      <tbody>
        <?php foreach ($rowsLast as $r): ?>
          <tr>
            <td><?= h(gmdate('Y-m-d H:i:s', (int)($r['ts']/1000))) ?></td>
            <td><?= h($r['type']) ?></td>
            <td class="muted"><?= h($r['url']) ?></td>
            <td class="muted"><?= h($r['ref'] ?: '(direct)') ?></td>
            <td><?= h($r['ip']) ?></td>
            <td><?= h($r['country'] ?: '-') ?></td>
            <td><?= h($r['lang'] ?: '-') ?></td>
            <td><?= h($r['dpr'] ?: '1') ?></td>
            <td><?= (int)$r['vp_w'] ?>×<?= (int)$r['vp_h'] ?></td>
            <td><?= h($r['theme']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
const byDay = <?= json_encode($rowsDay, JSON_UNESCAPED_UNICODE) ?>;
const refs  = <?= json_encode($rowsRef, JSON_UNESCAPED_UNICODE) ?>;
const byCC  = <?= json_encode($rowsCountry, JSON_UNESCAPED_UNICODE) ?>;

const d1 = { labels: byDay.map(x=>x.d), datasets: [{ label:'Visits', data: byDay.map(x=>+x.c) }] };
new Chart(document.getElementById('visitsChart'), { type:'line', data:d1, options:{maintainAspectRatio:false} });

const d2 = { labels: refs.map(x=>x.r), datasets: [{ label:'Referrers', data: refs.map(x=>+x.c) }] };
new Chart(document.getElementById('refsChart'), { type:'bar', data:d2, options:{maintainAspectRatio:false} });

const d3 = { labels: byCC.map(x=>x.cc), datasets: [{ label:'Countries', data: byCC.map(x=>+x.c) }] };
new Chart(document.getElementById('countryChart'), { type:'doughnut', data:d3, options:{maintainAspectRatio:false} });
</script>
</body>
</html>
