<?php
/**
 * @file ATTENTION!!! The code below was carefully crafted by a mean machine.
 * Please consider to NOT put any emotional human-generated modifications as the splendid AI will throw them away with no mercy.
 */

namespace App\Dtos\Errors\ErrorResponse;

use Swaggest\JsonSchema\Constraint\Properties;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\Structure\ClassStructure;


class XMLInvalidAttributeErrorData extends ClassStructure
{
    /** @var string Name of invalid attribute */
    public $invalidAttribute;

    /** @var int Column of element with invalid attribute */
    public $eColumn;

    /** @var int Line of element with invalid attribute */
    public $eLine;

    /** @var int Byte index of element with invalid attribute */
    public $eByteIndex;

    /** @var string[]|array Expected valid attributes at this position */
    public $expectedAttributes;

    /**
     * @param Properties|static $properties
     * @param Schema $ownerSchema
     */
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $properties->invalidAttribute = Schema::string();
        $properties->invalidAttribute->title = "Invalid attribute";
        $properties->invalidAttribute->description = "Name of invalid attribute";
        $properties->invalidAttribute->minLength = 1;
        $properties->eColumn = Schema::integer();
        $properties->eColumn->title = "Element column";
        $properties->eColumn->description = "Column of element with invalid attribute";
        $properties->eColumn->minimum = 0;
        $properties->eLine = Schema::integer();
        $properties->eLine->title = "Element line";
        $properties->eLine->description = "Line of element with invalid attribute";
        $properties->eLine->minimum = 0;
        $properties->eByteIndex = Schema::integer();
        $properties->eByteIndex->title = "Element byte index";
        $properties->eByteIndex->description = "Byte index of element with invalid attribute";
        $properties->eByteIndex->minimum = 0;
        $properties->expectedAttributes = Schema::arr();
        $properties->expectedAttributes->items = Schema::string();
        $properties->expectedAttributes->title = "Expected attributes";
        $properties->expectedAttributes->description = "Expected valid attributes at this position";
        $properties->expectedAttributes->minItems = 1;
        $ownerSchema->type = Schema::OBJECT;
        $ownerSchema->required = array(
            self::names()->invalidAttribute,
            self::names()->eColumn,
            self::names()->eLine,
            self::names()->eByteIndex,
            self::names()->expectedAttributes,
        );
    }

    /**
     * @param string $invalidAttribute Name of invalid attribute
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setInvalidAttribute($invalidAttribute)
    {
        $this->invalidAttribute = $invalidAttribute;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */

    /**
     * @param int $eColumn Column of element with invalid attribute
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
     * @param int $eLine Line of element with invalid attribute
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
     * @param int $eByteIndex Byte index of element with invalid attribute
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
     * @param string[]|array $expectedAttributes Expected valid attributes at this position
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setExpectedAttributes($expectedAttributes)
    {
        $this->expectedAttributes = $expectedAttributes;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */
}