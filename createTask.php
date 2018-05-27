<?php
include_once __DIR__.'/bundles/Task.php';
include_once __DIR__.'/model/TaskPull.php';

use Bundles\Task;

$start = microtime(true);

if(array_search('--help', $argv))
{
    Task::printHelp();
    return;
}

$Task = new Task($argv);

if( count($Task->getErrors())==0 )
{
    $DB = new \BDProvider\TaskPull();
    $taskID = $DB->createTask($Task);
    echo "Task ID=$taskID was created\n";
}
else
{
    print_r( $Task->getErrors() );
}

echo 'Время выполнения скрипта: '.round(microtime(true) - $start, 4)." сек.\n";






