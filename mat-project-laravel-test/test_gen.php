<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Str;
use  Dev\DtoGen\PhpDtosGenerator;
use  Dev\DtoGen\MyFileInfo;
use Dev\DtoGen\PathHelper;
use Dev\DtoGen\StrUtils;
use Dev\Utils\ScriptArgsBuilder;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use App\Dtos\TaskInfo\Create\Request;
use App\Dtos\Errors\XML\InvalidAttribute;

$request = Request::create()
    ->setData(
        Request\Data::create()
            ->setTask(
                Request\DataTask::create()
                    ->setName("TaskInfo name")
                    ->setDescription("TaskInfo description")
                    ->setOrientation('Horizontal')
                    ->setSource('TaskInfo source')
            )
    );

echo "\n------JSON_REQUEST-----\n",
json_encode(
    Request::export($request),
    JSON_PRETTY_PRINT
);

$error = InvalidAttribute\InvalidAttribute::create()
    ->setError(
        InvalidAttribute\XMLInvalidAttribute::create()
            ->setErrorData(
                InvalidAttribute\ErrorAllOf1ErrorData::create()
                    ->setEByteIndex(23)
                    ->setEColumn(2)
                    ->setELine(5)
                    ->setExpectedAttributes([
                        'name',
                        'description',
                        'orientation'
                    ])
            )
    );

echo "JSON_ERROR",
json_encode(
    InvalidAttribute\InvalidAttribute::export($error),
    JSON_PRETTY_PRINT
);
