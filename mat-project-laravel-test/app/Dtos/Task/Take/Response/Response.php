<?php
/**
 * @file ATTENTION!!! The code below was carefully crafted by a mean machine.
 * Please consider to NOT put any emotional human-generated modifications as the splendid AI will throw them away with no mercy.
 */

namespace App\Dtos\Task\Take\Response;

use Swaggest\JsonSchema\Constraint\Properties;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\Structure\ClassStructure;


/**
 * Take task response
 */
class Response extends ClassStructure
{
    /** @var Task */
    public $task;

    /**
     * @param Properties|static $properties
     * @param Schema $ownerSchema
     */
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $properties->task = Task::schema();
        $ownerSchema->type = Schema::OBJECT;
        $ownerSchema->schema = "http://json-schema.org/draft-07/schema";
        $ownerSchema->title = "Take task response";
        $ownerSchema->required = array(
            self::names()->task,
        );
        $ownerSchema->defs = (object)[
            'exercise' => (object)[
                'required' => [
                    'type',
                    'instructions',
                    'details',
                ],
                'properties' => (object)[
                    'type' => (object)[
                        'type' => 'string',
                        'const' => 'exercise',
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
                    'details' => (object)[
                        'anyOf' => [
                            (object)[
                                '$ref' => 'C:/Users/Jindra/source/repos/JS/Mat_project_backend-test/mat-project-laravel-test/schemas/defs/exercises/FillInBlanks/take_response.json',
                            ],
                            (object)[
                                '$ref' => 'C:/Users/Jindra/source/repos/JS/Mat_project_backend-test/mat-project-laravel-test/schemas/defs/exercises/FixErrors/take_response.json',
                            ],
                        ],
                    ],
                ],
                'type' => 'object',
            ],
            'group' => (object)[
                'required' => [
                    'resources',
                    'entries',
                ],
                'properties' => (object)[
                    'type' => (object)[
                        'type' => 'string',
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
            'cmb' => (object)[
                'title' => 'Combobox',
                'description' => 'Combobox of Fill in blanks exercise.',
                'required' => [
                    'type',
                    'values',
                ],
                'properties' => (object)[
                    'type' => (object)[
                        'type' => 'string',
                        'const' => 'cmb',
                    ],
                    'values' => (object)[
                        'items' => (object)[
                            'type' => 'object',
                        ],
                        'minItems' => 1,
                        'type' => 'array',
                    ],
                    'selectedIndex' => (object)[
                        'title' => 'User selected index',
                        'minimum' => 0,
                        'type' => 'integer',
                        '$comment' => 'Outside of bounds of the values array = no item is selected',
                    ],
                ],
                'type' => 'object',
            ],
            'txtI' => (object)[
                'title' => 'Text input',
                'description' => 'Text input of Fill in blanks exercise.',
                'required' => [
                    'type',
                ],
                'properties' => (object)[
                    'type' => (object)[
                        'type' => 'string',
                        'const' => 'txtI',
                    ],
                    'text' => (object)[
                        'title' => 'User filled text',
                        'type' => 'string',
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
     * @param Task $task
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setTask(Task $task)
    {
        $this->task = $task;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */
}