<?php
/**
 * @file ATTENTION!!! The code below was carefully crafted by a mean machine.
 * Please consider to NOT put any emotional human-generated modifications as the splendid AI will throw them away with no mercy.
 */

namespace App\Dtos\Task\Evaluate\Response;

use Swaggest\JsonSchema\Constraint\Properties;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\Structure\ClassStructure;


class Data extends ClassStructure
{
    /** @var DataTask */
    public $task;

    /**
     * @param Properties|static $properties
     * @param Schema $ownerSchema
     */
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $properties->task = DataTask::schema();
        $ownerSchema->type = Schema::OBJECT;
        $ownerSchema->required = array(
            self::names()->task,
        );
        $ownerSchema->defs = (object)[
            'group' => (object)[
                'required' => [
                    'resources',
                    'entries',
                ],
                'properties' => (object)[
                    'type' => (object)[
                        'const' => 'group',
                    ],
                    'resources' => (object)[
                        'items' => (object)[
                            'required' => [
                                'content',
                            ],
                            'properties' => (object)[
                                'content' => (object)[
                                    'type' => 'string',
                                ],
                            ],
                            'type' => 'object',
                        ],
                        'type' => 'array',
                    ],
                    'entries' => (object)[
                        'items' => (object)[
                            'oneOf' => [
                                (object)[
                                    '$ref' => '#/$defs/exercise',
                                ],
                                (object)[
                                    '$ref' => '#/$defs/group',
                                ],
                            ],
                        ],
                        'type' => 'array',
                    ],
                ],
                'type' => 'object',
            ],
            'exercise' => (object)[
                'allOf' => [
                    (object)[
                        'required' => [
                            'exerType',
                            'instructions',
                            'content',
                        ],
                        'properties' => (object)[
                            'type' => (object)[
                                'const' => 'exercise',
                            ],
                            'exerType' => (object)[
                                'type' => 'string',
                            ],
                            'instructions' => (object)[
                                'required' => [
                                    'content',
                                ],
                                'properties' => (object)[
                                    'content' => (object)[
                                        'type' => 'string',
                                    ],
                                ],
                                'type' => 'object',
                            ],
                            'content' => (object)[
                                'type' => 'object',
                            ],
                        ],
                        'type' => 'object',
                    ],
                    (object)[
                        'anyOf' => [
                            (object)[
                                '$ref' => 'C:/Users/Jindra/source/repos/JS/Mat_project_backend-test/mat-project-laravel-test/schemas/task/defs/exercises/Doplnovacka/review_response.json',
                            ],
                            (object)[
                                '$ref' => 'C:/Users/Jindra/source/repos/JS/Mat_project_backend-test/mat-project-laravel-test/schemas/task/defs/exercises/HledaniChyb/review_response.json',
                            ],
                        ],
                    ],
                ],
            ],
            'cmb' => (object)[
                'required' => [
                    'userValue',
                ],
                'properties' => (object)[
                    'userValue' => (object)[
                        'type' => 'string',
                    ],
                    'correctValue' => (object)[
                        'type' => [
                            'string',
                        ],
                    ],
                ],
                'type' => 'object',
            ],
            'txtI' => (object)[
                'properties' => (object)[
                    'value' => (object)[
                        'type' => [
                            'string',
                        ],
                    ],
                ],
                'type' => 'object',
            ],
            'value' => (object)[
                'type' => 'string',
            ],
        ];
    }

    /**
     * @param DataTask $task
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setTask(DataTask $task)
    {
        $this->task = $task;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */
}