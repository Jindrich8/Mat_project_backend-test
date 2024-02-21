<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Utils\Utils;
use App\Dtos\Defs\Endpoints\CreateInfo;
use App\Dtos\Defs\Types\Response\ResponseEnumElement;
use App\Dtos\Defs\Types\Response\ResponseOrderedEnumElement;
use App\ModelConstants\TagConstants;
use App\TableSpecificData\TaskDifficulty;
use App\Types\ConstructableTrait;
use App\TableSpecificData\TaskClass;

class TaskCreateInfoController extends Controller
{
    use ConstructableTrait;

    public function getCreateInfo(Request $request): CreateInfo\CreateInfoResponse
    {
        $difficulties = TaskDifficulty::getValues();
        $classes = TaskClass::getValues();

        $response = CreateInfo\CreateInfoResponse::create()
            ->setTags(
                DB::table(TagConstants::TABLE_NAME)
                    ->pluck(TagConstants::COL_NAME, TagConstants::COL_ID)
                    ->map(
                        fn ($name, $id) =>
                        ResponseEnumElement::create()
                            ->setName($name)
                            ->setId($id)
                    )
            )
            ->setDifficulties(
                Utils::arrayMapWKey(fn ($index, $name) => [
                    $index,
                    ResponseOrderedEnumElement::create()
                        ->setName($name)
                        ->setOrderedId($index)
                ], $difficulties)
            )
            ->setClasses(
                Utils::arrayMapWKey(fn ($index, $name) => [
                    $index,
                    ResponseOrderedEnumElement::create()
                        ->setName($name)
                        ->setOrderedId($index)
                ], $classes)
            );

        return $response;
    }
}
