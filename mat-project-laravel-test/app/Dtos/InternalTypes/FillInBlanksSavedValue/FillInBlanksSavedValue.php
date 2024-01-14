<?php
/**
 * @file ATTENTION!!! The code below was carefully crafted by a mean machine.
 * Please consider to NOT put any emotional human-generated modifications as the splendid AI will throw them away with no mercy.
 */

namespace App\Dtos\InternalTypes\FillInBlanksSavedValue;

use Swaggest\JsonSchema\Constraint\Properties;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\Structure\ClassStructure;


/**
 * Fill in blanks's saved value structure wrapper
 */
class FillInBlanksSavedValue extends ClassStructure
{
    /** @var string[]|int[]|array */
    public $structure;

    /**
     * @param Properties|static $properties
     * @param Schema $ownerSchema
     */
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $properties->structure = Schema::arr();
        $properties->structure->items = new Schema();
        $properties->structure->items->anyOf[0] = Schema::string();
        $propertiesStructureItemsAnyOf1 = Schema::integer();
        $propertiesStructureItemsAnyOf1->minimum = 0;
        $properties->structure->items->anyOf[1] = $propertiesStructureItemsAnyOf1;
        $ownerSchema->type = Schema::OBJECT;
        $ownerSchema->schema = "http://json-schema.org/draft-07/schema#";
        $ownerSchema->title = "Fill in blanks's saved value structure wrapper";
    }

    /**
     * @param string[]|int[]|array $structure
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