<?php

namespace Dev\DtoGen {

    use Illuminate\Support\Str;

    class MyApp extends \Swaggest\PhpCodeBuilder\App\PhpApp
    {
        public function storeNoClear(string $path):void{
        if (DIRECTORY_SEPARATOR === '\\') {
            $path = str_replace('\\', '/', $path);
        }

        $path = rtrim($path, '/') . '/';

        foreach ($this->files as $filepath => $contents) {
            $this->putContents($path . $filepath, $contents);
        }
        }
    }
}