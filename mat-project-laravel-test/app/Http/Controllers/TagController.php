<?php

namespace App\Http\Controllers;

use App\Models\Tags;
use App\Http\Requests\StoreTagsRequest;
use App\Http\Requests\UpdateTagsRequest;
use App\Models\Tag;
use App\Dtos\Tags\All\Response;
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

       $response = Response\Response::create()
        ->setTags(
            $tags->map(fn($tag)=>Response\Tag::create()
        ->setName($tag[Tag::NAME])
        ->setId($tag[$tagIdName])
        )
        );

        return $response;
    }
}
