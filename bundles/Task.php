<?php
namespace Bundles;

use Exception;

class Task
{
    protected $path;
    protected $filename;
    protected $extension;
    protected $errors =[];
    protected $params=[];

    const ERROR_INITIAL = 100;
    const ERROR_PROCESSING = 200;

    const PATH_PARAM = '--add';
    const FILE_PARAM = '--file';
    const EXTENSION_PARAM = '--extension';

    const NEW_STATUS = 'new';
    const IN_PROGRESS_STATUS = 'in_progress';
    const IN_SUCCESS_STATUS = 'success';
    const IN_ERROR_STATUS = 'error';

    public function __construct($params)
    {
        try{
            $this->setParams($params);
            $this->setPath();
            $this->setFilename();
            $this->setExtension();

        }catch (Exception $e)
        {
            $this->setErrors([
                'code' => $e->getCode(),
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * @return array
     */
    private function rules():array
    {
        return [
            'required' => [self::PATH_PARAM]
        ];
    }

    public static function printHelp()
    {
        echo"Help: ";print_r([
        self::PATH_PARAM => 'Folder Path. Required.',
        self::FILE_PARAM => "File name must mutch to pattern [file_name] - full, or [*file_name*] - partial  ",
        self::EXTENSION_PARAM => 'Extension must mutch to pattern [extension,extension] or [*] '
    ]);

        return true;
    }

    /**
     * @param array $error
     */
    protected function setErrors(array $error)
    {
        $this->errors[] = $error;
    }

    /**
     * @return array
     */
    public function getErrors():array
    {
        return $this->errors;
    }

    /**
     * @param array $params
     */
    protected function setParams($params):void
    {
        if( count($params) < 2 )
        {
            throw new Exception('Parameters doesn`t exist', self::ERROR_INITIAL);
        }

        $this->params = $params;
    }

    /**
     * @return array
     */
    protected function getParams()
    {
        return $this->params;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    protected function setPath(): void
    {
        $paramName = self::PATH_PARAM;
        $path = $this->getParamByName($paramName);

        if(!file_exists($path) or !is_dir($path) )
        {
            throw new Exception('Can`t find directory '.$path, self::ERROR_INITIAL);
        }

        if( substr($path,-1) != '/' ) $path .= '/';

        $this->path = $path;
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Valid text `text` or *text*
     */
    public function setFilename(): void
    {
        $paramName = self::FILE_PARAM;
        $filename = $this->getParamByName($paramName);

        if(is_null($filename)) return;

        $pattern = (preg_match('/^\[\w+\]$/',$filename)
            || preg_match('/^\[\*\w+\*\]$/',$filename) );


        if(!$pattern)
        {
            throw new Exception("$paramName must mutch to pattern [*file*] - partial, [file] - full. Get $filename ",
                self::ERROR_INITIAL);
        }

        $this->filename = $filename;
    }

    /**
     * @return string
     */
    public function getExtension()
    {
        return $this->extension;
    }

    protected function setExtension(): void
    {
        $paramName = self::EXTENSION_PARAM;
        $extension = $this->getParamByName($paramName);

        if(is_null($extension)) return;

        $extension = str_replace(' ','',$extension );

        //png, mp3..
        $pattern = preg_match('/^\[([0-9a-z]+)([,][0-9a-z]+)*\]$/',$extension);

        if(!$pattern)
        {
            throw new Exception("$paramName must mutch to pattern [extension,extension] or [*]. Get $extension ",
                self::ERROR_INITIAL);
        }

        $this->extension = $extension;
    }

    /**
     * @param string $param_name
     * @return mixed
     */
    protected function getParamByName(string $paramName)
    {
        $rules = $this->rules();

        $index = array_search($paramName,$this->params);

        //param not existed
        if(false === $index)
        {
            if( false !== array_search($paramName,$rules['required']) )
            {
                throw new Exception("$paramName param doesn`t exist", self::ERROR_INITIAL);
            }
            return null;
        }

        //param value not existed
        if( !isset($this->params[$index+1]) )
        {
            throw new Exception("$paramName value doesn`t exist. Set Param value ", self::ERROR_INITIAL);
        }

        return $this->params[$index+1];
    }
}