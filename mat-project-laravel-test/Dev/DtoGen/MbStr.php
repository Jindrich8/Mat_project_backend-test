<?php
namespace Dev\DtoGen{

use Illuminate\Support\Str;

    class MbStr{

    private string $str;

    public function __construct(string $str){
        $this->str = $str;

    }

    public function after(string $str){
     return Str::after($this->str,$str);
    }

    public function before(string $str){
        return Str::before($this->str,$str);
       }

}
}
