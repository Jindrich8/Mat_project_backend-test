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
 * Built from #/$defs/exercise
 */
class DefsExercise extends ClassStructure
{
    /** @var string */
    public $type;

    /** @var DefsExerciseInstructions */
    public $instructions;

    /** @var FillInBlanksTakeResponse|FixErrorsTakeResponse */
    public $details;

    /**
     * @return static
     */
    public static function create()
    {
        $instance = parent::create();
        $instance->type = "exercise";
        return $instance;
    }

    /**
     * @param Properties|static $properties
     * @param Schema $ownerSchema
     */
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $properties->type = Schema::string();
        $properties->type->const = "exercise";
        $properties->instructions = DefsExerciseInstructions::schema();
        $properties->details = new Schema();
        $properties->details->anyOf[0] = FillInBlanksTakeResponse::schema();
        $properties->details->anyOf[1] = FixErrorsTakeResponse::schema();
        $properties->details->defs = (object)[
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
        $ownerSchema->required = array(
            self::names()->type,
            self::names()->instructions,
            self::names()->details,
        );
        $ownerSchema->defs = (object)[
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
        $ownerSchema->setFromRef('#/$defs/exercise');
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
     * @param DefsExerciseInstructions $instructions
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setInstructions(DefsExerciseInstructions $instructions)
    {
        $this->instructions = $instructions;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */

    /**
     * @param FillInBlanksTakeResponse|FixErrorsTakeResponse $details
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setDetails($details)
    {
        $this->details = $details;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */
}