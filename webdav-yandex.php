<?
$WebDAV = [
  'login'=>'', //Login
  'password'=>'', //Pass
  'url'=>'https://webdav.yandex.ru/backups/', //DIR for yandex
];
$backupPath = '/home/bacups'; //local dir server
/*
function clear() {
if (file_exists($backupPath'/'))
foreach (glob($backupPath'/*') as $file)
unlink($file);}
clear(); */
$databases = [
  ['login' => '', 'password' => '', 'dbname' => '', 'host' => ''],
  ['login' => '', 'password' => '', 'dbname' => '', 'host' => ''],
];
$sites = [
  ['name' => 'site.com', 'path' => '/home/site.com/', 'exclude' => ['path' => '/home/site.com/temp']],
  ['name' => 'site.com', 'path' => '/home/site.com/', 'exclude' => ['path' => '/home/site.com/temp']],
];
/////////////////////////////////////////////////////////////////////////////////////////
//Если не прописать в дату часы и минуты, то имена файлов будут совпадать, и файл на Яндекс.Диске будет перезаписан.
//Соотв. можно делать бэкап каждый час, при этом файлы не будут излишне плодиться.
//На следующий день будет создан новый файл.
$date = date('Y-m-d');
$errors = [];
$success = [];
$files_to_send = [];
foreach ($databases as $db) {
  $filename = "$backupPath/bases/{$db['dbname']}.sql.gz";
  $output = `mysqldump --user={$db['login']}  --host={$db['host']} --password={$db['password']} -A | gzip -f > $filename`;
  if (!file_exists($filename)) {
    $errors[] = 'Dump ' . $db['dbname'] . ' failed: ' . $output;
  } else {
    $success[] = 'DB ' . $db['dbname'] . ' dumped';
    $files_to_send[] = $filename;
  }
}
foreach ($sites as $site) {
  $filename = "$backupPath/bases/{$site['name']}.tar.gz";
  $exclude = '';
  if ($site['exclude']) {
    $exclude = '-x ' . implode('\* -x ', $site['exclude']) . '\*';
  }
//  $cmd = "tar -cvvf \"$filename\"  {$site['path']} $exclude";
  $cmd = "tar -cvvzf $filename {$site['path']}";
  echo $cmd . "<br>\n";
  $output = `$cmd`;
  if (!file_exists($filename)) {
    $errors[] = 'Site backup ' . $site['name'] . ' failed: ' . $output;
  } else {
    $success[] = 'Site ' . $site['name'] . ' saved';
    $files_to_send[] = $filename;
  }
}
foreach ($errors as $e) {
  echo 'Ошибка: ' . $e . "<br>\n";
}
echo "<br>\n";
foreach ($success as $s) {
  echo 'ОК: ' . $s . "<br>\n";
}
echo "<br>\n";
echo "Следующие файлы будут загружены:<br>\n";
foreach ($files_to_send as $f) {
  echo $f . "<br>\n";
}
echo "<br>\n";
if (!empty($files_to_send)) {
  foreach ($files_to_send as $file) {
    echo shell_exec("curl --user {$WebDAV['login']}:{$WebDAV['password']} -T \"$file\" {$WebDAV['url']}") . "<br>\n";//если ругается на сертификат, можно добавить ключ -k
unlink($file);
}
}
?>