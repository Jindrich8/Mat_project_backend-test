<?php

namespace App\Http\Controllers;

use App\Dtos\Defs\Exercises\FillInBlanks\FillInBlanksReviewResponse;
use App\Dtos\Defs\Exercises\FixErrors\FixErrorsReviewResponse;
use App\Dtos\Defs\Types\Errors\EnumArrayError;
use App\Dtos\Defs\Types\Errors\UserSpecificPartOfAnError;
use App\Dtos\Defs\Types\Response\ResponseEnumElement;
use App\Dtos\Defs\Types\Response\ResponseOrderedEnumElement;
use App\Dtos\Defs\Types\Response\ResponseOrderedEnumRange;
use App\Dtos\Defs\Types\Review\AuthorInfo;
use App\Dtos\Defs\Types\Review\ExercisePoints;
use App\Dtos\Defs\Types\Review\ExerciseReview;
use App\Dtos\Defs\Types\Review\ReviewTaskDetailInfo;
use App\Dtos\Defs\Types\Review\ReviewTaskPreviewInfo;
use App\Dtos\Defs\Types\Task\TaskDetailInfo;
use App\Dtos\Errors\ErrorResponse;
use App\Dtos\InternalTypes\TaskReviewExercisesContent;
use App\Models\TaskReview;
use App\Http\Requests\StoreTaskReviewRequest;
use App\Http\Requests\UpdateTaskReviewRequest;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use App\Dtos\Task\Review;
use App\Dtos\Task\Review\Get\DefsExerciseInstructions;
use App\Dtos\Task\Review\List\ReviewsItems;
use App\Exceptions\ApplicationException;
use App\Exceptions\AppModelNotFoundException;
use App\Exceptions\InternalException;
use App\Helpers\BareModels\BareTaskWAuthorName;
use App\Helpers\Database\DBHelper;
use App\Helpers\Database\DBJsonHelper;
use App\Helpers\Database\UserHelper;
use App\Helpers\DtoHelper;
use App\Helpers\ExerciseType;
use App\Helpers\RequestHelper;
use App\Helpers\ResponseHelper;
use App\Helpers\TaskHelper;
use App\ModelConstants\ExerciseConstants;
use App\ModelConstants\GroupConstants;
use App\ModelConstants\TaskConstants;
use App\ModelConstants\TaskInfoConstants;
use App\ModelConstants\TaskReviewConstants;
use App\ModelConstants\TaskReviewExerciseConstants;
use App\ModelConstants\TaskReviewTemplateConstants;
use App\ModelConstants\UserConstants;
use App\Models\TaskReviewExercise;
use App\Models\TaskReviewTemplate;
use App\TableSpecificData\TaskDifficulty;
use App\TableSpecificData\TaskDisplay;
use App\Types\ConstructableTrait;
use App\Utils\DBUtils;
use App\Utils\DtoUtils;
use App\Utils\TimeStampUtils;
use App\Utils\Utils;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Throwable;

class TaskReviewController extends Controller
{
    use ConstructableTrait;

    public function get(Request $request, int $id): Review\Get\Response
    {
        $userId = UserHelper::getUserId();
        $taskReviewTable = TaskReviewConstants::TABLE_NAME;
        $taskReviewTemplateTable = TaskReviewTemplateConstants::TABLE_NAME;
        $taskInfoTable = TaskInfoConstants::TABLE_NAME;

        $review = DB::table($taskReviewTable)
            ->select([
                DBHelper::colFromTableAsCol($taskReviewTable, TaskReviewConstants::COL_EXERCISES),
                DBHelper::colFromTableAsCol($taskReviewTable, TaskReviewConstants::COL_CREATED_AT),
                DBHelper::colFromTableAsCol($taskReviewTable, TaskReviewConstants::COL_ID),
                DBHelper::colFromTableAsCol($taskReviewTable, TaskReviewConstants::COL_MAX_POINTS),
                DBHelper::colFromTableAsCol($taskReviewTable, TaskReviewConstants::COL_SCORE),
                DBHelper::colFromTableAsCol($taskReviewTable, TaskReviewConstants::COL_TASK_REVIEW_TEMPLATE_ID),
                DBHelper::colFromTableAsCol($taskReviewTemplateTable, TaskReviewTemplateConstants::COL_TASK_ID),
                DBHelper::colFromTableAsCol($taskReviewTemplateTable, TaskReviewTemplateConstants::COL_TASK_INFO_ID),
                DBHelper::colFromTableAsCol($taskInfoTable, TaskInfoConstants::COL_NAME),
                DBHelper::colExpression($taskInfoTable, TaskInfoConstants::COL_DIFFICULTY),
                DBHelper::colExpression($taskInfoTable, TaskInfoConstants::COL_DESCRIPTION),
                DBHelper::colExpression($taskInfoTable, TaskInfoConstants::COL_ORIENTATION),
                DBHelper::colExpression($taskInfoTable, TaskInfoConstants::COL_MIN_CLASS),
                DBHelper::colExpression($taskInfoTable, TaskInfoConstants::COL_MAX_CLASS)
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

        $exercises = DtoUtils::importDto(
            dto: TaskReviewExercisesContent::class,
            json: DBHelper::access($review, TaskReviewConstants::COL_EXERCISES),
            table: $taskReviewTable,
            column: TaskReviewConstants::COL_EXERCISES,
            id: $id,
            wrapper: TaskReviewExercisesContent::CONTENT
        );

        $score = DBHelper::access($review, TaskReviewConstants::COL_SCORE);
        $maxPoints = DBHelper::access($review, TaskReviewConstants::COL_MAX_POINTS);
        $evaluationTimestamp = TimeStampUtils::parseIsoTimestampToUtc(
            DBHelper::access($review, TaskReviewConstants::COL_CREATED_AT)
        );


        $responseTask = Review\Get\Task::create()
            ->setId(DBHelper::access($review, TaskReviewConstants::COL_ID))
            ->setName(DBHelper::access($review, TaskInfoConstants::COL_NAME))
            ->setDisplay(TaskDisplay::translateFrom(DBHelper::access($review, TaskInfoConstants::COL_ORIENTATION)))
            ->setPoints(
                ExercisePoints::create()
                    ->setHas($maxPoints * $score)
                    ->setMax($maxPoints)
            )
            ->setEvaluationTimestamp(TimeStampUtils::timestampToString($evaluationTimestamp));

        $taskEntries = &$responseTask->entries;
        TaskHelper::getTaskEntries(
            taskInfoId: $taskInfoId,
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

        return Review\Get\Response::create()
            ->setTask($responseTask);
    }

    public function detail(Request $request, int $id): Review\Detail\Response
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
                DBHelper::colFromTableAsCol($taskReviewTable, TaskReviewConstants::COL_TASK_REVIEW_TEMPLATE_ID),

                DBHelper::colFromTableAsCol($taskReviewTemplateTable, TaskReviewTemplateConstants::COL_TASK_ID),
                DBHelper::colFromTableAsCol($taskReviewTemplateTable, TaskReviewTemplateConstants::COL_TASK_INFO_ID),
                DBHelper::colFromTableAsCol($taskReviewTemplateTable, TaskReviewTemplateConstants::COL_AUTHOR_ID),
                DBHelper::colFromTableAsCol($taskReviewTemplateTable, TaskReviewTemplateConstants::COL_AUTHOR_NAME),

                DBHelper::colFromTableAsCol($taskInfoTable, TaskInfoConstants::COL_NAME),
                DBHelper::colExpression($taskInfoTable, TaskInfoConstants::COL_DIFFICULTY),
                DBHelper::colExpression($taskInfoTable, TaskInfoConstants::COL_DESCRIPTION),
                DBHelper::colExpression($taskInfoTable, TaskInfoConstants::COL_ORIENTATION),
                DBHelper::colExpression($taskInfoTable, TaskInfoConstants::COL_MIN_CLASS),
                DBHelper::colExpression($taskInfoTable, TaskInfoConstants::COL_MAX_CLASS),
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
            $author->setId($authorId);
        }



        $maxPoints = (float)DBHelper::access($review, TaskReviewConstants::COL_MAX_POINTS);
        $score = (float)DBHelper::access($review, TaskReviewConstants::COL_SCORE);

        $response = Review\Detail\Response::create()
            ->setId($id)
            ->setPoints(
                ExercisePoints::create()
                    ->setMax($maxPoints)
                    ->setHas($score * $maxPoints)
            )
            ->setTaskHasChanged($taskHasChanged)
            ->setTaskDetail(
                ReviewTaskDetailInfo::create()
                    ->setId(DBHelper::access($review, TaskReviewTemplateConstants::COL_TASK_ID))
                    ->setName(DBHelper::access($review, TaskInfoConstants::COL_NAME))
                    ->setDescription(DBHelper::access($review, TaskInfoConstants::COL_DESCRIPTION))
                    ->setDifficulty(
                        DtoUtils::createOrderedEnumDto(
                            TaskDifficulty::fromThrow(DBHelper::access($review, TaskInfoConstants::COL_DIFFICULTY))
                        )
                    )
                    ->setTags($tags)
                    ->setAuthor($author)
                    ->setClassRange(
                        ResponseOrderedEnumRange::create()
                            ->setMin(DBHelper::access($review, TaskInfoConstants::COL_MIN_CLASS))
                            ->setMax(DBHelper::access($review, TaskInfoConstants::COL_MAX_CLASS))
                    )
            );
        return $response;
    }


    /**
     * @throws ApplicationException
     * @throws AuthenticationException
     */
    public function list(Request $request): Review\List\Response
    {
        $userId = UserHelper::getUserId();
        $requestData = RequestHelper::getDtoFromRequest(Review\List\Request::class, $request);

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
                DBHelper::colFromTableAsCol($taskReviewTable, TaskReviewConstants::COL_TASK_REVIEW_TEMPLATE_ID),

                DBHelper::colFromTableAsCol($taskReviewTemplateTable, TaskReviewTemplateConstants::COL_TASK_ID),
                DBHelper::colFromTableAsCol($taskReviewTemplateTable, TaskReviewTemplateConstants::COL_TASK_INFO_ID),
                DBHelper::colFromTableAsCol($taskReviewTemplateTable, TaskReviewTemplateConstants::COL_AUTHOR_ID),
                DBHelper::colFromTableAsCol($taskReviewTemplateTable, TaskReviewTemplateConstants::COL_AUTHOR_NAME),

                DBHelper::colFromTableAsCol($taskInfoTable, TaskInfoConstants::COL_NAME),
                DBHelper::colExpression($taskInfoTable, TaskInfoConstants::COL_DIFFICULTY),
                DBHelper::colExpression($taskInfoTable, TaskInfoConstants::COL_DESCRIPTION),
                DBHelper::colExpression($taskInfoTable, TaskInfoConstants::COL_ORIENTATION),
                DBHelper::colExpression($taskInfoTable, TaskInfoConstants::COL_MIN_CLASS),
                DBHelper::colExpression($taskInfoTable, TaskInfoConstants::COL_MAX_CLASS),
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
                DBHelper::tableCol(TaskInfoConstants::TABLE_NAME, TaskInfoConstants::COL_NAME)
                    . " LIKE %?%",
                [$filters->name]
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
                column: DBHelper::tableCol($taskReviewTable, TaskReviewConstants::COL_CREATED_AT)
            );
            if ($rangeError) {
                ($filterErrorData ??= Review\List\Errors\FilterErrorDetailsErrorData::create())
                    ->setEvaluationTimestampRange($rangeError);
            }
        }
        if (($scoreRange = $filters->scoreRange)) {
            $builder->whereBetween(
                DBHelper::tableCol($taskReviewTable, TaskReviewConstants::COL_SCORE),
                [$scoreRange->min, $scoreRange->max]
            );
        }

        if ($filterErrorData) {
            $details = Review\List\Errors\FilterErrorDetails::create()
                ->setErrorData($filterErrorData);
            throw new ApplicationException(
                ResponseAlias::HTTP_BAD_REQUEST,
                ErrorResponse::create()
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

        $reviews = $builder->get();


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
            fn (int $key, array $tag) => [
                $key,
                ResponseEnumElement::create()
                    ->setId($tag[0])
                    ->setName($tag[1])
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
                $author->setId($authorId);
            }
            $taskPreviewInfo = ReviewTaskPreviewInfo::create()
                ->setName(DBHelper::access($review, TaskInfoConstants::COL_NAME))
                ->setDifficulty(
                    DtoUtils::createOrderedEnumDto(
                        TaskDifficulty::fromThrow(DBHelper::access($review, TaskInfoConstants::COL_DIFFICULTY))
                    )
                )
                ->setTags($tags[$taskInfoId])
                ->setAuthor($author)
                ->setClassRange(
                    ResponseOrderedEnumRange::create()
                        ->setMin(DBHelper::access($review, TaskInfoConstants::COL_MIN_CLASS))
                        ->setMax(DBHelper::access($review, TaskInfoConstants::COL_MAX_CLASS))
                );

            $taskId = DBHelper::access($review, TaskReviewTemplateConstants::COL_TASK_ID);
            if ($taskId !== null) {
                $taskPreviewInfo->setId(
                    ResponseHelper::translateIdForUser($taskId)
                );
            }
            return ReviewsItems::create()
                ->setId(
                    ResponseHelper::translateIdForUser(
                        DBHelper::access($review, TaskReviewConstants::COL_ID)
                    )
                )
                ->setScore(DBHelper::access($review, TaskReviewConstants::COL_SCORE))
                ->setEvaluationTimestamp(
                    TimeStampUtils::timestampToString(
                        TimeStampUtils::parseIsoTimestampToUtc(
                            DBHelper::access($review, TaskReviewConstants::COL_CREATED_AT)
                        )
                    )
                )
                ->setTaskPreviewInfo($taskPreviewInfo);
        });

        return Review\List\Response::create()
            ->setReviews($reviews->all());
    }

    public function delete(Request $request, int $id)
    {
        $userId = UserHelper::getUserId();
        // Task review never changes nor task template review does

        // Get task review template id and task info id
        $templateId = null;
        $taskInfoId = null; {
            $temp = DB::table(TaskReviewConstants::TABLE_NAME)
                ->select([
                    TaskReviewConstants::COL_TASK_REVIEW_TEMPLATE_ID,
                    TaskReviewTemplateConstants::COL_TASK_INFO_ID,
                ])
                ->join(
                    TaskReviewTemplateConstants::TABLE_NAME,
                    TaskReviewTemplateConstants::COL_ID,
                    '=',
                    TaskReviewConstants::COL_TASK_REVIEW_TEMPLATE_ID
                )
                ->where(TaskReviewConstants::COL_ID, '=', $id)
                ->where(TaskReviewConstants::COL_USER_ID, '=', $userId)
                ->first();


            if ($temp === null) {
                throw new AppModelNotFoundException(
                    TaskReview::class,
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
            return;
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
                return;
            }
            if (DB::table(TaskReviewConstants::TABLE_NAME)
                ->where(TaskReviewConstants::COL_TASK_REVIEW_TEMPLATE_ID, '=', $templateId)
                ->exists()
            ) {
                // If there are dependant task reviews on this template, we are done
                return;
            }

            $deleted = DB::table(TaskReviewTemplateConstants::TABLE_NAME)
                ->where(TaskReviewTemplateConstants::COL_ID, '=', $templateId)
                ->delete();
        }

        if ($deleted === 0) {
            // Someone already deleted this template for us,
            // so we let them to delete task info if possible too.
            return;
        }

        $taskReferingToTaskInfoExists ??= DB::table(TaskConstants::TABLE_NAME)
            ->where(TaskConstants::COL_TASK_INFO_ID, '=', $taskInfoId)
            ->exists();
        if ($taskReferingToTaskInfoExists) {
            return;
        }

        $taskReviewTemplateReferencingToTaskInfoExists = DB::table(TaskReviewTemplateConstants::TABLE_NAME)
            ->where(TaskReviewTemplateConstants::COL_TASK_INFO_ID, '=', $taskInfoId)
            ->exists();
        if ($taskReviewTemplateReferencingToTaskInfoExists) {
            return;
        }

        DB::table(TaskInfoConstants::TABLE_NAME)
            ->where(TaskInfoConstants::COL_ID, '=', $taskInfoId)
            ->delete();
    }
}
