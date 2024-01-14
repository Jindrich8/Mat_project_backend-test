<?php
namespace App{

    use Illuminate\Support\Str;

    require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/Helpers/CreateTask/ParseEntry.php';
echo Str::snake("FillInBlanks");
}
