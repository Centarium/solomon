<?php
include_once __DIR__.'/bundles/TaskProcessing.php';
include_once __DIR__.'/bundles/Config.php';
use \Bundles\TaskProcessing;
use \Bundles\Config;
sleep(Config::get('task:delay'));

$index = array_search(TaskProcessing::DELETE_FILE_COMMAND, $argv);
$taskID_index = array_search(TaskProcessing::TASK_ID_KEY, $argv);

if( false !== $index )
{
    if(isset($argv[$index+1]))
    {
        $file = $argv[$index+1];
        if(false === @unlink( $file ))
        {
            $message = "Can`t delete file $file\n";
            $log_file = TaskProcessing::getTaskErrorLog( $argv[$taskID_index+1] );

            error_log( $message, 3, $log_file );

            throw new Exception($message,
                TaskProcessing::ERROR_PROCESSING
            );
        }
        else
        {
            $message = "[D]{$file}[/D]\n";
            $log_file = TaskProcessing::getTaskSuccessLog( $argv[$taskID_index+1] );
            error_log( $message, 3, $log_file );
        }
    }
}