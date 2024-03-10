<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Dtos\Task\Create\Request;
use App\Dtos\Errors\XML\InvalidAttribute;

$request = Request::create()
    ->setData(
        Request\Data::create()
            ->setTask(
                Request\DataTask::create()
                    ->setName("Task name")
                    ->setDescription("Task description")
                    ->setOrientation('Horizontal')
                    ->setSource('Task source')
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
