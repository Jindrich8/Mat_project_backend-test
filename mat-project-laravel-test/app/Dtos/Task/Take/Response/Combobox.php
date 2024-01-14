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
 * Combobox
 * Combobox of Fill in blanks exercise.
 * Built from #/$defs/cmb
 */
class Combobox extends ClassStructure
{
    /** @var string */
    public $type;

    /** @var array */
    public $values;

    /** @var int */
    public $selectedIndex;

    /**
     * @return static
     */
    public static function create()
    {
        $instance = parent::create();
        $instance->type = "cmb";
        return $instance;
    }

    /**
     * @param Properties|static $properties
     * @param Schema $ownerSchema
     */
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $properties->type = Schema::string();
        $properties->type->const = "cmb";
        $properties->values = Schema::arr();
        $properties->values->items = Schema::object();
        $properties->values->minItems = 1;
        $properties->selectedIndex = Schema::integer();
        $properties->selectedIndex->title = "User selected index";
        $properties->selectedIndex->minimum = 0;
        $properties->selectedIndex->comment = "Outside of bounds of the values array = no item is selected";
        $ownerSchema->type = Schema::OBJECT;
        $ownerSchema->title = "Combobox";
        $ownerSchema->description = "Combobox of Fill in blanks exercise.";
        $ownerSchema->required = array(
            self::names()->type,
            self::names()->values,
        );
        $ownerSchema->setFromRef('#/$defs/cmb');
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
     * @param array $values
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setValues($values)
    {
        $this->values = $values;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */

    /**
     * @param int $selectedIndex
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setSelectedIndex($selectedIndex)
    {
        $this->selectedIndex = $selectedIndex;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */
}