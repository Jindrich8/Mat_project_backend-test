<?php
/**
 * @file ATTENTION!!! The code below was carefully crafted by a mean machine.
 * Please consider to NOT put any emotional human-generated modifications as the splendid AI will throw them away with no mercy.
 */

namespace App\Dtos\InternalTypes\FillInBlanksContent;

use Swaggest\JsonSchema\Constraint\Properties;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\Structure\ClassStructure;


/**
 * Fill in blank's content structure wrapper
 * Represents wrapper of content structure of Fill in blanks exercise.
 */
class FillInBlanksContent extends ClassStructure
{
    /** @var string[]|TextInput[]|Combobox[]|array Represents the content structure of Fill in blanks exercise. */
    public $structure;

    /**
     * @param Properties|static $properties
     * @param Schema $ownerSchema
     */
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $properties->structure = Schema::arr();
        $properties->structure->items = new Schema();
        $propertiesStructureItemsAnyOf0 = Schema::string();
        $propertiesStructureItemsAnyOf0->title = "Text";
        $propertiesStructureItemsAnyOf0->description = "Text between ui components.";
        $properties->structure->items->anyOf[0] = $propertiesStructureItemsAnyOf0;
        $properties->structure->items->anyOf[1] = TextInput::schema();
        $properties->structure->items->anyOf[2] = Combobox::schema();
        $properties->structure->items->defs = (object)[
            'txtI' => (object)[
                'title' => 'Text input',
                'description' => 'Text input of Fill in blanks exercise.',
                'required' => [
                    'type',
                    'correctText',
                ],
                'properties' => (object)[
                    'type' => (object)[
                        'type' => 'string',
                        'const' => 'txtI',
                    ],
                    'correctText' => (object)[
                        'type' => 'string',
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
                            'type' => 'string',
                        ],
                        'minItems' => 1,
                        'uniqueItems' => true,
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
            ],
        ];
        $properties->structure->title = "Fill in blanks's content structure";
        $properties->structure->description = "Represents the content structure of Fill in blanks exercise.";
        $properties->structure->minItems = 1;
        $properties->structure->contains = (object)[
            'anyOf' => [
                (object)[
                    '$ref' => '#/$defs/cmb',
                ],
                (object)[
                    '$ref' => '#/$defs/txtI',
                ],
            ],
        ];
        $properties->structure->defs = (object)[
            'txtI' => (object)[
                'title' => 'Text input',
                'description' => 'Text input of Fill in blanks exercise.',
                'required' => [
                    'type',
                    'correctText',
                ],
                'properties' => (object)[
                    'type' => (object)[
                        'type' => 'string',
                        'const' => 'txtI',
                    ],
                    'correctText' => (object)[
                        'type' => 'string',
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
                            'type' => 'string',
                        ],
                        'minItems' => 1,
                        'uniqueItems' => true,
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
            ],
        ];
        $ownerSchema->type = Schema::OBJECT;
        $ownerSchema->schema = "http://json-schema.org/draft-07/schema#";
        $ownerSchema->title = "Fill in blank's content structure wrapper";
        $ownerSchema->description = "Represents wrapper of content structure of Fill in blanks exercise.";
        $ownerSchema->required = array(
            self::names()->structure,
        );
        $ownerSchema->defs = (object)[
            'txtI' => (object)[
                'title' => 'Text input',
                'description' => 'Text input of Fill in blanks exercise.',
                'required' => [
                    'type',
                    'correctText',
                ],
                'properties' => (object)[
                    'type' => (object)[
                        'type' => 'string',
                        'const' => 'txtI',
                    ],
                    'correctText' => (object)[
                        'type' => 'string',
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
                            'type' => 'string',
                        ],
                        'minItems' => 1,
                        'uniqueItems' => true,
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
            ],
        ];
    }

    /**
     * @param string[]|TextInput[]|Combobox[]|array $structure Represents the content structure of Fill in blanks exercise.
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setStructure($structure)
    {
        $this->structure = $structure;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */
}