<?php
require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Str;
use  Dev\DtoGen\MyFileInfo;
use Dev\DtoGen\PathHelper;
use Dev\DtoGen\StrUtils;
use Dev\Utils\ScriptArgsBuilder;
use Symfony\Component\Finder\SplFileInfo;

const EXPANDED_REF = '$ref';
$Context = new Context();

echo "\n\n-------", MyFileInfo::omitAllExtensions(MyFileInfo::filename(__FILE__)), "-------\n";
if (ScriptArgsBuilder::create()
    ->optionSet(name: "dir", set: function ($value) use ($Context) {
        $Context->SchemasDir = PathHelper::parsePath($value, real: true);
    })
    ->option(name: "refKey", var: $Context->ExpandableRefkey)
    ->option(name: "sep", var: $Context->PathSeparator)
    ->option(name: "outSep", var: $Context->OutputPathSeparator)
    ->option(name: 'specialExtension', var: $Context->SpecialExtension)
    ->option(name: "schemaNamePattern", var: $Context->SchemaNamePattern)
    ->flag(name: "force", var: $Context->ForceRegenerate)
    ->fetchScriptArguments()
    ->showPassedOptions()
    ->showInvalidOptions()
    ->showNoArguments()
    ->helpRequested()
) return;

$finder = PathHelper::getFinderForReadableEntries($Context->SchemasDir)
    ->name($Context->SchemaNamePattern)
    ->files();


foreach ($finder as $file) {
    $file = new MyFileInfo($file);
    processFile(
        file: $file,
        Context: $Context
    );
}
class Context
{
    public function __construct(
        public string $SchemasDir = __DIR__ . DIRECTORY_SEPARATOR . 'schemas',
        public string $ExpandableRefkey = '$ref',
        public string $PathSeparator = DIRECTORY_SEPARATOR,
        public string $OutputPathSeparator = DIRECTORY_SEPARATOR,
        public string $SpecialExtension = '.special',
        public string $SchemaNamePattern = <<<'EOF'
/(request|response)\.special\.json$/
EOF,
        public bool $ForceRegenerate = false,
    ) {
    }

    public function normalFromSpecial(string $specialFilePath, string $separator = DIRECTORY_SEPARATOR)
    {
        return MyFileInfo::omitAllExtensions($specialFilePath, $separator) . '.json';
    }

    public function specialFromNormal(string $filePath, string $separator = DIRECTORY_SEPARATOR)
    {
        return MyFileInfo::omitAllExtensions($filePath, $separator) . $this->SpecialExtension . '.json';
    }

    public function normalizePathForOutput(string $path)
    {
        return $this->normalizePath($path, $this->OutputPathSeparator);
    }

    public function normalizePath(string $path, string $sep)
    {
        return Str::replace(
            search: array_keys([
                '/' => 0,
                '\\' => 0,
                DIRECTORY_SEPARATOR => 0,
                $this->PathSeparator => 0,
                $this->OutputPathSeparator => 0
            ]),
            replace: $sep,
            subject: $path
        );
    }
}

/**
 * @throws Exception
 */
function processFile(
    MyFileInfo $file,
    Context $Context,
) {
    $filePath = $file->getPath();
    try {
        if ($filePath === false) return;
        $newFilePath = $Context->normalFromSpecial($filePath);
        // Check if generated file needs to be updated
        if (!$Context->ForceRegenerate) {
            $fileMtime = $file->getInfo()->getMTime();
            if ($fileMtime !== false) {
                $newFileMtime = filemtime($newFilePath);
                if ($newFileMtime !== false && $newFileMtime > $fileMtime) {
                    echo "SKIPPING: ", Str::replaceStart($Context->SchemasDir, "", $filePath), "\n";
                    return;
                }
            }
        }
        echo "GENERATING: ", Str::replaceStart($Context->SchemasDir, "", $newFilePath), "\n";
        $schema = $file->getContents();
        if (!$schema) {
            throw new Exception("Could not get content of file: " . $filePath);
        }
        $fileEncoding =  mb_detect_encoding($schema);
        if (!$fileEncoding) {
            throw new Exception("Could not detect encoding of file: " . $filePath);
        }
        $content = $schema;
        $schema = json_decode($schema, true, flags: JSON_THROW_ON_ERROR);
        if ($schema === null) {
            echo "content: ";
            dump($content);
            throw new Exception("Could not decode schema to json for file: " . $filePath);
        }
        $dir =  MyFileInfo::dirname($newFilePath);


        replaceAllArraysWKey(
            $schema,
            $Context->ExpandableRefkey,
            function ($pattern)
            use ($dir, $Context) {
                $filePatternAndDefPath = StrUtils::explode("#", $pattern, limit: 2, ignoreEmptyParts: false);
                if (!$filePatternAndDefPath) {
                    throw new Exception("Could not get file pattern from ref value: $pattern");
                }
                $filePathPattern = $Context->normalizePath($filePatternAndDefPath[0], '/');
                if ($filePathPattern === "") {
                    return [EXPANDED_REF => $pattern];
                }
                $defPath = "";
                if (array_key_exists(1, $filePatternAndDefPath)) {
                    $defPath = "#" . $filePatternAndDefPath[1];
                }
               $filePathPattern = Str::replaceStart(
                    '@',
                $Context->normalizePath($Context->SchemasDir, '/'),
                $filePathPattern
            );
                if (PathHelper::isRelative($filePathPattern, '/')) {
                    $filePathPattern = PathHelper::concatPaths(
                        $Context->normalizePath($dir, '/'),
                        $filePathPattern,
                        separator: '/'
                    );
                }
                /** @noinspection PhpRegExpInvalidDelimiterInspection */
                $regex = <<<'EOF'
                /(\\\\)*[\?\*\[]/u
                EOF;
                $isStarNamePattern = preg_match($regex, $filePathPattern, $matches);
                if ($isStarNamePattern === false) {
                    throw new Exception("Could not recognize if ref '$filePathPattern' is a glob pattern.");
                }
                $isStarNamePattern = (bool)$isStarNamePattern;

                $expanded = array_unique(
                    array_values(
                    array_filter(
                        PathHelper::globstar(PathHelper::getPotentialyNonExistentAbsolutePath($filePathPattern, '/')),
                        fn (string $value) => MyFileInfo::getExtensionsPart($value) === '.json'
                    )
                )
            );

                $specialFilePath = $Context->specialFromNormal($filePathPattern, '/');
                echo "Patterns: ";
                dump([$filePathPattern, $specialFilePath]);
                $specialExpanded = array_unique(PathHelper::globstar($specialFilePath));

                if (count($expanded) < count($specialExpanded)) {
                    echo "Special expanded ";
                    dump($specialExpanded);
                    $files = array_diff(array_map(
                        fn ($specialPath) => $Context->normalFromSpecial($specialPath, '/'),
                        $specialExpanded
                    ), $expanded);
                    foreach ($files as $preprocessFile) {
                        processFile(
                            new MyFileInfo(
                                new SplFileInfo(
                                    $Context->specialFromNormal($preprocessFile, '/'),
                                    "",
                                    ""
                                )
                            ),
                            $Context
                        );
                    }
                    array_push($expanded, ...$files);
                }
                echo "Expanded: ";
                dump($expanded);
                $jsonObjects = array_map(
                    fn ($expandedFile) => [
                        EXPANDED_REF => $Context->normalizePathForOutput(realpath($expandedFile)) . $defPath
                    ],
                    $expanded
                );
                if (!$jsonObjects) {
                    throw new Exception("Pattern path does not match any file.\nPattern: '$pattern'.");
                }
                if (!$isStarNamePattern && count($jsonObjects) === 1) {
                    echo "Is single ref\n";
                    dump($jsonObjects[0][EXPANDED_REF]);
                    return $jsonObjects[0][EXPANDED_REF];
                }
                return $jsonObjects;
            }
        );

        file_put_contents($newFilePath, json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    } catch (Exception $e) {
        throw new Exception(
            message: "An error occured during processing file: " . $filePath . "\n",
            previous: $e
        );
    }
}

/**
 * @throws Exception
 */
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
            $newValue = $callback($arrValue);
            if(!is_array($newValue)){
                $array[$arrKey] = $newValue;
            }
            else{
                $array = $newValue;
            }
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
                if(!is_array($newValue)){
                    $grandParent[$parentKey][$arrKey] = $newValue;
                }
                else if (!is_array($newValue) || !array_is_list($newValue) || !$newValue) {
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
