<?php
/**
 * @file ATTENTION!!! The code below was carefully crafted by a mean machine.
 * Please consider to NOT put any emotional human-generated modifications as the splendid AI will throw them away with no mercy.
 */

namespace App\Dtos\Errors\ErrorResponse;

use Swaggest\JsonSchema\Constraint\Properties;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\Structure\ClassStructure;


/**
 * XML: Invalid element value error data
 * Serves as error action specific data.
 */
class XMLInvalidElementValueErrorData extends ClassStructure
{
    /** @var int Column of element with invalid value. */
    public $eColumn;

    /** @var int Line of element with invalid value. */
    public $eLine;

    /** @var int Byte index of element with invalid value. */
    public $eByteIndex;

    /**
     * @param Properties|static $properties
     * @param Schema $ownerSchema
     */
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $properties->eColumn = Schema::integer();
        $properties->eColumn->title = "Element column";
        $properties->eColumn->description = "Column of element with invalid value.";
        $properties->eColumn->minimum = 0;
        $properties->eLine = Schema::integer();
        $properties->eLine->title = "Element line";
        $properties->eLine->description = "Line of element with invalid value.";
        $properties->eLine->minimum = 0;
        $properties->eByteIndex = Schema::integer();
        $properties->eByteIndex->title = "Element byte index";
        $properties->eByteIndex->description = "Byte index of element with invalid value.";
        $properties->eByteIndex->minimum = 0;
        $ownerSchema->type = Schema::OBJECT;
        $ownerSchema->title = "XML: Invalid element value error data";
        $ownerSchema->description = "Serves as error action specific data.";
        $ownerSchema->required = array(
            self::names()->eColumn,
            self::names()->eLine,
            self::names()->eByteIndex,
        );
    }

    /**
     * @param int $eColumn Column of element with invalid value.
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
     * @param int $eLine Line of element with invalid value.
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
     * @param int $eByteIndex Byte index of element with invalid value.
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setEByteIndex($eByteIndex)
    {
        $this->eByteIndex = $eByteIndex;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */
}