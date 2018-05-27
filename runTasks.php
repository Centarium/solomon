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

$taskID = $data['task_id'];
$data = $taskPull->convertStorageData($data);
$task = new TaskProcessing($taskID, $data );

echo "Start task ".$taskID."\n";

//Создаем fork процесса чтобы открепить от консоли
$pid = pcntl_fork();
if($pid !=0)
{
    exit();
}

$task->taskProcessing();








