<?php
namespace BDProvider;

include_once __DIR__.'/../bundles/Task.php';
include_once __DIR__.'/../bundles/Config.php';

use PDO;
use Exception;
use Bundles\Task;
use Bundles\Config;

class TaskPull
{
    protected $conn;

    public function __construct()
    {
        $this->conn = $this->getConnection(Config::get('db:user'), Config::get('db:pass'));
    }

    public function migrateUp()
    {
         $query = $this->conn->query("
              SELECT table_catalog 
              FROM information_schema.tables 
              WHERE table_schema = 'public' AND table_name = 'task_pull'"
        );
         $query->execute();

        $res = $query->fetch(\PDO::FETCH_ASSOC);

        if($res) return true;

        $this->conn->query("
        CREATE TYPE task_statuses AS ENUM ('new','in_progress','success','error')
        ");

        $this->conn->query(
            "CREATE TABLE task_pull (
              task_id SERIAL NOT NULL, 
              path VARCHAR(100), 
              file_name VARCHAR(50), 
              file_extension VARCHAR(10),
              status task_statuses NOT NULL,
              timestamp TIMESTAMP DEFAULT current_timestamp, 
              PRIMARY KEY(task_id) )"
        );
    }

    public function migrateDown()
    {
        $this->conn->query("DROP TYPE task_statuses");
        $this->conn->query("DROP TABLE task_pull");
    }

    /**
     * @param \Bundles\Task $task
     * @return int
     */
    public function createTask(\Bundles\Task $task):int
    {
        $query = $this->conn->prepare("
            INSERT INTO task_pull(path,file_name,file_extension,status)
            VALUES (:path, :file_name, :file_extension, :status  )
        ");

        $new_status = $task::NEW_STATUS;
        $path = $task->getPath();
        $file = $task->getFilename();
        $ext = $task->getExtension();

        $query->bindParam(':path', $path );
        $query->bindParam(':file_name', $file );
        $query->bindParam(':file_extension', $ext );
        $query->bindParam(':status', $new_status );

        $query->execute();

        return $this->conn->lastInsertId();
    }

    /**
     * @param string $user
     * @param string $pass
     * @return PDO
     */
    protected function getConnection(string $user, string $pass): PDO
    {
        $dbType = Config::get('db:dbtype');
        $host = Config::get('db:host');
        $dbname = Config::get('db:dbname');

        return new PDO("$dbType:host=$host;dbname=$dbname", $user, $pass,
            [PDO::ATTR_PERSISTENT => true]
        );
    }

    /**
     * @param $status
     * @return array|bool
     */
    public function getTaskByStatus($status = Task::NEW_STATUS)
    {
        $res = false;

        try{
            $this->conn->beginTransaction();
            $query = $this->conn->prepare("
               SELECT task_id,path,file_name,file_extension
               FROM task_pull
               WHERE status =:status
               LIMIT 1
            ");
            $query->bindParam(':status',$status );
            $query->execute();
            $res = $query->fetch(\PDO::FETCH_ASSOC);

            if(!$res) return false;

            $this->updateTaskStatus($res['task_id'],Task::IN_PROGRESS_STATUS);

            $this->conn->commit();
        }catch (Exception $e)
        {
            $this->conn->rollBack();
        }

        return $res;
    }

    /**
     * Convert Storage to Console Data For Validation
     * @param $data
     * @return array
     */
    public function convertStorageData($data)
    {
        $params = [];
        $params[] = Task::PATH_PARAM;
        $params[] = $data['path'];
        $params[] = Task::EXTENSION_PARAM;
        $params[] = $data['file_extension'];
        $params[] = Task::FILE_PARAM;
        $params[] = $data['file_name'];

        return $params;
    }

    public function updateTaskStatus($taskID, $newStatus)
    {
        $query = $this->conn->prepare(
            "UPDATE task_pull SET status = :new_status WHERE task_id =: task_id"
        );
        $query->bindParam(':task_id', $taskID);
        $query->bindParam(':new_status', $newStatus);
        $query->execute();
    }
}
