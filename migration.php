<?php
include_once __DIR__.'/model/TaskPull.php';

use BDProvider\TaskPull;

$type = $argv[1];
$model = new TaskPull();

if( $type == '--migrateUp' )
{
    $model->migrateUp();
}
elseif($type == '--migrateDown')
{
    $model->migrateDown();
}