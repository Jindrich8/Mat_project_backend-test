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
 * Built from #/$defs/cmb
 * @property int|null $selectedIndex
 */
class DefsCmb extends ClassStructure
{
    /** @var array */
    public $values;

    /**
     * @param Properties|static $properties
     * @param Schema $ownerSchema
     */
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $properties->values = Schema::arr();
        $properties->values->items = Schema::object();
        $properties->values->minItems = 1;
        $properties->selectedIndex = new Schema();
        $propertiesSelectedIndexOneOf0 = Schema::integer();
        $propertiesSelectedIndexOneOf0->minimum = 0;
        $properties->selectedIndex->oneOf[0] = $propertiesSelectedIndexOneOf0;
        $properties->selectedIndex->oneOf[1] = Schema::null();
        $properties->selectedIndex->comment = "Outside of bounds of the values array = same as if it was null, i.e. no item is selected";
        $ownerSchema->type = Schema::OBJECT;
        $ownerSchema->required = array(
            self::names()->values,
        );
        $ownerSchema->setFromRef('#/$defs/cmb');
    }

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
     * @param int|null $selectedIndex
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