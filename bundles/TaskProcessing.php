<?php
namespace Bundles;
include_once __DIR__.'/Task.php';
include_once __DIR__.'/Config.php';
include_once __DIR__.'/../model/TaskPull.php';

use Exception;
use SplFileInfo;
use BDProvider\TaskPull;

/**
 * Class TaskProcessing
 * Делает обход дерева каталогов, назначает потоки(workers)
 * @package Bundles
 */
class TaskProcessing extends Task
{
    protected $is_partial = false;
    protected $is_filename = false;
    protected $is_extension = false;

    protected $taskID;
    protected $has_error  = false;

    protected $total_processes;
    protected $process_pull = [];

    const DELETE_FILE_PROCESS = 'php worker.php';
    const DELETE_FILE_COMMAND = '--deleteFile';
    const TASK_ID_KEY = '--taskID';

    const LOG_PATH = 'logs';

    public function __construct($taskID, $params)
    {
        parent::__construct($params);
        $this->taskID = $taskID;
        $this->total_processes = Config::get('task:total_proc');
    }

    public function taskProcessing()
    {
        try{
            $handleError = $this->createTaskLogErrorFile();
            $handleSuccess = $this->createTaskLogSuccessFile();
            fclose($handleSuccess);

            if( count($this->errors) != 0 )
            {
                throw new Exception(print_r($this->errors, true), Task::ERROR_PROCESSING);
            }

            $this->isFilename();
            $this->isExtension();

            $this->readDirs( $this->getPath() );
            $this->clearPullProcesses();

        }catch (Exception $e)
        {
            $this->has_error = true;

            if($handleError)
            {
                fwrite($handleError, $e->getMessage() );
                fclose($handleError);
            }
        }

        $this->onAfterProcessing();
    }

    /**
     * Write task log in common log
     * @param int $taskID
     * @param resource $taskErrorHandle
     * @param resource $taskSuccessHandle
     */
    protected function createCommonLog()
    {
        $successCommonLog = self::getSuccessCommonLog();
        $errorCommonLog = self::getErrorCommonLog();

        $handleSuccess = fopen($successCommonLog, 'a+');
        $handleError = fopen($errorCommonLog, 'a+');

        $this->createSuccessCommonLog($handleSuccess);
        fclose($handleSuccess);
        $this->deleteTaskLogFile(TaskProcessing::getTaskSuccessLog( $this->taskID ));


        $this->createErrorCommonLog($handleError);
        fclose($handleError);
        $this->deleteTaskLogFile(TaskProcessing::getTaskErrorLog( $this->taskID ));
    }

    /**
     * @param resource $handleSuccess
     */
    protected function createSuccessCommonLog($handleSuccess)
    {
        $file_name = self::getTaskSuccessLog($this->taskID);
        $content = $this->getTaskLogContent($file_name);
        if( false === $content) return false;

        fwrite($handleSuccess,"[TASK_$this->taskID]\n");
        fwrite($handleSuccess,  $content );
        fwrite($handleSuccess,"[/TASK_$this->taskID]\n");
    }

    /**
     * @todo refactor {createSuccessCommonLog(), createErrorCommonLog()} to one method
     * @param resource $handleError
     */
    protected function createErrorCommonLog($handleError)
    {
        $file_name = self::getTaskErrorLog($this->taskID);
        $content = $this->getTaskLogContent($file_name);
        if( false === $content) return false;

        fwrite($handleError,"[TASK_$this->taskID]\n");
        fwrite($handleError,  $content );
        fwrite($handleError,"[/TASK_$this->taskID]\n");
    }

    /**
     * @param string $filename
     * @return bool|string
     */
    protected function getTaskLogContent($filename)
    {
        $file_handler = fopen($filename, 'r');
        $filesize = filesize($filename);

        if($filesize === 0) return false;
        return fread($file_handler, $filesize);
    }

    protected function onAfterProcessing()
    {
        $this->createCommonLog();

        $model = new TaskPull();
        if( $this->has_error )
        {
            $new_status = self::IN_ERROR_STATUS;
        }
        else
        {
            $new_status = self::IN_SUCCESS_STATUS;
        }
        $model->updateTaskStatus($this->taskID, $new_status);
    }

    protected function createTaskLogSuccessFile()
    {
        $handle = fopen(self::getTaskSuccessLog($this->taskID), 'w+');
        if(!$handle)
        {
            throw new Exception('Error create success task log',Task::ERROR_PROCESSING);
        }
        return $handle;
    }

    protected function createTaskLogErrorFile()
    {
        $handle = fopen(self::getTaskErrorLog($this->taskID), 'w+');
        if( !$handle )
        {
            throw new Exception('Error create error task log',Task::ERROR_PROCESSING);
        }
        return $handle;
    }


    static function getSuccessCommonLog()
    {
        return Config::get('log:taskSuccessCommonLog');
    }

    static function getErrorCommonLog()
    {
        return Config::get('log:taskErrorCommonLog');
    }

    static function getTaskSuccessLog($taskID)
    {
        return Config::get('log:taskSuccess').$taskID;
    }

    static function getTaskErrorLog($taskID)
    {
        return Config::get('log:taskError').$taskID;
    }

    /**
     * @param resource $handle
     */
    protected function deleteTaskLogFile($filePath)
    {
        unlink( $filePath );
    }

    protected function isFilename()
    {
        if( !is_null($this->filename) )
        {
            $this->filename = substr( $this->filename, 1 );
            $this->filename = substr( $this->filename, 0, -1 );

            $this->is_filename = true;
            $this->isPartial();
        }
    }

    protected function isPartial()
    {
        if( $this->filename{0} == '*' and  substr($this->filename, -1) =='*'  )
        {
            $this->is_partial = true;
        }
    }

    protected function isExtension()
    {
        if( !is_null($this->getExtension()) )
        {
            $this->extension = substr( $this->extension, 1 );
            $this->extension = substr( $this->extension, 0, -1 );

            $this->is_extension = true;
        }
    }

    protected function isDir(string $path, string $file_name):bool
    {
        $symlinks = ['.','..'];
        return ( is_dir($path) && !in_array($file_name,$symlinks) );
    }

    protected function isFile(string $file):bool
    {
        $symlinks = ['.','..'];
        return !in_array($file, $symlinks);
    }

    /**
     * Delete file if true
     * @param $file
     * @return bool
     */
    protected function checkFileMask($file)
    {
        $info = new SplFileInfo($file);

        $fileName = $info->getFilename();
        $fileExtension = $info->getExtension();

        if( $this->checkFilename($fileName) && $this->checkExtension($fileExtension) )
        {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    private function checkFilename(string $filename)
    {
        if( !$this->is_filename ) return true;
        $storage_mask = $this->getFilename();

        if( $this->is_partial )
        {
            //Delete start and end * from filename
            $storage_mask = substr( $storage_mask, 1 );
            $mask = substr( $storage_mask, 0, -1 );

            return ( false !== strpos($filename, $mask) );
        }

        return ( $filename == $storage_mask );
    }

    /**
     * @return bool
     */
    private function checkExtension(string $extension)
    {
        if( !$this->is_extension ) return false;

        $extensions = explode(',', $this->getExtension());
        return in_array($extension,$extensions);
    }

    protected function getDescriptors()
    {
        return [
            0 => array("pipe", "r"),// stdin это канал, из которого потомок будет читать
            1 => array("pipe", "w"),// stdout это канал, в который потомок будет записывать
            2 => array("pipe", "w"), // stderr это файл для записи
        ];
    }

    /**
     * Process like worker.php --deleteFile filename --taskID $taskID
     * @param $filePath
     */
    protected function deleteFileProcess($filePath)
    {
        $pipes = array();
        $process = proc_open(self::DELETE_FILE_PROCESS.' ' .
            self::DELETE_FILE_COMMAND.' '."'$filePath'".' '.
            self::TASK_ID_KEY." $this->taskID",
            $this->getDescriptors(), $pipes);

        $this->pushToPullProcesses($process, $pipes);
    }

    /**
     * @param resource $process
     * @param array $pipe
     */
    protected function pushToPullProcesses($process, $pipes)
    {
        if( count($this->process_pull)  == $this->total_processes)
        {
            $this->clearPullProcesses();
        }

        $this->process_pull[] = [
            'process' => $process,
            'pipes' => $pipes
        ];
    }

    protected function clearPullProcesses()
    {
        foreach ($this->process_pull as $process)
        {
            $pipes = $process['pipes'];

            $error = stream_get_contents($pipes[2]);

            if($error != '') $this->has_error = true;

            foreach ($pipes as $pipe) {
                if (is_resource($pipe)) {
                    fclose($pipe);
                }
            }

            proc_close($process['process']);
        }

        $this->process_pull = [];
    }

    protected function readDirs($path)
    {
        if ($handle = opendir($path)) {

            while (false !== ($file = readdir($handle))) {

                $new_path = $path.$file;

                if( $this->isDir($new_path, $file) )
                {
                    //Directory
                    $this->readDirs($new_path.'/');
                }
                elseif( $this->isFile($file) )
                {
                    //File
                    if( $this->checkFileMask($file) )
                    {
                        $this->deleteFileProcess($new_path);
                    }
                }
            }
            closedir($handle);
        }

    }

}