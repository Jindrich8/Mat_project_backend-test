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
 * Built from #/$defs/cmb
 */
class DefsCmb extends ClassStructure
{
    /** @var string */
    public $userValue;

    /** @var string */
    public $correctValue;

    /**
     * @param Properties|static $properties
     * @param Schema $ownerSchema
     */
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $properties->userValue = Schema::string();
        $properties->correctValue = (new Schema())->setType([Schema::STRING]);
        $ownerSchema->type = Schema::OBJECT;
        $ownerSchema->required = array(
            self::names()->userValue,
        );
        $ownerSchema->setFromRef('#/$defs/cmb');
    }

    /**
     * @param string $userValue
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setUserValue($userValue)
    {
        $this->userValue = $userValue;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */

    /**
     * @param string $correctValue
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setCorrectValue($correctValue)
    {
        $this->correctValue = $correctValue;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */
}