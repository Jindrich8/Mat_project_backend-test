<?php

namespace Dev\DtoGen {
    use Dev\DtoGen\PathHelper;
    use Illuminate\Support\Str;
    use Swaggest\JsonSchema\RemoteRef\BasicFetcher;
    use Swaggest\JsonSchema\RemoteRefProvider;

    class RelativeSchemaRefResolver implements RemoteRefProvider
    {
        private BasicFetcher $fetcher;
        private string $dir;

        public function __construct(string $dir)
        {
            $this->dir = $dir;
            $this->fetcher = new BasicFetcher();
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
