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
 * XML: Invalid element value part error data
 * Serves as error action specific data.
 */
class XMLInvalidElementValuePartErrorData extends ClassStructure
{
    /** @var int Column of invalid element value part */
    public $column;

    /** @var int Line of invalid element value part */
    public $line;

    /** @var int Byte index of element value part */
    public $byteIndex;

    /** @var int Length in bytes of invalid part of element value */
    public $byteLength;

    /**
     * @param Properties|static $properties
     * @param Schema $ownerSchema
     */
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $properties->column = Schema::integer();
        $properties->column->title = "Value column";
        $properties->column->description = "Column of invalid element value part";
        $properties->column->minimum = 0;
        $properties->line = Schema::integer();
        $properties->line->title = "Value line";
        $properties->line->description = "Line of invalid element value part";
        $properties->line->minimum = 0;
        $properties->byteIndex = Schema::integer();
        $properties->byteIndex->title = "Value byte index";
        $properties->byteIndex->description = "Byte index of element value part";
        $properties->byteIndex->minimum = 0;
        $properties->byteLength = Schema::integer();
        $properties->byteLength->title = "Invalid part byte length";
        $properties->byteLength->description = "Length in bytes of invalid part of element value";
        $properties->byteLength->minimum = 1;
        $ownerSchema->type = Schema::OBJECT;
        $ownerSchema->title = "XML: Invalid element value part error data";
        $ownerSchema->description = "Serves as error action specific data.";
        $ownerSchema->required = array(
            self::names()->column,
            self::names()->line,
            self::names()->byteIndex,
            self::names()->byteLength,
        );
    }

    /**
     * @param int $column Column of invalid element value part
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setColumn($column)
    {
        $this->column = $column;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */

    /**
     * @param int $line Line of invalid element value part
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setLine($line)
    {
        $this->line = $line;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */

    /**
     * @param int $byteIndex Byte index of element value part
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setByteIndex($byteIndex)
    {
        $this->byteIndex = $byteIndex;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */

    /**
     * @param int $byteLength Length in bytes of invalid part of element value
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setByteLength($byteLength)
    {
        $this->byteLength = $byteLength;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */
}