<?php

namespace Dev\DtoGen {

    use Dev\DtoGen\PathHelper;
    use Directory;
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
               // echo "Part: " . $part . "\n";
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
                       // echo "Finder parts\n";
                        // $part should never be null, because $parts must end with non pattern part
                        $finderParts = [self::toPart($part)];
                        while (true) {
                            $part = array_shift($parts);
                           // echo "Possible finder part: $part\n";
                            switch ($part) {
                                case '*':
                                case '**':
                                case null:
                                    break 2;

                                default:
                                    echo "Finder part $part\n";
                                    $finderParts[] =self::toPart($part);
                                    continue 2;
                            }
                        }
                        
                        $expandedPaths = self::getEntries($expandedPaths, $finderParts, $depth, $unlimitedDepth);
                        break;

                    default:
                        $newExpandedPaths = [];
                        $explodedPart = self::toPart($part);

                        if (!is_array($explodedPart)) {
                            $expandedPaths = array_map(
                                fn ($path) => PathHelper::concatPaths($path, $part),
                                $expandedPaths
                            );
                        } else {
                            echo "Special case \n";
                            foreach ($expandedPaths as $expandedPath) {
                                $entries = PathHelper::getNamesOfAllEntriesInDir($expandedPath);
                                $newEntries = [];
                                foreach ($entries as $entry) {
                                   if(self::matchesPart($explodedPart,$entry)){
                                    $newEntries[] = $entry;
                                   }
                                }

                                array_push(
                                    $newExpandedPaths,
                                    ...array_map(
                                        fn ($newEntry) => PathHelper::concatPaths($expandedPath, $newEntry),
                                        $newEntries
                                    )
                                );
                            }
                            $expandedPaths = $newExpandedPaths;
                        }
                        break;
                }
            }
            return $expandedPaths;
        }

        static function toPart(string $part):array|string{
           $exploded = explode('*',$part);
           if($exploded && count($exploded) > 1){
            return $exploded;
           }
           return $part;
        }

        static function matchesPart(array|string $explodedPart, string $entry)
        {
            if(!is_array($explodedPart)){
                return $explodedPart === $entry;
            }
            //echo "Special match for entry $entry: \n";
            //var_dump($explodedPart);
            $pos = 0;
            $skip = false;
            $count = count($explodedPart);
            for ($i = 0; $i < $count; ++$i) {
                if ($explodedPart[$i] === '') {
                    if (($nextPart = $explodedPart[$i + 1] ?? null) !== null) {
                        $nextPartI = Str::position($entry, $nextPart, offset: $pos);
                        if ($nextPartI === false || $nextPartI < 0) {
                            $skip = true;
                            break;
                        }
                        $pos = $nextPartI + Str::length($nextPart);
                        ++$i;
                    }
                } else {
                    if (!Str::startsWith(Str::substr($entry, $pos), $explodedPart[$i])) {
                        $skip = true;
                        break;
                    }
                    $pos += Str::length($explodedPart[$i]);
                }
            }
          //  echo "skip $entry - $skip\n";
            return !$skip;
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
                        if (self::matchesPart($parts[$i],Str::afterLast($dir, DIRECTORY_SEPARATOR))) {
                            if ($i === count($parts) - 1) {
                                $newDirs[] = $dir;
                                break;
                            }
                            $newI = $i + 1;
                        }
                        if (is_dir($dir)) {
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
                            if ($minDepth === 0) {
                                $newDirsNew = PathHelper::getAllEntriesInDir($dir);
                            } else {
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
                                if (self::matchesPart($part,Str::afterLast($dir, DIRECTORY_SEPARATOR))) {
                                    if (is_dir($dir)) {
                                        array_push($newDirs, ...PathHelper::getAllEntriesInDir($dir));
                                    } else {
                                        $newDirs[] = $dir;
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
