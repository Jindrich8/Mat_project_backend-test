<?php
namespace Dev\DtoGen{

use Illuminate\Support\Str;
use Symfony\Component\Finder\SplFileInfo;

class MyFileInfo{
    
    private SplFileInfo $file;
    public function __construct(SplFileInfo $file){
       $this->file = $file;
    }

    public function getInfo():SplFileInfo{
        return $this->file;
    }

    public function getFilenameWithoutExtensions(){
        return Str::before($this->file->getBasename(),'.');
    }

    public function getFilename(){
        return $this->file->getBasename();
    }

    public function getPath(){
        return $this->file->getRealPath();
    }

    public function getContents(){
        return $this->file->getContents();
    }

    /**
     * Returns directory name without endig separator
     */
    public function getDirname(){
         $path = $this->getPath();
        return $path !== false ? MyFileInfo::dirname($path) 
        : $path;
    }

    public function getPathWithoutExtensions(){
        $path = $this->getPath();
        return $path !== false ? MyFileInfo::omitAllExtensions($path) 
        : $path;
    }

    public static function omitAllExtensions(string $path){
       $path = PathHelper::getPotentialyNonExistentAbsolutePath($path);
       $newPath = Str::before($path,'.');
       if($newPath === false) return $path;
       return $newPath;
    }

    public static function dirname(string $path){
        $dirSepPos = mb_strrpos($path,DIRECTORY_SEPARATOR);
        if($dirSepPos === false){
            return '.';
        }
       return mb_substr($path,0,$dirSepPos);
    }

    public static function filename(string $path){
        $dirSepPos = mb_strrpos($path,DIRECTORY_SEPARATOR);
        if($dirSepPos === false){
            return pathinfo($path,PATHINFO_FILENAME);
        }
        return mb_substr($path,$dirSepPos+1);
    }
}
}