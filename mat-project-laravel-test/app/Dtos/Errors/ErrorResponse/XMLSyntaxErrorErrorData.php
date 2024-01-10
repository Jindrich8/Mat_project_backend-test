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
 * XML: Syntax error error data
 * Serves as error action specific data.
 */
class XMLSyntaxErrorErrorData extends ClassStructure
{
    /** @var int Column of syntax error */
    public $column;

    /** @var int Line of syntax error */
    public $line;

    /** @var int Byte index of syntax error */
    public $byteIndex;

    /**
     * @param Properties|static $properties
     * @param Schema $ownerSchema
     */
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $properties->column = Schema::integer();
        $properties->column->title = "Error column";
        $properties->column->description = "Column of syntax error";
        $properties->column->minimum = 0;
        $properties->line = Schema::integer();
        $properties->line->title = "Error line";
        $properties->line->description = "Line of syntax error";
        $properties->line->minimum = 0;
        $properties->byteIndex = Schema::integer();
        $properties->byteIndex->title = "Error byte index";
        $properties->byteIndex->description = "Byte index of syntax error";
        $properties->byteIndex->minimum = 0;
        $ownerSchema->type = Schema::OBJECT;
        $ownerSchema->title = "XML: Syntax error error data";
        $ownerSchema->description = "Serves as error action specific data.";
        $ownerSchema->required = array(
            self::names()->column,
            self::names()->line,
            self::names()->byteIndex,
        );
    }

    /**
     * @param int $column Column of syntax error
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
     * @param int $line Line of syntax error
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
     * @param int $byteIndex Byte index of syntax error
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setByteIndex($byteIndex)
    {
        $this->byteIndex = $byteIndex;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */
}