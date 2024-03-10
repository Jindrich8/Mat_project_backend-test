<?php

namespace Dev\DtoGen {

    use Swaggest\PhpCodeBuilder\App\PhpApp;

    class MyApp extends PhpApp
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
