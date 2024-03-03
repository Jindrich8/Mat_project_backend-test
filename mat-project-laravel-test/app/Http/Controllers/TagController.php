<?php

namespace App\Http\Controllers;

use App\Dtos\Defs\Types\Response\ResponseEnumElement;
use App\Dtos\Defs\Endpoints\Tags\All\GetAllTagsResponse;
use App\Helpers\Database\DBHelper;
use App\Helpers\ResponseHelper;
use App\ModelConstants\TagConstants;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\DB;

class TagController extends Controller
{
    /**
     * Get all tags
     */
    public function getAll(HttpRequest $request): GetAllTagsResponse
    {
        $tagIdName = TagConstants::COL_ID;
       $tags = DB::table(TagConstants::TABLE_NAME)
        ->select([$tagIdName,TagConstants::COL_NAME])
        ->get();

        return GetAllTagsResponse::create()
         ->setTags(
             $tags->map(fn($tag)=>ResponseEnumElement::create()
         ->setName(DBHelper::access($tag,TagConstants::COL_NAME))
         ->setId(ResponseHelper::translateIdForUser(DBHelper::access($tag,$tagIdName)))
         )->all()
         );
    }
}
