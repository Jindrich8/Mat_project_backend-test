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
 * Text input
 * Text input of Fill in blanks exercise.
 * Built from #/$defs/txtI
 */
class TextInput extends ClassStructure
{
    /** @var string */
    public $type;

    /**
     * @return static
     */
    public static function create()
    {
        $instance = parent::create();
        $instance->type = "txtI";
        return $instance;
    }

    /**
     * @param Properties|static $properties
     * @param Schema $ownerSchema
     */
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $properties->type = Schema::string();
        $properties->type->const = "txtI";
        $ownerSchema->type = Schema::OBJECT;
        $ownerSchema->title = "Text input";
        $ownerSchema->description = "Text input of Fill in blanks exercise.";
        $ownerSchema->required = array(
            self::names()->type,
        );
        $ownerSchema->setFromRef('#/$defs/txtI');
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
}