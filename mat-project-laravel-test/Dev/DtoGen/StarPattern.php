<?php

namespace Dev\DtoGen {

    use Dev\DtoGen\PathHelper;
    use Dotenv\Exception\InvalidPathException;
    use Illuminate\Support\Str;

    class StarPattern
    {

        /**
         * This function may return **non existing** file or directory
         * @param $pattern
         * @return string[]
         */
        public static function expandStarNameSearchPattern(
            array $patternParts,
            bool|null &$expanded = null
        ): array {
            $parts = $patternParts;
            $partsCount = count($parts);
            if ($partsCount < 1) return [];

            $firstPart = array_shift($parts);
            switch ($firstPart) {
                case '*':
                case "**":
                    throw new InvalidPathException(
                        "Path should not start with any pattern ("
                            . implode(DIRECTORY_SEPARATOR, $patternParts)
                            . ")."
                    );
            }
            // We need to substract 2 from $partsCount because we shifted first part out of it
            $lastPart = $parts[$partsCount - 2];
            switch ($lastPart) {
                case '*':
                case "**":
                    throw new InvalidPathException(
                        "Path should not end with any pattern ("
                            . implode(DIRECTORY_SEPARATOR, $patternParts)
                            . ")."
                    );
            }

            $expandedPaths = [$firstPart];
            while (($part = array_shift($parts))) {
                $depth = 0;
                $unlimitedDepth = false;
                switch ($part) {
                    case "**":
                        $unlimitedDepth = true;
                    case "*":
                        if (!$unlimitedDepth) {
                            $depth = 1;
                        }
                        $expanded = true;
                        $spliceCount = 0;
                        for ($i = count($expandedPaths) - 1; $i >= 0; --$i) {
                            $expanded = $expandedPaths[$i];
                            // expandedPaths must be directories because $parts cannot end with pattern part
                            if (!is_dir($expanded)) {
                                ++$spliceCount;
                            } else if ($spliceCount !== 0) {
                                $expandedPaths = array_splice($expandedPaths, $i + 1, $spliceCount);
                                $spliceCount = 0;
                            }
                        }
                        while (true) {
                            $part = array_shift($parts);
                            switch ($part) {
                                case '*':
                                    $depth += 1;
                                    continue 2;
                                case '**':
                                    $unlimitedDepth = true;
                                    continue 2;

                                default:
                                    break 2;
                            }
                        }

                        // $part should never be null, because $parts must end with non pattern part
                        $finderParts = [$part];
                        while (true) {
                            $part = array_shift($parts);
                            switch ($part) {
                                case '*':
                                case '**':
                                case null:
                                    break 2;

                                default:
                                    $finderParts[] = $part;
                                    continue 2;
                            }
                        }
                        $expandedPaths = self::getEntries($expandedPaths, $finderParts, $depth, $unlimitedDepth);
                        break;

                    default:
                        for ($i = count($expandedPaths) - 1; $i >= 0; --$i) {
                            $expandedPaths[$i] .= DIRECTORY_SEPARATOR . $part;
                        }
                        break;
                }
            }
            return $expandedPaths;
        }

        static function getEntriesSParts(array &$dirs, array &$parts, int $i)
        {
            $newDirs = [];
            foreach ($dirs as $dir) {
                switch ($dir) {
                    case '.':
                    case '..':
                        continue 2;

                    default:
                        $newI = 0;
                        if ($parts[$i] === Str::afterLast($dir,DIRECTORY_SEPARATOR)) {
                            if ($i === count($parts) - 1) {
                                $newDirs[] = $dir;
                                break;
                            }
                            $newI = $i + 1;
                        }
                        if(is_dir($dir)){
                            $newDirsNew = PathHelper::getAllEntriesInDir($dir);
                            array_push($newDirs, ...self::getEntriesSParts($newDirsNew, $parts, $newI));
                        }
                        break;
                }
            }
            return $newDirs;
        }

        static function getEntries(array $dirs, array $parts, int $minDepth, bool $unlimitedDepth)
        {
            for (; $minDepth >= 0; --$minDepth) {
                $newDirs = [];
                foreach ($dirs as $dir) {
                    switch ($dir) {
                        case '.':
                        case '..':
                            continue 2;

                        default:
                        $newDirsNew = [];
                            if($minDepth === 0){
                                $newDirsNew = PathHelper::getAllEntriesInDir($dir);
                            }
                            else{
                                $newDirsNew = PathHelper::getAllSubdirsInDir($dir);
                            }
                            array_push(
                                $newDirs,
                                ...$newDirsNew
                            );
                            break;
                    }
                }
                $dirs = $newDirs;
               
            }
            if (!$unlimitedDepth) {
                foreach ($parts as $part) {
                    $newDirs = [];
                    foreach ($dirs as $dir) {
                        switch ($dir) {
                            case '.':
                            case '..':
                                continue 2;

                            default:
                                if ($part === Str::afterLast($dir,DIRECTORY_SEPARATOR)) {
                                   if(is_dir($dir)){
                                    array_push($newDirs,...PathHelper::getAllEntriesInDir($dir));
                                   }
                                   else{
                                    $newDirs[]=$dir;
                                   }
                                }
                                break;
                        }
                    }
                    $dirs = $newDirs;
                }
            } else {
                $dirs = self::getEntriesSParts($dirs, $parts, 0);
            }
            return $dirs;
        }
    }
}
