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
 * Built from #/$defs/txtI
 */
class DefsTxtI extends ClassStructure
{
    /** @var string */
    public $value;

    /**
     * @param Properties|static $properties
     * @param Schema $ownerSchema
     */
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $properties->value = (new Schema())->setType([Schema::STRING]);
        $ownerSchema->type = Schema::OBJECT;
        $ownerSchema->setFromRef('#/$defs/txtI');
    }

    /**
     * @param string $value
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */
}