<?php
/**
 * @file ATTENTION!!! The code below was carefully crafted by a mean machine.
 * Please consider to NOT put any emotional human-generated modifications as the splendid AI will throw them away with no mercy.
 */

namespace App\Dtos\Task\Create\ErrorResponse;

use Swaggest\JsonSchema\Constraint\Properties;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\Structure\ClassStructure;


class ErrorAllOf1OneOf0ErrorData extends ClassStructure
{
    /** @var int Column of invalid element */
    public $eColumn;

    /** @var int Line of invalid element */
    public $eLine;

    /** @var int Byte index of invalid element */
    public $eByteIndex;

    /** @var string[]|array Expected valid elements at this position */
    public $expectedElements;

    /**
     * @param Properties|static $properties
     * @param Schema $ownerSchema
     */
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $properties->eColumn = Schema::integer();
        $properties->eColumn->title = "Element column";
        $properties->eColumn->description = "Column of invalid element";
        $properties->eColumn->minimum = 0;
        $properties->eLine = Schema::integer();
        $properties->eLine->title = "Element line";
        $properties->eLine->description = "Line of invalid element";
        $properties->eLine->minimum = 0;
        $properties->eByteIndex = Schema::integer();
        $properties->eByteIndex->title = "Element byte index";
        $properties->eByteIndex->description = "Byte index of invalid element";
        $properties->eByteIndex->minimum = 0;
        $properties->expectedElements = Schema::arr();
        $properties->expectedElements->items = Schema::string();
        $properties->expectedElements->title = "Expected elements";
        $properties->expectedElements->description = "Expected valid elements at this position";
        $properties->expectedElements->minItems = 1;
        $ownerSchema->type = Schema::OBJECT;
        $ownerSchema->required = array(
            self::names()->eColumn,
            self::names()->eLine,
            self::names()->expectedElements,
        );
    }

    /**
     * @param int $eColumn Column of invalid element
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setEColumn($eColumn)
    {
        $this->eColumn = $eColumn;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */

    /**
     * @param int $eLine Line of invalid element
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setELine($eLine)
    {
        $this->eLine = $eLine;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */

    /**
     * @param int $eByteIndex Byte index of invalid element
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setEByteIndex($eByteIndex)
    {
        $this->eByteIndex = $eByteIndex;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */

    /**
     * @param string[]|array $expectedElements Expected valid elements at this position
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setExpectedElements($expectedElements)
    {
        $this->expectedElements = $expectedElements;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */
}