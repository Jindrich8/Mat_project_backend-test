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
 * Built from C:/Users/Jindra/source/repos/JS/Mat_project_backend-test/mat-project-laravel-test/schemas/defs/exercises/FillInBlanks/take_response.json
 */
class FillInBlanksTakeResponse extends ClassStructure
{
    /** @var string */
    public $exerType;

    /** @var Combobox[]|TextInput[]|string[]|array */
    public $content;

    /**
     * @return static
     */
    public static function create()
    {
        $instance = parent::create();
        $instance->exerType = "Fill in blanks";
        return $instance;
    }

    /**
     * @param Properties|static $properties
     * @param Schema $ownerSchema
     */
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $properties->exerType = Schema::string();
        $properties->exerType->const = "Fill in blanks";
        $properties->content = Schema::arr();
        $properties->content->items = new Schema();
        $properties->content->items->oneOf[0] = Combobox::schema();
        $properties->content->items->oneOf[1] = TextInput::schema();
        $propertiesContentItemsOneOf2 = Schema::string();
        $propertiesContentItemsOneOf2->setFromRef('#/$defs/value');
        $properties->content->items->oneOf[2] = $propertiesContentItemsOneOf2;
        $properties->content->items->defs = (object)[
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
        $properties->content->minItems = 1;
        $properties->content->contains = (object)[
            'oneOf' => [
                (object)[
                    '$ref' => '#/$defs/cmb',
                ],
                (object)[
                    '$ref' => '#/$defs/txtI',
                ],
            ],
        ];
        $properties->content->defs = (object)[
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
        ];
        $ownerSchema->setFromRef('C:/Users/Jindra/source/repos/JS/Mat_project_backend-test/mat-project-laravel-test/schemas/defs/exercises/FillInBlanks/take_response.json');
    }

    /**
     * @param string $exerType
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
     * @param Combobox[]|TextInput[]|string[]|array $content
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