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
 * Built from C:/Users/Jindra/source/repos/JS/Mat_project_backend-test/mat-project-laravel-test/schemas/task/defs/exercises/Doplnovacka/take_response.json
 */
class DoplnovackaTakeResponse extends ClassStructure
{
    /** @var mixed */
    public $exerType;

    /** @var DefsCmb[]|DefsTxtI[]|string[]|array */
    public $content;

    /**
     * @param Properties|static $properties
     * @param Schema $ownerSchema
     */
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $properties->exerType = new Schema();
        $properties->exerType->const = "Doplnovacka";
        $properties->content = Schema::arr();
        $properties->content->items = new Schema();
        $properties->content->items->oneOf[0] = DefsCmb::schema();
        $properties->content->items->oneOf[1] = DefsTxtI::schema();
        $propertiesContentItemsOneOf2 = Schema::string();
        $propertiesContentItemsOneOf2->setFromRef('#/$defs/value');
        $properties->content->items->oneOf[2] = $propertiesContentItemsOneOf2;
        $properties->content->items->minItems = 1;
        $properties->content->items->contains = (object)[
            'oneOf' => [
                (object)[
                    '$ref' => '#/$defs/cmb',
                ],
                (object)[
                    '$ref' => '#/$defs/txtI',
                ],
            ],
        ];
        $properties->content->items->defs = (object)[
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
        $properties->content->defs = (object)[
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
        $ownerSchema->type = Schema::OBJECT;
        $ownerSchema->schema = "http://json-schema.org/draft-07/schema#";
        $ownerSchema->required = array(
            self::names()->exerType,
            self::names()->content,
        );
        $ownerSchema->defs = (object)[
            'value' => (object)[
                'type' => 'string',
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
        ];
        $ownerSchema->setFromRef('C:/Users/Jindra/source/repos/JS/Mat_project_backend-test/mat-project-laravel-test/schemas/task/defs/exercises/Doplnovacka/take_response.json');
    }

    /**
     * @param mixed $exerType
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setExerType($exerType)
    {
        $this->exerType = $exerType;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */

    /**
     * @param DefsCmb[]|DefsTxtI[]|string[]|array $content
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */
}