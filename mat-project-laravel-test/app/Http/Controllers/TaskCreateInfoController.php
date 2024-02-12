<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Dtos\Task as TaskDto;
use App\Dtos\Task\Create;
use App\Dtos\Task\Evaluate;
use App\Dtos\Task\Review;
use App\Helpers\CreateTask\ParseEntry;
use App\Helpers\CreateTask\TaskRes;
use Illuminate\Http\Request as HttpRequest;
use App\Dtos\Task\Take;
use App\Exceptions\InternalException;
use App\Exceptions\UnsupportedVariantException;
use App\Helpers\RequestHelper;
use App\TableSpecificData\TaskDisplay;
use App\Helpers\ExerciseHelper;
use Illuminate\Support\Facades\DB;
use App\Models\Exercise;
use App\Models\Group;
use App\Models\Resource;
use App\Utils\DebugUtils;
use App\Utils\Utils;
use Carbon\Carbon;
use DateTime;
use DateTimeZone;
use Swaggest\JsonSchema\Structure\ClassStructure;
use App\Dtos\CreateInfo\Response;
use App\Dtos\CreateInfo\Response\Tag as ResponseTag;
use App\Models\Tag;
use App\TableSpecificData\TaskDifficulty;
use App\Types\ConstructableTrait;
use App\TableSpecificData\TaskClass;

class TaskCreateInfoController extends Controller
{
    use ConstructableTrait;

    public function getCreateInfo(): Response\Response
    {
        $response = Response\Response::create()
            ->setTags(
                DB::table(TagConstants::TABLE_NAME)
                    ->pluck(TagConstants::COL_NAME, Tag::getPrimaryKeyName())
                    ->map(
                        fn ($name, $id) =>
                        Response\ResponseEnumElement::create()
                            ->setName($name)
                            ->setId($id)
                    )
            )
            ->setDifficulties(
                Utils::arrayMapWKey(fn ($index, $name) => [
                    $index,
                    Response\ResponseOrderedEnumElement::create()
                        ->setName($name)
                        ->setOrderedId($index)
                ], TaskDifficulty::getValues())
            )
            ->setClasses(
                Utils::arrayMapWKey(fn ($index, $name) => [
                    $index,
                    Response\ResponseOrderedEnumElement::create()
                        ->setName($name)
                        ->setOrderedId($index)
                ], TaskClass::getValues())
            );

        return $response;
    }
}
