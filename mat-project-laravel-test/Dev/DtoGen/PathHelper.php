<?php

namespace Dev\DtoGen {

    use Exception;
    use Illuminate\Support\Str;
    use Symfony\Component\Finder\Finder;

    class PathHelper
    {
        /**
         * @throws Exception
         */
        public static function parsePath(string $path, string $sep = DIRECTORY_SEPARATOR, bool $real = false): array|false|string
        {
            $replaced = Str::replace(['/', '\\'], $sep, $path);
            if ($real) {
                $replaced = realpath($replaced);
                if ($replaced === false) {
                    throw new Exception("Path should exist '$path'");
                }
            }
            return $replaced;
        }
        public static function getPotentialyNonExistentAbsolutePath(string $path, string $separator = DIRECTORY_SEPARATOR): string
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
            return array_filter(self::getAllEntriesInDir($dir), fn ($entry) => is_dir($entry));
        }

        /**
         * @param string $dir
         * @return string[]
         */
        public static function getNamesOfAllEntriesInDir(string $dir): array
        {
            return array_diff(scandir($dir), ['.', '..']);
        }

        /**
         * @param string $dir
         * @return string[]
         */
        public static function getNamesOfAllSubdirsInDir(string $dir): array
        {
            return array_filter(
                self::getNamesOfAllEntriesInDir($dir),
                fn ($entry) =>
                is_dir(PathHelper::concatPaths($dir, $entry))
            );
        }

        /**
         * @param string $dir
         * @return string[]
         */
        public static function getAllEntriesInDir(string $dir): array
        {
            return array_map(
                fn ($v) =>
                PathHelper::concatPaths($dir, $v),
                self::getNamesOfAllEntriesInDir($dir)
            );
        }

        /**
         * @param string $path1
         * @param string $path2
         * @param string $separator
         * @return string
         */
        public static function concatPaths(string $path1, string $path2, string $separator = DIRECTORY_SEPARATOR): string
        {
            if ($path1) {
                $first = Str::endsWith($path1, $separator);
                if (!$path2) {
                    return $first ? substr($path1, 0, strlen($path1) - strlen($separator)) : $path1;
                } else if (Str::startsWith($path2, $separator)) {
                    return $path1 .  ($first ? substr($path2, strlen($separator)) : $path2);
                } else {
                    return $path1 . ($first ? $path2 : $separator . $path2);
                }
            } else if ($path2) {
                return Str::replaceStart($path2, '', $separator);
            }
            else{
                return '';
            }
        }

        /**
         * @param string|null $separator null = DIRECTORY_SEPARATOR
         * @param mixed ...$paths
         * @return string
         */
        public static function concatMultiplePaths(?string $separator = DIRECTORY_SEPARATOR, ...$paths): string
        {
            return PathHelper::concatArrayOfPaths($paths, $separator ?? DIRECTORY_SEPARATOR);
        }

        /**
         * @param string[] $paths
         */
        public static function concatArrayOfPaths(array $paths, string $separator = DIRECTORY_SEPARATOR): string
        {
            $result = '';
            if ($paths) {
                $path1 = $paths[0];
                $len = count($paths);
                for ($i = 1; $i < $len; ++$i) {
                    $result .= PathHelper::concatPaths($path1, $paths[$i], $separator);
                }
            }
            return $result;
        }

        public static function getFinderForReadableEntries(string|array $searchIn): Finder
        {
            return Finder::create()->in($searchIn)->ignoreDotFiles(true)
                ->ignoreUnreadableDirs(true);
        }



        public static function isRelative(string $path,string $sep = DIRECTORY_SEPARATOR): bool
        {
            return Str::startsWith($path, '.')
                || !(
                    windows_os() ?
                    Str::charAt($path, 1) === ':'
                    : Str::startsWith($path, $sep)
                );
        }
    }
}
