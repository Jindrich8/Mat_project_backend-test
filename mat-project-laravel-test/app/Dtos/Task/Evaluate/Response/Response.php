<?php
/**
 * @file ATTENTION!!! The code below was carefully crafted by a mean machine.
 * Please consider to NOT put any emotional human-generated modifications as the splendid AI will throw them away with no mercy.
 */

namespace App\Dtos\Task\Evaluate\Response;

use Swaggest\JsonSchema\Constraint\Properties;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\Structure\ClassStructure;


/**
 * Evaluate task response
 */
class Response extends ClassStructure
{
    /** @var Data */
    public $data;

    /**
     * @param Properties|static $properties
     * @param Schema $ownerSchema
     */
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $properties->data = Data::schema();
        $ownerSchema->type = Schema::OBJECT;
        $ownerSchema->schema = "http://json-schema.org/draft-07/schema";
        $ownerSchema->title = "Evaluate task response";
        $ownerSchema->required = array(
            self::names()->data,
        );
        $ownerSchema->defs = (object)[
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
     * @param Data $data
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setData(Data $data)
    {
        $this->data = $data;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */
}