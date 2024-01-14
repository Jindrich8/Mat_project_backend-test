<?php
/**
 * @file ATTENTION!!! The code below was carefully crafted by a mean machine.
 * Please consider to NOT put any emotional human-generated modifications as the splendid AI will throw them away with no mercy.
 */

namespace App\Dtos\Task\Review\Response;

use Swaggest\JsonSchema\Constraint\Properties;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\Structure\ClassStructure;


/**
 * Built from #/$defs/group
 */
class DefsGroup extends ClassStructure
{
    /** @var string */
    public $type;

    /** @var DefsGroupResourcesItems[]|array */
    public $resources;

    /** @var DefsExercise[]|DefsGroup[]|array */
    public $entries;

    /**
     * @return static
     */
    public static function create()
    {
        $instance = parent::create();
        $instance->type = "group";
        return $instance;
    }

    /**
     * @param Properties|static $properties
     * @param Schema $ownerSchema
     */
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $properties->type = Schema::string();
        $properties->type->const = "group";
        $properties->resources = Schema::arr();
        $properties->resources->items = DefsGroupResourcesItems::schema();
        $properties->entries = Schema::arr();
        $properties->entries->items = new Schema();
        $properties->entries->items->oneOf[0] = DefsExercise::schema();
        $properties->entries->items->oneOf[1] = DefsGroup::schema();
        $properties->entries->items->defs = (object)[
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
                                '$ref' => 'C:/Users/Jindra/source/repos/JS/Mat_project_backend-test/mat-project-laravel-test/schemas/defs/exercises/FillInBlanks/review_response.json',
                            ],
                            (object)[
                                '$ref' => 'C:/Users/Jindra/source/repos/JS/Mat_project_backend-test/mat-project-laravel-test/schemas/defs/exercises/FixErrors/review_response.json',
                            ],
                        ],
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
                            '$ref' => '#',
                        ],
                        'type' => 'array',
                    ],
                ],
                'type' => 'object',
            ],
        ];
        $properties->entries->defs = (object)[
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
                                '$ref' => 'C:/Users/Jindra/source/repos/JS/Mat_project_backend-test/mat-project-laravel-test/schemas/defs/exercises/FillInBlanks/review_response.json',
                            ],
                            (object)[
                                '$ref' => 'C:/Users/Jindra/source/repos/JS/Mat_project_backend-test/mat-project-laravel-test/schemas/defs/exercises/FixErrors/review_response.json',
                            ],
                        ],
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
                        '$ref' => '#',
                    ],
                ],
                'type' => 'object',
            ],
        ];
        $ownerSchema->type = Schema::OBJECT;
        $ownerSchema->required = array(
            self::names()->resources,
            self::names()->entries,
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
                                '$ref' => 'C:/Users/Jindra/source/repos/JS/Mat_project_backend-test/mat-project-laravel-test/schemas/defs/exercises/FillInBlanks/review_response.json',
                            ],
                            (object)[
                                '$ref' => 'C:/Users/Jindra/source/repos/JS/Mat_project_backend-test/mat-project-laravel-test/schemas/defs/exercises/FixErrors/review_response.json',
                            ],
                        ],
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
            'group' => (object)[
                '$ref' => '#',
            ],
        ];
        $ownerSchema->setFromRef('#/$defs/group');
    }

    /**
     * @param string $type
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */

    /**
     * @param DefsGroupResourcesItems[]|array $resources
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setResources($resources)
    {
        $this->resources = $resources;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */

    /**
     * @param DefsExercise[]|DefsGroup[]|array $entries
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setEntries($entries)
    {
        $this->entries = $entries;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */
}