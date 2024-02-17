<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Console\Kernel;
use App\Exceptions\InternalException;
use App\Utils\ValidateUtils;
use Illuminate\Support\Str;
use  Dev\DtoGen\PhpDtosGenerator;
use  Dev\DtoGen\MyFileInfo;
use Dev\DtoGen\PathHelper;
use Dev\DtoGen\StrUtils;
use Dev\Utils\ScriptArgsBuilder;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Illuminate\Support\Arr;


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
        $ModelsDir = parsePath($value, real: true);
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
    $obj = new SplFileObject($realPath);;
    $continue = false;
    while (true) {
        $line = $obj->getCurrentLine();
        if (!is_string($line) || $obj->eof()){
            echo "SKIPPING DUE TO MISSING namespace App\\Models\n";
            continue 2;
        } 
        $line = trim($line);
        if (preg_match(<<<'EOF'
        /^namespace\s*App\\Models\s*[;{]$/
        EOF, $line)) {
            $continue = true;
            break;
        }
    }
    if (!$continue){
        echo "SKIPPING DUE TO MISSING namespace App\\Models\n";
        continue;
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
        array_push($columns, Schema::getColumnListing($instance->getTable()));
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
   $namespace = parsePath($DestinationDir);
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
    echo "Genarating '$destFileName'\n";
    $destPath = PathHelper::concatPaths($DestinationDir, $destFileName);
    $generatedFiles[] = $destPath;
    file_put_contents($destPath, $modelClassStr);
}
LaravelServer::dispose();

$delete = array_diff(
    array_map(fn($path)=>parsePath($path),PathHelper::getAllEntriesInDir($DestinationDir)),
    array_map(fn($path)=>parsePath($path),$generatedFiles)
);
File::delete($delete);

class LaravelServer
{
    private static ?Kernel $kernel = null;
    private static int $status = 0;
    private static ?\Symfony\Component\Console\Input\ArgvInput $input = null;

    public static function execute(callable $call)
    {
        $kernel = self::getKernel();
        $call();
    }

    public static function dispose()
    {
        self::getKernel()->terminate(self::$input, self::$status);
    }

    private static function getKernel()
    {
        if (!self::$kernel) {
            $app = require_once __DIR__ . '/bootstrap/app.php';

            /*
|--------------------------------------------------------------------------
| Run The Artisan Application
|--------------------------------------------------------------------------
|
| When we run the console application, the current CLI command will be
| executed in this console and the response sent back to a terminal
| or another output device for the developers. Here goes nothing!
|
*/

            /**
             * @var Illuminate\Contracts\Console\Kernel $kernel
             */
            self::$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

            self::$status = self::$kernel->handle(
                self::$input = new Symfony\Component\Console\Input\ArgvInput,
                new Symfony\Component\Console\Output\ConsoleOutput
            );
        }
        return self::$kernel;
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

function SetSeparatorsTo(string $path, string $sep = DIRECTORY_SEPARATOR)
{
    return Str::replace(['/', '\\'], $sep, $path);
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

function filesChange(\Symfony\Component\Finder\Finder $finder, int $lastTime): bool
{
    foreach ($finder as $file) {
        $time = $file->getMTime();
        if ($time === false || $time < $lastTime) {
            return true;
        }
    }
    return false;
}
