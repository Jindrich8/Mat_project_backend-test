<?php

namespace Dev\DtoGen {
    use Dev\DtoGen\PathHelper;
    use Illuminate\Support\Str;

    class RelativeSchemaRefResolver implements \Swaggest\JsonSchema\RemoteRefProvider
    {
        private \Swaggest\JsonSchema\RemoteRef\BasicFetcher $fetcher;
        private string $dir;

        public function __construct(string $dir)
        {
            $this->dir = $dir;
            $this->fetcher = new \Swaggest\JsonSchema\RemoteRef\BasicFetcher();
        }

        function getSchemaData($url)
        {
            echo "Resolving path: " . $url . "..." . "\n";
            // if(PathHelper::isRelative($url)){
            //     $url = PathHelper::concatPaths($this->dir,$url);
            //    $url = Str::replace(['/','\\'],DIRECTORY_SEPARATOR,$url);
            //   $url = PathHelper::getPotentialyNonExistentAbsolutePath($url);
            //   echo "Resolved path: " . $url . "..." . "\n";
            // }
            return $this->fetcher->getSchemaData($url);
        }
    }
}