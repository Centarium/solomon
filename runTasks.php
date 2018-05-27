<?php
include_once __DIR__.'/model/TaskPull.php';
include_once __DIR__.'/bundles/Task.php';
include_once __DIR__.'/bundles/TaskProcessing.php';

use BDProvider\TaskPull;
use Bundles\TaskProcessing;

$taskPull = new TaskPull();
$data = $taskPull->getTaskByStatus();

if(!$data)
{
    echo "Task pull with status 'new' is empty \n";
    exit();
}

$data = $taskPull->convertStorageData($data);
$task = new TaskProcessing( $data );

echo "Start task ".$data['task_id'];

//Создаем fork процесса чтобы открепить от консоли
$pid = pcntl_fork();
if($pid ==0)
{
    exit();
}

$task->taskProcessing( $data['task_id'] );








