<?php
namespace Configs;
use Interfaces\ConfigInterface;

include_once __DIR__.'/../interfaces/ConfigInterface.php';

Class Dev implements ConfigInterface
{
    public static function getConfigList()
    {
        return [
            'db' => [
                'dbtype' => 'postgres',
                'host' => 'localhost',
                'dbname' => 'postgres',
                'user' => 'root',
                'pass' => 'admin'
            ],
            'task' => [
                'total_proc' => 6
            ],
            'testStorage' => 'test',
            'log' => [
                'taskSuccessCommonLog' => __DIR__.'/../logs/taskSuccessCommonLog',
                'taskErrorCommonLog' => __DIR__.'/../logs/taskErrorCommonLog',
                'taskSuccess' => __DIR__.'/../logs/taskSuccess',
                'taskError' => __DIR__.'/../logs/taskError',
            ]
        ];
    }
}