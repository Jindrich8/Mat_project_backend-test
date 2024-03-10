<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Utils\ValidateUtils;
use Illuminate\Support\Str;
use  Dev\DtoGen\MyFileInfo;
use Dev\DtoGen\PathHelper;
use Dev\Utils\ScriptArgsBuilder;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Illuminate\Support\Arr;
use Symfony\Component\Finder\Finder;
use Dev\LaravelServer;


$ModelsDir = __DIR__ . 'app/Models';
$DestinationDir = __DIR__ . 'app/ModelConstants';
$DestinationPrefix = '';
$DestinationSuffix = 'Constants';
$ConstantsFileNamePattern = '*.php';
$Prefix = "";
$Suffix = "";
$Indentation = 4;
$IndentationChar = ' ';
$LineSep = PHP_EOL;

echo "\n\n-------", MyFileInfo::omitAllExtensions(MyFileInfo::filename(__FILE__)), "-------\n";
if (ScriptArgsBuilder::create()
    ->optionSet(name: "modelsDir", set: function ($value) use (&$ModelsDir) {
        $ModelsDir = PathHelper::parsePath($value, real: true);
    })
    ->option(name: "constantsFileNamePattern", var: $ConstantsFileNamePattern)
    ->option(name: 'destinationDir', var: $DestinationDir)
    ->option(name: 'destinationPrefix', var: $DestinationPrefix)
    ->option(name: 'destinationSuffix', var: $DestinationSuffix)
    ->optionSet(name: 'indentation', set: function ($value) use (&$Indentation) {
        $Indentation = ValidateUtils::validateInt($value, inclusiveMin: 0);
    })
    ->optionSet(name: 'indentationChar', set: function ($value) use (&$IndentationChar) {
        $IndentationChar = Str::charAt($value, 0);
    })
    ->option(name: 'lineSep', var: $LineSep)
    ->option(name: 'prefix', var: $Prefix)
    ->option(name: 'suffix', var: $Suffix)
    ->fetchScriptArguments()
    ->showPassedOptions()
    ->showInvalidOptions()
    ->showNoArguments()
    ->helpRequested()
) return;

$IndentationStr = str_repeat($IndentationChar, $Indentation);
$finder = PathHelper::getFinderForReadableEntries($ModelsDir)
    ->name($ConstantsFileNamePattern)
    ->files();

    $generatedFiles = [];
foreach ($finder as $file) {
    echo "File: " . $file->getFilenameWithoutExtension() . "\n";
    $realPath = $file->getRealPath();
    if (!$realPath){
        echo "SKIPPING DUE TO NON EXITENCE\n";
    }
    $obj = new SplFileObject($realPath);
    $continue = false;
    while (true) {
        $line = $obj->getCurrentLine();
        if ($obj->eof()){
            echo "SKIPPING DUE TO MISSING namespace App\\Models\n";
            continue 2;
        }
        $line = trim($line);
        if (preg_match('/^namespace\\s*App\\\\Models\\s*[;{]$/', $line)) {
            $continue = true;
            break;
        }
    }

    $modelName = $file->getFilenameWithoutExtension();
    $class = "App\\Models\\$modelName";
    $instance = null;
    try {
        /**
         * @var Illuminate\Database\Eloquent\Model $instance
         */
        $instance = new $class;
    } catch (Throwable $e) {
        echo "SKIPPING DUE TO ERROR WHILE INSTANTING MODEL\nERROR: ".$e->getMessage()."\n";
        continue;
    }


    $table = $instance->getTable();
    $columns = [];
    LaravelServer::execute(function () use (&$columns, $instance) {
        $columns[] = Schema::getColumnListing($instance->getTable());
    });
    $columns = Arr::flatten($columns);

    //$instance->getConnection()->getSchemaBuilder()->getColumnListing($instance->getTable());
    echo "COLUMNS: ";
    dump($columns);
    $constantStrs = array_map(
        fn ($column) => $IndentationStr."const $Prefix" . Str::upper(Str::snake($column)) . "$Suffix = '$column';",
        $columns
    );
    array_unshift($constantStrs,$IndentationStr."const TABLE_NAME = '".$instance->getTable()."';","");
    $constantsStr = implode($LineSep, $constantStrs);
    $searchClassBracket = true;

    if (!File::exists($DestinationDir)) {
        if (!File::makeDirectory($DestinationDir, recursive: true)) {
            throw new Exception("Could not create destination directory");
        }
    }
    $destClassName = $DestinationPrefix . $modelName . $DestinationSuffix;
    $destFileName = $destClassName . '.php';
   $namespace = PathHelper::parsePath($DestinationDir);
   $namespace = Str::after($namespace,DIRECTORY_SEPARATOR.'app'. DIRECTORY_SEPARATOR);
   $namespace = Str::ucfirst($namespace);
    $modelClassStr = <<<EOF
    <?php
    /*
    This file was carefully crafted by mean machine.
    Do not change this file!
    */
    namespace App\\$namespace;

    class $destClassName
    {
    $constantsStr
    }
    EOF;
    echo "Generating '$destFileName'\n";
    $destPath = PathHelper::concatPaths($DestinationDir, $destFileName);
    $generatedFiles[] = $destPath;
    file_put_contents($destPath, $modelClassStr);
}
LaravelServer::dispose();

$delete = array_diff(
    array_map(/**
     * @throws Exception
     */ fn($path)=>PathHelper::parsePath($path),PathHelper::getAllEntriesInDir($DestinationDir)),
    array_map(/**
     * @throws Exception
     */ fn($path)=>PathHelper::parsePath($path),$generatedFiles)
);
File::delete($delete);





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
