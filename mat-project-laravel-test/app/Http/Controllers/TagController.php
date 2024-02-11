<?php

namespace App\Http\Controllers;

use App\Dtos\Defs\Types\Response\ResponseEnumElement;
use App\Models\Tags;
use App\Http\Requests\StoreTagsRequest;
use App\Http\Requests\UpdateTagsRequest;
use App\Models\Tag;
use App\Dtos\Tags\All\Response;
use App\Helpers\RequestHelper;
use App\Helpers\ResponseHelper;
use App\Utils\Utils;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\DB;

class TagController extends Controller
{
    public static function construct():static{
        return new static();
    }
    /**
     * Get all tags
     */
    public function getAll(HttpRequest $request)
    {
        $tagIdName = Tag::getPrimaryKeyName();
       $tags = DB::table(Tag::getTableName())
        ->select([$tagIdName,Tag::NAME])
        ->get();

       $response = Response::create()
        ->setTags(
            $tags->map(fn($tag)=>ResponseEnumElement::create()
        ->setName(DBHelper::access($tag,Tag::NAME))
        ->setId(ResponseHelper::translateIdForUser(DBHelper::access($tag,$tagIdName)))
        )->all()
        );

        return $response;
    }
}
