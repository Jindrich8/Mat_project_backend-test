<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Str;
use  Dev\DtoGen\PhpDtosGenerator;
use  Dev\DtoGen\MyFileInfo;
use Dev\DtoGen\PathHelper;
use Dev\Utils\ScriptArgsBuilder;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\Finder;


$SchemasDir = __DIR__ . DIRECTORY_SEPARATOR . 'schemas';
$TargetDir = __DIR__ . DIRECTORY_SEPARATOR
    . 'app' . DIRECTORY_SEPARATOR
    . 'Dtos' . DIRECTORY_SEPARATOR
    . 'Task';
$TargetNamespace = 'App' . DIRECTORY_SEPARATOR
    . 'Dtos' . DIRECTORY_SEPARATOR
    . 'Task';

$PathSeparator = DIRECTORY_SEPARATOR;
$ExludeSchemas = "defs";
$ForceRegenerate = false;
$SchemaNamePattern = <<<'EOF'
/(request|response)\.json$/
EOF;

echo "\n\n-------", MyFileInfo::omitAllExtensions(MyFileInfo::filename(__FILE__)), "-------\n";
if (ScriptArgsBuilder::create()
    ->optionSet(name: "dir", set: function ($value) use (&$SchemasDir) {
        $SchemasDir = PathHelper::parsePath($value, real: true);
    })
    ->optionSet(name: "targetDir", set: function ($value) use (&$TargetDir) {
        $TargetDir = PathHelper::parsePath($value, real: true);
    })
    ->optionSet(name: "targetNamespace", set: function ($value) use (&$TargetNamespace) {
        $TargetNamespace = PathHelper::parsePath($value);
    })
    ->option(name: "sep", var: $PathSeparator)
    ->optionSet(name: 'excludeRelDir', set: function ($value) use (&$ExludeSchemas) {
        $ExludeSchemas = PathHelper::parsePath($value, sep: '/');
    })
    ->option(name: "schemaNamePattern", var: $SchemaNamePattern)
    ->flag(name: "force", var: $ForceRegenerate)
    ->fetchScriptArguments()
    ->showPassedOptions()
    ->showInvalidOptions()
    ->showNoArguments()
    ->helpRequested()
) return;

$finder = PathHelper::getFinderForReadableEntries($SchemasDir)
    ->name($SchemaNamePattern)
    ->notPath($ExludeSchemas)
    ->files();

foreach ($finder as $file) {
    if ($file->isReadable()) {
        $file = new MyFileInfo($file);
        $path = realpath($file->getPath());
        if ($path) {
            $relPath = Str::replaceStart($SchemasDir, '', $path);
            $parts = explode(DIRECTORY_SEPARATOR, $relPath);
            $parts = array_map(fn($part) => Str::studly($part), $parts);
            $parsedRelPath = implode(DIRECTORY_SEPARATOR, $parts);
            $fileName = $file->getFilenameWithoutExtensions();
            $schema = json_decode($file->getContents());
            if (is_array($schema) || is_object($schema)) {
                $parsedRelPathNoExtensions = MyFileInfo::omitAllExtensions($parsedRelPath);
                $targetPath = PathHelper::concatPaths($TargetDir, $parsedRelPathNoExtensions);
                // construct relative path to target
                $targetRelPath = Str::replaceFirst(__DIR__ . DIRECTORY_SEPARATOR, '', $targetPath);
                // generate ?
                $generate = $ForceRegenerate;
                if (!$generate) {
                    $mTime = $file->getInfo()->getMTime();
                    $generate = $mTime === false || phpFilesChange($mTime, $targetPath);
                }
                if ($generate) {
                    $targetNamespace = PathHelper::concatPaths($TargetNamespace, $parsedRelPathNoExtensions);

                    echo "\n\ngenerating... $fileName",
                    "\ntarget path: $targetRelPath",
                    "\ntarget namespace: $targetNamespace\n";
                    $fileDir = MyFileInfo::dirname($path);
                    echo "FilePath: $path\n FileDir: $fileDir\n";
                    PhpDtosGenerator::generate(
                        schemaData: $schema,
                        rootName: $fileName,
                        basePath: $targetPath,
                        baseNameSpace: "App\\Dtos", //$targetNamespace,
                        relResolverDir: $fileDir,
                        schemaFilePath: $path,
                        separator: $PathSeparator
                    );
                } else {
                    echo "\n\nskipping... $fileName",
                    "\ntarget path: $targetRelPath\n";
                }
            }
        }
    }
}

function phpFilesChange(int $lastTime, string|array $targetPath): bool
{
    try {
        return filesChange(
            PathHelper::getFinderForReadableEntries($targetPath)
                ->name('/.php$/')
                ->files(),
            $lastTime
        );
    } catch (DirectoryNotFoundException $ex) {
        return true;
    }
}

function filesChange(Finder $finder, int $lastTime): bool
{
    foreach ($finder as $file) {
        $time = $file->getMTime();
        if ($time === false || $time < $lastTime) {
            return true;
        }
    }
    return false;
}
