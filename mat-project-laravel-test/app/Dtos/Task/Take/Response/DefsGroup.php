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
 * Built from #/$defs/group
 */
class DefsGroup extends ClassStructure
{
    /** @var mixed */
    public $type;

    /** @var DefsGroupResourcesItems[]|array */
    public $resources;

    /** @var DefsExerciseAllOf0[]|DoplnovackaTakeResponse[]|HledaniChybTakeResponse[]|DefsGroup[]|array */
    public $entries;

    /**
     * @param Properties|static $properties
     * @param Schema $ownerSchema
     */
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $properties->type = new Schema();
        $properties->type->const = "group";
        $properties->resources = Schema::arr();
        $properties->resources->items = DefsGroupResourcesItems::schema();
        $properties->entries = Schema::arr();
        $properties->entries->items = new Schema();
        $propertiesEntriesItemsOneOf0 = new Schema();
        $propertiesEntriesItemsOneOf0->allOf[0] = DefsExerciseAllOf0::schema();
        $propertiesEntriesItemsOneOf0AllOf1 = new Schema();
        $propertiesEntriesItemsOneOf0AllOf1->anyOf[0] = DoplnovackaTakeResponse::schema();
        $propertiesEntriesItemsOneOf0AllOf1->anyOf[1] = HledaniChybTakeResponse::schema();
        $propertiesEntriesItemsOneOf0AllOf1->defs = (object)[
            'cmb' => (object)[
                'required' => [
                    'values',
                ],
                'properties' => (object)[
                    'values' => (object)[
                        'items' => (object)[
                            'type' => 'object',
                        ],
                        'minItems' => 1,
                        'type' => 'array',
                    ],
                    'selectedIndex' => (object)[
                        'oneOf' => [
                            (object)[
                                'minimum' => 0,
                                'type' => 'integer',
                            ],
                            (object)[
                                'type' => 'null',
                            ],
                        ],
                        '$comment' => 'Outside of bounds of the values array = same as if it was null, i.e. no item is selected',
                    ],
                ],
                'type' => 'object',
            ],
            'txtI' => (object)[
                'properties' => (object)[
                    'value' => (object)[
                        'type' => [
                            'string',
                            'null',
                        ],
                    ],
                ],
                'type' => 'object',
            ],
            'value' => (object)[
                'type' => 'string',
            ],
        ];
        $propertiesEntriesItemsOneOf0->allOf[1] = $propertiesEntriesItemsOneOf0AllOf1;
        $propertiesEntriesItemsOneOf0->defs = (object)[
            'cmb' => (object)[
                'required' => [
                    'values',
                ],
                'properties' => (object)[
                    'values' => (object)[
                        'items' => (object)[
                            'type' => 'object',
                        ],
                        'minItems' => 1,
                        'type' => 'array',
                    ],
                    'selectedIndex' => (object)[
                        'oneOf' => [
                            (object)[
                                'minimum' => 0,
                                'type' => 'integer',
                            ],
                            (object)[
                                'type' => 'null',
                            ],
                        ],
                        '$comment' => 'Outside of bounds of the values array = same as if it was null, i.e. no item is selected',
                    ],
                ],
                'type' => 'object',
            ],
            'txtI' => (object)[
                'properties' => (object)[
                    'value' => (object)[
                        'type' => [
                            'string',
                            'null',
                        ],
                    ],
                ],
                'type' => 'object',
            ],
            'value' => (object)[
                'type' => 'string',
            ],
        ];
        $propertiesEntriesItemsOneOf0->setFromRef('#/$defs/exercise');
        $properties->entries->items->oneOf[0] = $propertiesEntriesItemsOneOf0;
        $properties->entries->items->oneOf[1] = DefsGroup::schema();
        $properties->entries->items->defs = (object)[
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
                                '$ref' => 'C:/Users/Jindra/source/repos/JS/Mat_project_backend-test/mat-project-laravel-test/schemas/task/defs/exercises/Doplnovacka/take_response.json',
                            ],
                            (object)[
                                '$ref' => 'C:/Users/Jindra/source/repos/JS/Mat_project_backend-test/mat-project-laravel-test/schemas/task/defs/exercises/HledaniChyb/take_response.json',
                            ],
                        ],
                    ],
                ],
            ],
            'cmb' => (object)[
                'required' => [
                    'values',
                ],
                'properties' => (object)[
                    'values' => (object)[
                        'items' => (object)[
                            'type' => 'object',
                        ],
                        'minItems' => 1,
                        'type' => 'array',
                    ],
                    'selectedIndex' => (object)[
                        'oneOf' => [
                            (object)[
                                'minimum' => 0,
                                'type' => 'integer',
                            ],
                            (object)[
                                'type' => 'null',
                            ],
                        ],
                        '$comment' => 'Outside of bounds of the values array = same as if it was null, i.e. no item is selected',
                    ],
                ],
                'type' => 'object',
            ],
            'txtI' => (object)[
                'properties' => (object)[
                    'value' => (object)[
                        'type' => [
                            'string',
                            'null',
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
                                '$ref' => 'C:/Users/Jindra/source/repos/JS/Mat_project_backend-test/mat-project-laravel-test/schemas/task/defs/exercises/Doplnovacka/take_response.json',
                            ],
                            (object)[
                                '$ref' => 'C:/Users/Jindra/source/repos/JS/Mat_project_backend-test/mat-project-laravel-test/schemas/task/defs/exercises/HledaniChyb/take_response.json',
                            ],
                        ],
                    ],
                ],
            ],
            'cmb' => (object)[
                'required' => [
                    'values',
                ],
                'properties' => (object)[
                    'values' => (object)[
                        'items' => (object)[
                            'type' => 'object',
                        ],
                        'minItems' => 1,
                        'type' => 'array',
                    ],
                    'selectedIndex' => (object)[
                        'oneOf' => [
                            (object)[
                                'minimum' => 0,
                                'type' => 'integer',
                            ],
                            (object)[
                                'type' => 'null',
                            ],
                        ],
                        '$comment' => 'Outside of bounds of the values array = same as if it was null, i.e. no item is selected',
                    ],
                ],
                'type' => 'object',
            ],
            'txtI' => (object)[
                'properties' => (object)[
                    'value' => (object)[
                        'type' => [
                            'string',
                            'null',
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
                                '$ref' => 'C:/Users/Jindra/source/repos/JS/Mat_project_backend-test/mat-project-laravel-test/schemas/task/defs/exercises/Doplnovacka/take_response.json',
                            ],
                            (object)[
                                '$ref' => 'C:/Users/Jindra/source/repos/JS/Mat_project_backend-test/mat-project-laravel-test/schemas/task/defs/exercises/HledaniChyb/take_response.json',
                            ],
                        ],
                    ],
                ],
            ],
            'cmb' => (object)[
                'required' => [
                    'values',
                ],
                'properties' => (object)[
                    'values' => (object)[
                        'items' => (object)[
                            'type' => 'object',
                        ],
                        'minItems' => 1,
                        'type' => 'array',
                    ],
                    'selectedIndex' => (object)[
                        'oneOf' => [
                            (object)[
                                'minimum' => 0,
                                'type' => 'integer',
                            ],
                            (object)[
                                'type' => 'null',
                            ],
                        ],
                        '$comment' => 'Outside of bounds of the values array = same as if it was null, i.e. no item is selected',
                    ],
                ],
                'type' => 'object',
            ],
            'txtI' => (object)[
                'properties' => (object)[
                    'value' => (object)[
                        'type' => [
                            'string',
                            'null',
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
     * @param mixed $type
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
     * @param DefsExerciseAllOf0[]|DoplnovackaTakeResponse[]|HledaniChybTakeResponse[]|DefsGroup[]|array $entries
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