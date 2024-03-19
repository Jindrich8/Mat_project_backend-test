<?php

namespace App\Http\Controllers;

use App\Dtos\Defs\Endpoints\Task\Evaluate\EvaluateResponseTask;
use App\Dtos\Defs\Endpoints\Task\Review\List\ListTaskReviewsResponse;
use App\Dtos\Defs\Types\Errors\EnumArrayError;
use App\Dtos\Defs\Types\Errors\UserSpecificPartOfAnError;
use App\Dtos\Defs\Types\Response\ResponseEnumElement;
use App\Dtos\Defs\Types\Response\ResponseOrderedEnumRange;
use App\Dtos\Defs\Types\Review\AuthorInfo;
use App\Dtos\Defs\Types\Review\ExercisePoints;
use App\Dtos\Defs\Types\Review\ExerciseReview;
use App\Dtos\Defs\Types\Review\ReviewTaskDetailInfo;
use App\Dtos\Defs\Types\Review\ReviewTaskPreviewInfo;
use App\Dtos\Errors\ApplicationErrorInformation;
use App\Dtos\InternalTypes\TaskReviewExercisesContent;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use App\Dtos\Defs\Endpoints\Task\Review;
use App\Dtos\Defs\Types\ListConfig;
use App\Exceptions\ApplicationException;
use App\Exceptions\AppModelNotFoundException;
use App\Exceptions\UnsupportedVariantException;
use App\Helpers\Database\DBHelper;
use App\Helpers\Database\UserHelper;
use App\Helpers\RequestHelper;
use App\Helpers\ResponseHelper;
use App\Helpers\TaskHelper;
use App\ModelConstants\TaskConstants;
use App\ModelConstants\TaskInfoConstants;
use App\ModelConstants\TaskReviewConstants;
use App\ModelConstants\TaskReviewTemplateConstants;
use App\TableSpecificData\TaskClass;
use App\TableSpecificData\TaskDifficulty;
use App\TableSpecificData\TaskDisplay;
use App\Utils\DtoUtils;
use App\Utils\TimeStampUtils;
use App\Utils\Utils;
use App\Utils\ValidateUtils;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use \App\Dtos\Defs\Endpoints\Task\Review\List as ReviewList;
use Log;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Throwable;

class TaskReviewController extends Controller
{

    /**
     * @throws AppModelNotFoundException
     * @throws AuthenticationException
     */
    public function get(Request $request, int $id): Review\Get\ReviewTaskResponse
    {
        $userId = UserHelper::getUserId();
        $taskReviewTable = TaskReviewConstants::TABLE_NAME;
        $taskReviewTemplateTable = TaskReviewTemplateConstants::TABLE_NAME;
        $taskInfoTable = TaskInfoConstants::TABLE_NAME;

        $review = DB::table($taskReviewTable)
            ->select([
                DBHelper::colFromTableAsCol($taskReviewTable, TaskReviewConstants::COL_EXERCISES),
                DBHelper::colFromTableAsCol($taskReviewTable, TaskReviewConstants::COL_EVALUATED_AT),
                DBHelper::colFromTableAsCol($taskReviewTable, TaskReviewConstants::COL_ID),
                DBHelper::colFromTableAsCol($taskReviewTable, TaskReviewConstants::COL_MAX_POINTS),
                DBHelper::colFromTableAsCol($taskReviewTable, TaskReviewConstants::COL_SCORE),
                DBHelper::colFromTableAsCol($taskReviewTable, TaskReviewConstants::COL_TASK_REVIEW_TEMPLATE_ID),
                DBHelper::colFromTableAsCol($taskReviewTemplateTable, TaskReviewTemplateConstants::COL_TASK_ID),
                DBHelper::colFromTableAsCol($taskReviewTemplateTable, TaskReviewTemplateConstants::COL_TASK_INFO_ID),
                DBHelper::colFromTableAsCol($taskReviewTemplateTable, TaskReviewTemplateConstants::COL_TASK_NAME),
                DBHelper::colFromTableAsCol($taskInfoTable, TaskInfoConstants::COL_TASK_SOURCE_ID),
                DBHelper::colFromTableAsCol($taskInfoTable, TaskInfoConstants::COL_DIFFICULTY),
                DBHelper::colFromTableAsCol($taskInfoTable, TaskInfoConstants::COL_DESCRIPTION),
                DBHelper::colFromTableAsCol($taskInfoTable, TaskInfoConstants::COL_ORIENTATION),
                DBHelper::colFromTableAsCol($taskInfoTable, TaskInfoConstants::COL_MIN_CLASS),
                DBHelper::colFromTableAsCol($taskInfoTable, TaskInfoConstants::COL_MAX_CLASS)
            ])
            ->join(
                $taskReviewTemplateTable,
                DBHelper::tableCol($taskReviewTemplateTable, TaskReviewTemplateConstants::COL_ID),
                '=',
                DBHelper::tableCol($taskReviewTable, TaskReviewConstants::COL_TASK_REVIEW_TEMPLATE_ID)
            )
            ->join(
                $taskInfoTable,
                DBHelper::tableCol($taskInfoTable, TaskInfoConstants::COL_ID),
                '=',
                DBHelper::tableCol($taskReviewTemplateTable, TaskReviewTemplateConstants::COL_TASK_INFO_ID)
            )
            ->where(
                DBHelper::tableCol($taskReviewTable, TaskReviewConstants::COL_ID),
                '=',
                $id
            )
            ->where(
                DBHelper::tableCol($taskReviewTable, TaskReviewConstants::COL_USER_ID),
                '=',
                $userId
            )
            ->first()
            ?? throw new AppModelNotFoundException('Review', ['id' => $id]);
        /**
         * @var int $taskInfoId
         */
        $taskInfoId = DBHelper::access($review, TaskReviewTemplateConstants::COL_TASK_INFO_ID);
        /**
         * @var int $taskSourceId
         */
        $taskSourceId = DBHelper::access($review, TaskInfoConstants::COL_TASK_SOURCE_ID);

        $exercises = DtoUtils::importDto(
            dto: TaskReviewExercisesContent::class,
            json: DBHelper::access($review, TaskReviewConstants::COL_EXERCISES),
            table: $taskReviewTable,
            column: TaskReviewConstants::COL_EXERCISES,
            id: $id,
            wrapper: TaskReviewExercisesContent::CONTENT
        );

        $score = ValidateUtils::validateFloat(
            DBHelper::access($review, TaskReviewConstants::COL_SCORE)
        );
        $maxPoints = ValidateUtils::validateFloat(
            DBHelper::access($review, TaskReviewConstants::COL_MAX_POINTS)
        );
        $evaluationTimestamp = Carbon::parse(
            DBHelper::access($review, TaskReviewConstants::COL_EVALUATED_AT),
            'UTC'
        );

        $orientation = TaskDisplay::fromThrow(DBHelper::access($review, TaskInfoConstants::COL_ORIENTATION));
        $responseTask = EvaluateResponseTask::create()
            ->setId(
                ResponseHelper::translateIdForUser(
                    DBHelper::access($review, TaskReviewConstants::COL_ID)
                )
            )
            ->setName(DBHelper::access($review, TaskReviewTemplateConstants::COL_TASK_NAME))
            ->setDisplay(match ($orientation) {
                TaskDisplay::HORIZONTAL => EvaluateResponseTask::HORIZONTAL,
                TaskDisplay::VERTICAL => EvaluateResponseTask::VERTICAL,
                default => throw new UnsupportedVariantException($orientation)
            })
            ->setDescription(DBHelper::access($review, TaskInfoConstants::COL_DESCRIPTION))
            ->setPoints(
                ExercisePoints::create()
                    ->setHas($maxPoints * $score)
                    ->setMax($maxPoints)
            )
            ->setEvaluationTimestamp(TimeStampUtils::timestampToString($evaluationTimestamp));

        $taskEntries = &$responseTask->setEntries([])->entries;
        TaskHelper::getTaskEntries(
            taskSourceId: $taskSourceId,
            exercises: $exercises->content,
            entries: $taskEntries,
            groupToDto: function (array $resources) {
                $groupDto = Review\Get\DefsGroup::create()
                    ->setResources(
                        array_map(
                            fn (string $resource) =>
                            Review\Get\DefsGroupResourcesItems::create()
                                ->setContent($resource),
                            $resources
                        )
                    );
                return $groupDto;
            },
            exerciseToDto: fn (ExerciseReview $exercise) => $exercise
        );
        return Review\Get\ReviewTaskResponse::create()
            ->setTask($responseTask);
    }

    /**
     * @throws AuthenticationException
     */
    public function detail(Request $request, int $id): Review\Detail\TaskReviewDetailResponse
    {
        $userId = UserHelper::getUserId();
        $taskReviewTable = TaskReviewConstants::TABLE_NAME;
        $taskReviewTemplateTable = TaskReviewTemplateConstants::TABLE_NAME;
        $taskInfoTable = TaskInfoConstants::TABLE_NAME;
        $taskTable = TaskConstants::TABLE_NAME;
        $review = DB::table($taskReviewTable)
            ->select([
                DBHelper::colFromTableAsCol($taskReviewTable, TaskReviewConstants::COL_ID),
                DBHelper::colFromTableAsCol($taskReviewTable, TaskReviewConstants::COL_CREATED_AT),
                DBHelper::colFromTableAsCol($taskReviewTable, TaskReviewConstants::COL_MAX_POINTS),
                DBHelper::colFromTableAsCol($taskReviewTable, TaskReviewConstants::COL_SCORE),
                DBHelper::colFromTableAsCol($taskReviewTable, TaskReviewConstants::COL_EVALUATED_AT),
                DBHelper::colFromTableAsCol($taskReviewTable, TaskReviewConstants::COL_TASK_REVIEW_TEMPLATE_ID),

                DBHelper::colFromTableAsCol($taskReviewTemplateTable, TaskReviewTemplateConstants::COL_TASK_ID),
                DBHelper::colFromTableAsCol($taskReviewTemplateTable, TaskReviewTemplateConstants::COL_TASK_INFO_ID),
                DBHelper::colFromTableAsCol($taskReviewTemplateTable, TaskReviewTemplateConstants::COL_AUTHOR_ID),
                DBHelper::colFromTableAsCol($taskReviewTemplateTable, TaskReviewTemplateConstants::COL_AUTHOR_NAME),
                DBHelper::colFromTableAsCol($taskReviewTemplateTable, TaskReviewTemplateConstants::COL_TASK_NAME),

                DBHelper::colFromTableAsCol($taskInfoTable, TaskInfoConstants::COL_DIFFICULTY),
                DBHelper::colFromTableAsCol($taskInfoTable, TaskInfoConstants::COL_DESCRIPTION),
                DBHelper::colFromTableAsCol($taskInfoTable, TaskInfoConstants::COL_ORIENTATION),
                DBHelper::colFromTableAsCol($taskInfoTable, TaskInfoConstants::COL_MIN_CLASS),
                DBHelper::colFromTableAsCol($taskInfoTable, TaskInfoConstants::COL_MAX_CLASS),
            ])
            ->join(
                $taskReviewTemplateTable,
                DBHelper::tableCol($taskReviewTemplateTable, TaskReviewTemplateConstants::COL_ID),
                '=',
                DBHelper::tableCol($taskReviewTable, TaskReviewConstants::COL_TASK_REVIEW_TEMPLATE_ID)
            )
            ->join(
                $taskInfoTable,
                DBHelper::tableCol($taskInfoTable, TaskInfoConstants::COL_ID),
                '=',
                DBHelper::tableCol($taskReviewTemplateTable, TaskReviewTemplateConstants::COL_TASK_INFO_ID)
            )
            ->where(
                DBHelper::tableCol($taskReviewTable, TaskReviewConstants::COL_ID),
                '=',
                $id
            )
            ->where(
                DBHelper::tableCol($taskReviewTable, TaskReviewConstants::COL_USER_ID),
                '=',
                $userId
            )
            ->first();

        $taskInfoId = DBHelper::access($review, TaskReviewTemplateConstants::COL_TASK_INFO_ID);

        $tags = TaskHelper::getTaskInfoTags($taskInfoId)
            ->map(
                fn ($tagName, $tagId) =>
                ResponseEnumElement::create()
                    ->setId(ResponseHelper::translateIdForUser($tagId))
                    ->setName($tagName)
            );


        $taskHasChanged = !DB::table($taskTable)
            ->where(TaskConstants::COL_TASK_INFO_ID, '=', $taskInfoId)
            ->exists();

        $author = AuthorInfo::create()
            ->setName(DBHelper::access($review, TaskReviewTemplateConstants::COL_AUTHOR_NAME));
        $authorId = DBHelper::access($review, TaskReviewTemplateConstants::COL_AUTHOR_ID);
        if ($authorId !== null) {
            $author->setId(ResponseHelper::translateIdForUser($authorId));
        }



        $maxPoints = (float)DBHelper::access($review, TaskReviewConstants::COL_MAX_POINTS);
        $score = (float)DBHelper::access($review, TaskReviewConstants::COL_SCORE);

        $response = Review\Detail\TaskReviewDetailResponse::create()
            ->setId(ResponseHelper::translateIdForUser($id))
            ->setPoints(
                ExercisePoints::create()
                    ->setMax($maxPoints)
                    ->setHas($score * $maxPoints)
            )
            ->setEvaluationTimestamp(TimeStampUtils::parseIsoTimestampToUtc(
                TimeStampUtils::timestampToString(
                    TimeStampUtils::parseIsoTimestampToUtc(
                        DBHelper::access($review, TaskReviewConstants::COL_EVALUATED_AT)
                    )
                )
            ))
            ->setTaskHasChanged($taskHasChanged)
            ->setTaskDetail(
                ReviewTaskDetailInfo::create()
                    ->setId(
                        ResponseHelper::translateIdForUser(
                            DBHelper::access($review, TaskReviewTemplateConstants::COL_TASK_ID)
                        )
                    )
                    ->setName(DBHelper::access($review, TaskReviewTemplateConstants::COL_TASK_NAME))
                    ->setDescription(DBHelper::access($review, TaskInfoConstants::COL_DESCRIPTION))
                    ->setDifficulty(
                        DtoUtils::accessAsOrderedEnumDto(
                            record: $review,
                            prop: TaskInfoConstants::COL_DIFFICULTY,
                            enum: TaskDifficulty::class
                        )
                    )
                    ->setTags(array_values($tags->all()))
                    ->setAuthor($author)
                    ->setClassRange(
                        ResponseOrderedEnumRange::create()
                            ->setMin(
                                DtoUtils::accessAsOrderedEnumDto(
                                    record: $review,
                                    prop: TaskInfoConstants::COL_MIN_CLASS,
                                    enum: TaskClass::class
                                )
                            )
                            ->setMax(
                                DtoUtils::accessAsOrderedEnumDto(
                                    record: $review,
                                    prop: TaskInfoConstants::COL_MAX_CLASS,
                                    enum: TaskClass::class
                                )
                            )
                    )
            );
        return $response;
    }


    /**
     * @param Request $request
     * @return ListTaskReviewsResponse
     * @throws ApplicationException
     * @throws AuthenticationException
     * @throws ValidationException
     */
    public function list(Request $request): Review\List\ListTaskReviewsResponse
    {
        $userId = UserHelper::getUserId();
        $requestData = RequestHelper::getDtoFromRequest(Review\List\ListTaskReviewsRequest::class, $request);
        $config = ListConfig::create();

        $userId = UserHelper::getUserId();
        $taskReviewTable = TaskReviewConstants::TABLE_NAME;
        $taskReviewTemplateTable = TaskReviewTemplateConstants::TABLE_NAME;
        $taskInfoTable = TaskInfoConstants::TABLE_NAME;
        $builder = DB::table($taskReviewTable)
            ->select([
                DBHelper::colFromTableAsCol($taskReviewTable, TaskReviewConstants::COL_ID),
                DBHelper::colFromTableAsCol($taskReviewTable, TaskReviewConstants::COL_CREATED_AT),
                DBHelper::colFromTableAsCol($taskReviewTable, TaskReviewConstants::COL_MAX_POINTS),
                DBHelper::colFromTableAsCol($taskReviewTable, TaskReviewConstants::COL_SCORE),
                DBHelper::colFromTableAsCol($taskReviewTable, TaskReviewConstants::COL_EVALUATED_AT),
                DBHelper::colFromTableAsCol($taskReviewTable, TaskReviewConstants::COL_TASK_REVIEW_TEMPLATE_ID),

                DBHelper::colFromTableAsCol($taskReviewTemplateTable, TaskReviewTemplateConstants::COL_TASK_ID),
                DBHelper::colFromTableAsCol($taskReviewTemplateTable, TaskReviewTemplateConstants::COL_TASK_INFO_ID),
                DBHelper::colFromTableAsCol($taskReviewTemplateTable, TaskReviewTemplateConstants::COL_AUTHOR_ID),
                DBHelper::colFromTableAsCol($taskReviewTemplateTable, TaskReviewTemplateConstants::COL_AUTHOR_NAME),
                DBHelper::colFromTableAsCol($taskReviewTemplateTable, TaskReviewTemplateConstants::COL_TASK_NAME),

                DBHelper::colFromTableAsCol($taskInfoTable, TaskInfoConstants::COL_DIFFICULTY),
                DBHelper::colFromTableAsCol($taskInfoTable, TaskInfoConstants::COL_DESCRIPTION),
                DBHelper::colFromTableAsCol($taskInfoTable, TaskInfoConstants::COL_ORIENTATION),
                DBHelper::colFromTableAsCol($taskInfoTable, TaskInfoConstants::COL_MIN_CLASS),
                DBHelper::colFromTableAsCol($taskInfoTable, TaskInfoConstants::COL_MAX_CLASS),
            ])
            ->join(
                $taskReviewTemplateTable,
                DBHelper::tableCol($taskReviewTemplateTable, TaskReviewTemplateConstants::COL_ID),
                '=',
                DBHelper::tableCol($taskReviewTable, TaskReviewConstants::COL_TASK_REVIEW_TEMPLATE_ID)
            )
            ->join(
                $taskInfoTable,
                DBHelper::tableCol($taskInfoTable, TaskInfoConstants::COL_ID),
                '=',
                DBHelper::tableCol($taskReviewTemplateTable, TaskReviewTemplateConstants::COL_TASK_INFO_ID)
            )
            ->where(
                DBHelper::tableCol($taskReviewTable, TaskReviewConstants::COL_USER_ID),
                '=',
                $userId
            );

        $filters = $requestData->filters;
        if ($filters) {
            $filterErrorData = null;
            if ($filters->tags) {
                $invalidTags = TaskHelper::filterTaskByTags(
                    $filters->tags,
                    $builder,
                    taskInfoIdColName: DBHelper::tableCol(
                        $taskReviewTemplateTable,
                        TaskReviewTemplateConstants::COL_TASK_INFO_ID
                    )
                );
                if ($invalidTags) {
                    ($filterErrorData ??= Review\List\Errors\FilterErrorDetailsErrorData::create())
                        ->setTags(
                            EnumArrayError::create()
                                ->setMessage("Invalid ids specified.")
                        );
                }
            }

            if ($filters->name) {
                $builder->whereRaw(
                    DBHelper::tableCol(
                        $taskReviewTemplateTable,
                        TaskReviewTemplateConstants::COL_TASK_NAME
                    )
                        . " LIKE ?",
                    ["%{$filters->name}%"]
                );
            }

            if ($filters->difficultyRange) {
                $RangeError = TaskHelper::filterTaskInfoByDifficultyRange(
                    min: $filters->difficultyRange->min,
                    max: $filters->difficultyRange->max,
                    builder: $builder,
                    withPrefix: true
                );
                if ($RangeError) {
                    ($filterErrorData ??= Review\List\Errors\FilterErrorDetailsErrorData::create())
                        ->setDifficultyRange($RangeError);
                }
            }

            if ($filters->classRange) {
                $RangeError = TaskHelper::filterTaskInfoByClassRange(
                    min: $filters->classRange->min,
                    max: $filters->classRange->max,
                    builder: $builder,
                    withPrefix: true
                );
                if ($RangeError) {
                    ($filterErrorData ??= Review\List\Errors\FilterErrorDetailsErrorData::create())
                        ->setDifficultyRange($RangeError);
                }
            }

            if (($evaluationRange = $filters->evaluationTimestampRange)) {
                $rangeError = TaskHelper::filterByTimestampColumn(
                    $evaluationRange,
                    $builder,
                    column: DBHelper::tableCol($taskReviewTable, TaskReviewConstants::COL_EVALUATED_AT)
                );
                if ($rangeError) {
                    ($filterErrorData ??= Review\List\Errors\FilterErrorDetailsErrorData::create())
                        ->setEvaluationTimestampRange($rangeError);
                }
            }
            if (($scoreRange = $filters->scoreRange)) {
                $column =  DBHelper::tableCol($taskReviewTable, TaskReviewConstants::COL_SCORE);
                if (isset($scoreRange->min) && isset($scoreRange->max)) {
                    $builder->whereBetween(
                        $column,
                        [$scoreRange->min / 100, $scoreRange->max / 100]
                    );
                } else if (isset($scoreRange->min)) {
                    $builder->where($column, '>=', $scoreRange->min / 100);
                } else if (isset($scoreRange->max)) {
                    $builder->where($column, '<=', $scoreRange->max / 100);
                }
            }

            if ($filterErrorData) {
                $details = Review\List\Errors\FilterErrorDetails::create()
                    ->setErrorData($filterErrorData);
                throw new ApplicationException(
                    ResponseAlias::HTTP_BAD_REQUEST,
                    ApplicationErrorInformation::create()
                        ->setUserInfo(
                            UserSpecificPartOfAnError::create()
                                ->setMessage("Bad request.")
                                ->setDescription("Please correct request fields.")
                        )
                        ->setDetails(
                            $details
                        )
                );
            }
        }
        $transformOrderBy = function (array $orderBy) {
            /**
             * @var ReviewList\ListRequestOrderByItems[] $orderBy
             */
            foreach ($orderBy as $filterAndOrder) {
                yield $filterAndOrder->filterName =>
                    $filterAndOrder->type === ReviewList\ListRequestOrderByItems::DESC ? 'DESC' : 'ASC';
            }
        };

        if ($requestData->orderBy) {
            TaskHelper::distinctOrderBy(
                $transformOrderBy($requestData->orderBy),
                function (string $filterName, $direction) use ($builder) {
                    if ($filterName === ReviewList\ListRequestOrderByItems::CLASS_RANGE) {
                        $builder->orderBy(
                            DBHelper::tableCol(TaskInfoConstants::TABLE_NAME, TaskInfoConstants::COL_MIN_CLASS),
                            $direction
                        );
                        $builder->orderBy(
                            DBHelper::tableCol(TaskInfoConstants::TABLE_NAME, TaskInfoConstants::COL_MAX_CLASS),
                            $direction
                        );
                    } else {
                        if ($filterName === ReviewList\ListRequestOrderByItems::DIFFICULTY) {
                            $column = DBHelper::tableCol(TaskInfoConstants::TABLE_NAME, TaskInfoConstants::COL_DIFFICULTY);
                        } else if ($filterName === ReviewList\ListRequestOrderByItems::NAME) {
                            $column = DBHelper::tableCol(TaskReviewTemplateConstants::TABLE_NAME, TaskReviewTemplateConstants::COL_TASK_NAME);
                        } else if ($filterName === ReviewList\ListRequestOrderByItems::EVALUATION_TIMESTAMP) {
                            $column = DBHelper::tableCol(TaskReviewConstants::TABLE_NAME, TaskReviewConstants::COL_EVALUATED_AT);
                        } else if ($filterName === ReviewList\ListRequestOrderByItems::SCORE) {
                            $column = DBHelper::tableCol(TaskReviewConstants::TABLE_NAME, TaskReviewConstants::COL_SCORE);
                        } else {
                            return false;
                        }
                        $builder->orderBy($column, $direction);
                    }
                    return true;
                }
            );
        }
        Log::debug("TaskReviewController - list - Executed query: '" . $builder->toRawSql() . "");
        $paginator = $builder->orderBy(TaskConstants::COL_ID)
            ->cursorPaginate(
                perPage: $requestData->options->limit,
                cursor: $requestData->options->cursor
            );
        $nextCursor = $paginator->nextCursor();
        $prevCursor = $paginator->previousCursor();
        if ($nextCursor) {
            $config->setNextCursor($nextCursor->encode());
        }
        if ($prevCursor) {
            $config->setPrevCursor($prevCursor->encode());
        }

        $reviews = collect($paginator->items());


        $taskInfoIds = [];
        foreach ($reviews as $review) {
            $taskInfoId = DBHelper::access($review, TaskReviewTemplateConstants::COL_TASK_INFO_ID);
            $taskInfoIds[$taskInfoId] = true;
        }
        $taskInfoIds = array_keys($taskInfoIds);
        /**
         * @var array<int,ResponseEnumElement> $tags
         */
        $tagsByTaskInfoId = TaskHelper::getTagsByTaskInfoId($taskInfoIds);
        $tags = Utils::arrayMapWKey(
            /**
             * @param array{0: int, 1: string}[] $tags
             */
            fn (int $key, array $tags) => [
                $key,
                array_map(
                    fn ($tag) => ResponseEnumElement::create()
                        ->setId(ResponseHelper::translateIdForUser($tag[0]))
                        ->setName($tag[1]),
                    $tags
                )
            ],
            $tagsByTaskInfoId
        );
        unset($taskInfoIds);

        $reviews =  $reviews->map(function ($review) use ($tags) {
            $taskInfoId = DBHelper::access($review, TaskReviewTemplateConstants::COL_TASK_INFO_ID);
            $author = AuthorInfo::create()
                ->setName(DBHelper::access($review, TaskReviewTemplateConstants::COL_AUTHOR_NAME));
            $authorId = DBHelper::access($review, TaskReviewTemplateConstants::COL_AUTHOR_ID);
            if ($authorId !== null) {
                $author->setId(ResponseHelper::translateIdForUser($authorId));
            }
            $taskPreviewInfo = ReviewTaskPreviewInfo::create()
                ->setName(DBHelper::access($review,  TaskReviewTemplateConstants::COL_TASK_NAME))
                ->setDifficulty(
                    DtoUtils::accessAsOrderedEnumDto(
                        record: $review,
                        prop: TaskInfoConstants::COL_DIFFICULTY,
                        enum: TaskDifficulty::class
                    )
                )
                ->setTags($tags[$taskInfoId])
                ->setAuthor($author)
                ->setClassRange(
                    ResponseOrderedEnumRange::create()
                        ->setMin(
                            DtoUtils::accessAsOrderedEnumDto(
                                record: $review,
                                prop: TaskInfoConstants::COL_MIN_CLASS,
                                enum: TaskClass::class
                            )
                        )
                        ->setMax(
                            DtoUtils::accessAsOrderedEnumDto(
                                record: $review,
                                prop: TaskInfoConstants::COL_MAX_CLASS,
                                enum: TaskClass::class
                            )
                        )
                );

            $taskId = DBHelper::access($review, TaskReviewTemplateConstants::COL_TASK_ID);
            if ($taskId !== null) {
                $taskPreviewInfo->setId(
                    ResponseHelper::translateIdForUser($taskId)
                );
            }
            return Review\List\ListResponseReviewsItems::create()
                ->setId(
                    ResponseHelper::translateIdForUser(
                        DBHelper::access($review, TaskReviewConstants::COL_ID)
                    )
                )
                ->setScore(DBHelper::access($review, TaskReviewConstants::COL_SCORE))
                ->setEvaluationTimestamp(
                    TimeStampUtils::timestampToString(
                        TimeStampUtils::parseIsoTimestampToUtc(
                            DBHelper::access($review, TaskReviewConstants::COL_EVALUATED_AT)
                        )
                    )
                )
                ->setTaskPreviewInfo($taskPreviewInfo);
        });

        return Review\List\ListTaskReviewsResponse::create()
            ->setReviews($reviews->all())
            ->setConfig($config);
    }

    /**
     * @throws AppModelNotFoundException
     * @throws AuthenticationException
     */
    public function delete(Request $request, int $id): Response
    {
        $userId = UserHelper::getUserId();
        $response = response(status: Response::HTTP_NO_CONTENT);
        // Task review never changes nor task template review does

        // Get task review template id and task info id
        $templateId = null;
        $taskInfoId = null; {
            $taskReviewTable = TaskReviewConstants::TABLE_NAME;
            $taskReviewTemplateTable = TaskReviewTemplateConstants::TABLE_NAME;
            $temp = DB::table($taskReviewTable)
                ->select([
                    DBHelper::tableCol($taskReviewTable, TaskReviewConstants::COL_TASK_REVIEW_TEMPLATE_ID),
                    DBHelper::tableCol($taskReviewTemplateTable, TaskReviewTemplateConstants::COL_TASK_INFO_ID),
                ])
                ->join(
                    $taskReviewTemplateTable,
                    DBHelper::tableCol($taskReviewTemplateTable, TaskReviewTemplateConstants::COL_ID),
                    '=',
                    DBHelper::tableCol($taskReviewTable, TaskReviewConstants::COL_TASK_REVIEW_TEMPLATE_ID)
                )
                ->where(
                    DBHelper::tableCol($taskReviewTable, TaskReviewConstants::COL_ID),
                    '=',
                    $id
                )
                ->where(
                    DBHelper::tableCol($taskReviewTable, TaskReviewConstants::COL_USER_ID),
                    '=',
                    $userId
                )
                ->first();


            if ($temp === null) {
                throw new AppModelNotFoundException(
                    'Review',
                    withProperties: [
                        'id' => $id,
                        'userId' => $userId
                    ]
                );
            }

            $templateId = DBHelper::access($temp, TaskReviewConstants::COL_TASK_REVIEW_TEMPLATE_ID);
            $taskInfoId = DBHelper::access($temp, TaskReviewTemplateConstants::COL_TASK_INFO_ID);
            /**
             * @var int $templateId
             * @var int $taskInfoId
             */
        }

        // Delete task review
        $deleted = DB::table(TaskReviewConstants::TABLE_NAME)
            ->where(TaskReviewConstants::COL_ID, '=', $id)
            ->where(TaskReviewConstants::COL_USER_ID, '=', $userId)
            ->delete();

        if ($deleted === 0) {
            // someone already deleted this task review for us,
            // so we let them to also delete everything else
            return $response;
        }

        $taskReferingToTaskInfoExists = null;

        // We try to delete task review template if possible
        $deleted = 0;
        try {
            $deleted = DB::table(TaskReviewTemplateConstants::TABLE_NAME)
                ->where(TaskReviewTemplateConstants::COL_ID, '=', $templateId)
                ->delete();
        } catch (Throwable $e) {
            if (($taskReferingToTaskInfoExists = DB::table(TaskConstants::TABLE_NAME)
                ->where(TaskConstants::COL_TASK_INFO_ID, '=', $taskInfoId)
                ->exists())) {
                // If there is task with same task info, we won't try to delete template again,
                // because it just recreates it again and maybe we hit some special case where
                // we would try to delete locked template
                return $response;
            }
            if (DB::table(TaskReviewConstants::TABLE_NAME)
                ->where(TaskReviewConstants::COL_TASK_REVIEW_TEMPLATE_ID, '=', $templateId)
                ->exists()
            ) {
                // If there are dependant task reviews on this template, we are done
                return $response;
            }

            $deleted = DB::table(TaskReviewTemplateConstants::TABLE_NAME)
                ->where(TaskReviewTemplateConstants::COL_ID, '=', $templateId)
                ->delete();
        }

        if ($deleted === 0) {
            // Someone already deleted this template for us,
            // so we let them to delete task info if possible too.
            return $response;
        }

        $taskReferingToTaskInfoExists ??= DB::table(TaskConstants::TABLE_NAME)
            ->where(TaskConstants::COL_TASK_INFO_ID, '=', $taskInfoId)
            ->exists();
        if ($taskReferingToTaskInfoExists) {
            return $response;
        }

        $taskReviewTemplateReferencingToTaskInfoExists = DB::table(TaskReviewTemplateConstants::TABLE_NAME)
            ->where(TaskReviewTemplateConstants::COL_TASK_INFO_ID, '=', $taskInfoId)
            ->exists();
        if ($taskReviewTemplateReferencingToTaskInfoExists) {
            return $response;
        }

        DB::table(TaskInfoConstants::TABLE_NAME)
            ->where(TaskInfoConstants::COL_ID, '=', $taskInfoId)
            ->delete();
        return $response;
    }
}
