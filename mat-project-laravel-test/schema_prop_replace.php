<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Utils\StrUtils as UtilsStrUtils;
use App\Utils\Utils;
use Illuminate\Support\Str;
use  Dev\DtoGen\MyFileInfo;
use Dev\DtoGen\PathHelper;
use  Dev\DtoGen\StarPattern;
use Dev\DtoGen\StrUtils;
use Dev\Utils\ScriptArgsBuilder;
use SplFileInfo as GlobalSplFileInfo;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

$arguments = [];
echo "\n\n-------", MyFileInfo::omitAllExtensions(MyFileInfo::filename(__FILE__)), "-------\n";
if (ScriptArgsBuilder::create()
    ->fetchScriptArguments()
    ->getArguments($arguments)
    ->showPassedOptions()
    ->showInvalidOptions()
    ->helpRequested()
) return;

$filePath = PathHelper::parsePath($arguments[0]);
if (PathHelper::isRelative($filePath)) {
    $filePath = PathHelper::concatPaths(__DIR__, $filePath);
}
$replacerFile = json_decode(file_get_contents($filePath), associative: true);
dump($replacerFile);
$filePath = PathHelper::getPotentialyNonExistentAbsolutePath($filePath);
/**
 * @var array{mixed,string,string} $stack
 * [$json, $path, $posfix]
 */
$stack = [[$replacerFile, MyFileInfo::dirname($filePath),""]];
while ($stack) {
    $nextPosfix = "";
    [$json, $path,$posfix] = Utils::arrayShift($stack);
    echo "PATH: '$path'\n";
    echo "POSFIX: '$posfix'\n";
    foreach ($json as $prop => $value) {
        if (Str::startsWith($prop, '$')) {
            if ($prop === '$posfix') {
                $nextPosfix = $value;
            }
        } else if (!is_string($value)) {
            $newPathSegment = PathHelper::parsePath($prop);
            $newPath = PathHelper::getPotentialyNonExistentAbsolutePath(
                PathHelper::concatPaths($path, $newPathSegment)
            );
            $stack[] = [$value, $newPath,$posfix.$nextPosfix];
        } else {
            $valueWPosfix = PathHelper::parsePath($value.$posfix);
            [$file, $filePropPath] = explode("#", $valueWPosfix, limit: 2);
            $file = PathHelper::concatPaths($path, $file);
            $currentFile = json_decode(file_get_contents($file), associative: true);
            $newValue = json_decode($prop) ?? $prop;
            echo "  SET '$value' TO '$newValue'\n";
            replaceValueAtJsonPath(
                json: $currentFile,
                path: explode(DIRECTORY_SEPARATOR, $filePropPath),
                value: $newValue
            );
            file_put_contents($file, json_encode($currentFile, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
    }
}


function replaceValueAtJsonPath(array &$json, array $path, array|int|string|bool $value): void
{
    $current = &$json;
    foreach ($path as $part) {
        $current = &$current[$part];
    }
    $current = $value;
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
