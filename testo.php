<?php
include_once 'bundles/Config.php';
include_once __DIR__.'/config/Dev.php';

use \Configs\Dev;
use \Bundles\Config;

Config::setEvironment(new Dev());
$get = Config::get('testStorage');

$pipes = array();

$descr = [
    0 => array("pipe", "r"),// stdin это канал, из которого потомок будет читать
    1 => array("pipe", "w"),// stdout это канал, в который потомок будет записывать
    2 => array("pipe", "w"), // stderr это файл для записи
];

$process = proc_open("php worker.php --deleteFile '/var/www/html/test/test/2.txt' --taskID 8 ",
    $descr, $pipes);

$error = stream_get_contents($pipes[2]);

echo"<pre>";var_dump($error);exit();

foreach ($pipes as $pipe) {
    if (is_resource($pipe)) {
        fclose($pipe);
    }
}

proc_close($process);


/*$descr =  [
    0 => array("pipe", "r"),// stdin это канал, из которого потомок будет читать
    1 => array("pipe", "w"),// stdout это канал, в который потомок будет записывать
    2 => array("pipe", "w"), // stderr это файл для записи
];

$pipes = array();
$process = proc_open("php worker.php --deleteFile '/var/www/html/test/test/2.txt' ", $descr, $pipes);

$stdout = stream_get_contents($pipes[1]);
$strerr = stream_get_contents($pipes[2]);*/
