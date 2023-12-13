<?php
namespace Dev\DtoGen{

use Illuminate\Support\Str;
use Symfony\Component\Finder\SplFileInfo;

class MbStr{

    private string $str;

    public function __construct(string $str){
        $this->str = $str;
        
    }

    public function after(string $str){
     return mb_strstr($this->str,$str);
    }

    public function before(string $str){
        return mb_strstr($this->str,$str,true);
       }

}
}