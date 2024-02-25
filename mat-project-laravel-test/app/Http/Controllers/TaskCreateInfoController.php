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
use App\Utils\DtoUtils;
use Illuminate\Support\Facades\Log;

class TaskCreateInfoController extends Controller
{
    use ConstructableTrait;

    public function getCreateInfo(Request $request): CreateInfo\CreateInfoResponse
    {
        $response = CreateInfo\CreateInfoResponse::create()
            ->setTags(
                array_values(DB::table(TagConstants::TABLE_NAME)
                    ->pluck(TagConstants::COL_NAME, TagConstants::COL_ID)
                    ->map(
                        fn ($name, $id) =>
                        ResponseEnumElement::create()
                            ->setName($name)
                            ->setId($id)
                    )->all())
            )
            ->setDifficulties(
                array_map(
                    fn (TaskDifficulty $case) => DtoUtils::createOrderedEnumDto($case),
                    TaskDifficulty::cases()
                )
            )
            ->setClasses(
                array_map(
                    fn (TaskClass $case) => DtoUtils::createOrderedEnumDto($case),
                    TaskClass::cases()
                )
            );
        Log::info("getCreateInfo: ", ['tags' => var_export($response->tags, true)]);
        return $response;
    }
}
