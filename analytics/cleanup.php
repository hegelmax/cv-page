<?php
require __DIR__.'/bootstrap.php';
$ttlDays = 180;
$since = (int)( (microtime(true)*1000) - $ttlDays*86400*1000 );
db()->prepare("DELETE FROM visits WHERE ts < ?")->execute([$since]);
db()->exec("VACUUM");
echo "OK\n";
