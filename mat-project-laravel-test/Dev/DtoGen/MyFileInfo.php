<?php
namespace Dev\DtoGen{

    use App\Utils\StrUtils;
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
    public static function getExtensionsPart(string $path): bool|string
    {
        $extensions = mb_strstr($path,'.',encoding:'UTF-8');
        if(!$extensions){
            $extensions = '';
        }
        return $extensions;
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
     * Returns directory name without ending separator
     */
    public function getDirname(): false|string
    {
         $path = $this->getPath();
        return $path !== false ? MyFileInfo::dirname($path)
        : false;
    }

    public static function omitAllExtensions(string $path,string $separator = DIRECTORY_SEPARATOR): string
    {
       $path = PathHelper::getPotentialyNonExistentAbsolutePath($path,separator:$separator);
       $skipped = StrUtils::skipAsciiChar($path,'.',0);
       $dotPos = strpos($path,'.',$skipped);
       $newPath = '';
       if($dotPos !== false){
        $newPath = substr($path,0,$dotPos);
       }
       if(!$newPath) return $path;
       return $newPath;
    }

    public static function dirname(string $path): string
    {
        $dirSepPos = mb_strrpos($path,DIRECTORY_SEPARATOR);
        if($dirSepPos === false){
            return '.';
        }
       return mb_substr($path,0,$dirSepPos);
    }

    public static function filename(string $path): array|string
    {
        $dirSepPos = mb_strrpos($path,DIRECTORY_SEPARATOR);
        if($dirSepPos === false){
            return pathinfo($path,PATHINFO_FILENAME);
        }
        return mb_substr($path,$dirSepPos+1);
    }
}
}
