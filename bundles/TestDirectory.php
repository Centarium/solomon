<?php
namespace Bundles;
include_once __DIR__.'/../bundles/Config.php';

class TestDirectory
{
    public $rootPath;

    public function __construct()
    {
        $this->rootPath = Config::get('testStorage');

        if( is_dir($this->rootPath) )
        {
            exec("rm -R $this->rootPath");
        }

        mkdir($this->rootPath);
    }

    public function createDirs(string $Path,int $numDirs,int $numLevels, int $Level=0):void
    {
        $Level++;
        for($i=0;$i<$numDirs;$i++)
        {
            $new_path = "$Path/$i";
            mkdir($new_path);
            exec("touch $new_path/file_".random_int(1,10).'.txt');
            exec("touch $new_path/file_".random_int(5,20).'.csv');
            $this->createImage($i, $new_path);

            if( $Level < $numLevels )
            {
                $this->createDirs($new_path,$numDirs,$numLevels,$Level);
            }
        }
    }

    protected function createImage($i, $path)
    {
        $image_name = random_int(1000,1200).'X'.random_int(700,900);
        $img = imagecreatetruecolor(320, 240);
        $text_color = imagecolorallocate($img, 233, 14, 91);
        imagestring($img, 2, 5, 5,  "This is example $i" , $text_color);
        imagepng($img,"$path/preview_$image_name.png");
        imagedestroy($img);
    }
}