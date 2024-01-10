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
 * Built from #/$defs/exercise
 */
class DefsExercise extends ClassStructure
{
    /** @var string */
    public $type;

    /** @var DefsExerciseInstructions */
    public $instructions;

    /** @var DoplnovackaReviewResponse|HledaniChybReviewResponse */
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
        $properties->details->anyOf[0] = DoplnovackaReviewResponse::schema();
        $properties->details->anyOf[1] = HledaniChybReviewResponse::schema();
        $properties->details->defs = (object)[
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
        $ownerSchema->type = Schema::OBJECT;
        $ownerSchema->required = array(
            self::names()->type,
            self::names()->instructions,
            self::names()->details,
        );
        $ownerSchema->defs = (object)[
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
     * @param DoplnovackaReviewResponse|HledaniChybReviewResponse $details
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