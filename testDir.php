<?php
include __DIR__.'/bundles/TestDirectory.php';

use Bundles\TestDirectory;

$type = $argv[1];

if( $type == '--testDirs' )
{
    $model = new TestDirectory();
    $model->createDirs( $model->rootPath, 4, 2 );
}