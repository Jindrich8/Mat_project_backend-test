<?php

namespace Dev\DtoGen {

    use Illuminate\Support\Str;
    use Symfony\Component\Finder\Finder;

    class PathHelper
    {

        public static function getPotentialyNonExistentAbsolutePath(string $path, string $separator = DIRECTORY_SEPARATOR)
        {
            $parts = array_filter(explode($separator, $path), fn ($value) => strlen($value));
            $absolutes = array();
            foreach ($parts as $part) {
                if ('.' == $part) continue;
                if ('..' == $part) {
                    array_pop($absolutes);
                } else {
                    $absolutes[] = $part;
                }
            }
            return implode($separator, $absolutes);
        }

        /**
         * @param string $dir
         * @return string[]
         */
        public static function getAllSubdirsInDir(string $dir): array
        {
            $subdirs = array_filter(self::getAllEntriesInDir($dir), fn ($entry) => is_dir($entry));
            return $subdirs;
        }

         /**
         * @param string $dir
         * @return string[]
         */
        public static function getAllEntriesInDir(string $dir): array
        {
            $entries = array_map(
                fn ($v) =>
                PathHelper::concatPaths($dir, $v),
                array_diff(scandir($dir), ['.', '..'])
            );
            return $entries;
        }

        /**
         * @param string $path1
         * @param string $path2
         */
        public static function concatPaths(string $path1, string $path2, string $separator = DIRECTORY_SEPARATOR): string
        {
            $first = Str::endsWith($path1, $separator);
            if ($first) {
                return $path1 . Str::replaceStart($separator, '', $path2);
            } else if (!Str::startsWith($path2, $separator)) {
                return $path1 . $separator . $path2;
            } else {
                return $path1 . $path2;
            }
        }

        /**
         * @param string $separator null = DIRECTORY_SEPARATOR
         * @param string $paths
         */
        public static function concatMultiplePaths(?string $separator = DIRECTORY_SEPARATOR,...$paths): string
        {
            return PathHelper::concatArrayOfPaths($paths,$separator ?? DIRECTORY_SEPARATOR);
        }

        /**
         * @param string[] $paths
         */
        public static function concatArrayOfPaths(array $paths,string $separator = DIRECTORY_SEPARATOR): string
        {
            $result = '';
            if ($paths) {
                $path1 = $paths[0];
                $len = count($paths);
                for ($i = 1; $i < $len; ++$i) {
                    $result .= PathHelper::concatPaths($path1, $paths[$i],$separator);
                }
            }
            return $result;
        }

        public static function getFinderForReadableEntries(string|array $searchIn)
        {
            return Finder::create()->in($searchIn)->ignoreDotFiles(true)
                ->ignoreUnreadableDirs(true);
        }
    }
}
