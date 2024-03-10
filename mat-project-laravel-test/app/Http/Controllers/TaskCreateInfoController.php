<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Dtos\Defs\Endpoints\CreateInfo;
use App\Dtos\Defs\Types\Response\ResponseEnumElement;
use App\Helpers\ResponseHelper;
use App\ModelConstants\TagConstants;
use App\TableSpecificData\TaskDifficulty;
use App\TableSpecificData\TaskClass;
use App\Utils\DebugUtils;
use App\Utils\DtoUtils;

class TaskCreateInfoController extends Controller
{

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
                            ->setId(ResponseHelper::translateIdForUser($id))
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
        DebugUtils::log("getCreateInfo: ", ['tags' => var_export($response->tags, true)]);
        return $response;
    }
}
