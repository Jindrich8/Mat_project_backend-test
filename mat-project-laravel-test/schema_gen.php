<?php
require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Str;
use  Dev\DtoGen\MyFileInfo;
use Dev\DtoGen\PathHelper;
use  Dev\DtoGen\StarPattern;
use Dev\DtoGen\StrUtils;
use Dev\Utils\ScriptArgsBuilder;

const EXPANDED_REF = '$ref';

$SchemasDir = __DIR__ . DIRECTORY_SEPARATOR . 'schemas';
$ExpandableRefkey = '$ref';
$PathSeparator = DIRECTORY_SEPARATOR;
$SchemaExtension = ".special";
$OutputPathSeparator = DIRECTORY_SEPARATOR;
$ForceRegenerate = false;
echo "\n\n-------",MyFileInfo::omitAllExtensions(MyFileInfo::filename(__FILE__)), "-------\n";
if(ScriptArgsBuilder::create()
->optionSet(name: "dir", set: function($value)use(&$SchemasDir){
    $SchemasDir = parsePath($value,real:true);
})
    ->option(name: "refKey", var: $ExpandableRefkey)
    ->option(name: "sep", var: $PathSeparator)
    ->option(name: "extension", var: $SchemaExtension)
    ->option(name: "outSep", var: $OutputPathSeparator)
    ->flag(name: "force", var: $ForceRegenerate)
    ->fetchScriptArguments()
    ->showPassedOptions()
    ->showInvalidOptions()
    ->showNoArguments()
    ->helpRequested()) return;

$finder = PathHelper::getFinderForReadableEntries($SchemasDir)
    ->name("/(request|response)$SchemaExtension\.json$/")
    ->files();
foreach ($finder as $file) {
    $file = new MyFileInfo($file);
    $filePath = $file->getPath();
    try {
        if ($filePath === false) continue;
        $pathNoExtensions = MyFileInfo::omitAllExtensions($filePath);
        $newFilePath = $pathNoExtensions . '.json';
        // Check if generated file needs to be updated
        if (!$ForceRegenerate) {
            $fileMtime = $file->getInfo()->getMTime();
            if ($fileMtime !== false) {
                $newFileMtime = filemtime($newFilePath);
                if ($newFileMtime !== false && $newFileMtime > $fileMtime) {
                    echo "SKIPPING: ", Str::replaceStart($SchemasDir, "", $filePath), "\n";
                    continue;
                }
            }
        }
        echo "GENERATING: ", Str::replaceStart($SchemasDir, "", $newFilePath), "\n";
        $schema = $file->getContents();
        if (!$schema) {
            throw new Exception("Could not get content of file: " . $filePath);
        }
        $fileEncoding =  mb_detect_encoding($schema);
        if (!$fileEncoding) {
            throw new Exception("Could not detect encoding of file: " . $filePath);
        }

        $schema = json_decode($schema, true);
        if ($schema === null) {
            throw new Exception("Could not decode schema to json for file: " . $filePath);
        }
        $dir =  MyFileInfo::dirname($newFilePath);


        replaceAllArraysWKey(
            $schema,
            $ExpandableRefkey,
            function ($pattern)
            use ($dir, $PathSeparator, $OutputPathSeparator) {
                $filePatternAndDefPath = StrUtils::explode("#", $pattern, limit: 2, ignoreEmptyParts: false);
                if (!$filePatternAndDefPath) {
                    throw new Exception("Could not get file pattern from ref value: $pattern");
                }
                $filePathPattern = $filePatternAndDefPath[0];
                if ($filePathPattern === "") {
                    return [EXPANDED_REF => $pattern];
                }

                if (array_key_exists(1, $filePatternAndDefPath)) {
                    $filePatternAndDefPath[1] = "#" . $filePatternAndDefPath[1];
                }


                $patternParts = StrUtils::explode($PathSeparator, $filePathPattern);
                if (!$patternParts) {
                    throw new Exception("Could not split pattern '$filePathPattern' by delimiter '$PathSeparator'");
                }
                if (mb_substr($filePathPattern, 0, 1) === '.') {
                    array_unshift($patternParts, $dir);
                }
                $isStarNamePattern = false;
                $expanded = StarPattern::expandStarNameSearchPattern($patternParts,$isStarNamePattern);
                $jsonObjects = [];
                foreach ($expanded as $expandedValue) {
                    $file = new SplFileInfo($expandedValue);
                    if ($file->isFile() && ($realPath = $file->getRealPath())) {
                        if (DIRECTORY_SEPARATOR !== $OutputPathSeparator) {
                            $newRealPath = StrUtils::replace(DIRECTORY_SEPARATOR, $OutputPathSeparator, $realPath);
                            if ($newRealPath === null) {
                                throw new Exception("Could not use '$OutputPathSeparator' as output paths delimiter."
                                    . "\nPath: $realPath");
                            }
                            $realPath = $newRealPath;
                        }
                        $jsonObjects[] = [EXPANDED_REF => $realPath . ($filePatternAndDefPath[1] ?? "")];
                    }
                }
                if (!$jsonObjects) {
                    throw new Exception("Pattern path does not match any file.");
                }
                if(!$isStarNamePattern && count($jsonObjects) === 1){
                    return $jsonObjects[0];
                }
                return $jsonObjects;
            }
        );

        file_put_contents($newFilePath, json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    } catch (Exception $e) {
        echo "An error occured during processing file: ", $filePath, "\n",
        "Error: $e\n";
    }
}

function parsePath(string $path, string $sep = DIRECTORY_SEPARATOR, bool $real = false)
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

/**
 * @param array $array
 * @param string|int $key
 * @param callable(mixed):mixed $callback
 */
function replaceAllArraysWKey(array &$array, string|int $key, callable $callback)
{
    /**
     * @var array<array{array,string|int|null}> $arrays
     */
    $arrays = [];
    foreach ($array as $arrKey => $arrValue) {
        if ($arrKey === $key) {
            $array = $callback($arrValue);
        } else if (is_array($arrValue)) {
            $arrays[] = [&$array, $arrKey];
        }
    }

    while (true) {
        $entry = array_pop($arrays);
        if ($entry === null) {
            break;
        }
        list(&$grandParent, $parentKey) = $entry;
        foreach ($grandParent[$parentKey] as $arrKey => $arrValue) {
            if ($arrKey === $key) {
                $newValue  = $callback($arrValue);
                if (!is_array($newValue) || !array_is_list($newValue) || !$newValue) {
                    $grandParent[$parentKey] = $newValue;
                } else {
                    $grandParent[$parentKey] = array_shift($newValue);
                    array_push($grandParent, ...$newValue);
                }
            } else if (is_array($arrValue)) {
                $arrays[] = [&$grandParent[$parentKey], $arrKey];
            }
        }
    }
}
