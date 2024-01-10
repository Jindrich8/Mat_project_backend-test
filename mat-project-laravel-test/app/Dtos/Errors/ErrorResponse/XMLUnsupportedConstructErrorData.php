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
 * XML: Unsupported construct error data
 * Serves as error action specific data.
 */
class XMLUnsupportedConstructErrorData extends ClassStructure
{
    /** @var int Column of unsupported construct */
    public $column;

    /** @var int Line of unsupported construct */
    public $line;

    /** @var int Byte index of unsupported construct */
    public $byteIndex;

    /** @var string[]|array */
    public $supportedConstructs;

    /**
     * @param Properties|static $properties
     * @param Schema $ownerSchema
     */
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $properties->column = Schema::integer();
        $properties->column->title = "Column";
        $properties->column->description = "Column of unsupported construct";
        $properties->column->minimum = 0;
        $properties->line = Schema::integer();
        $properties->line->title = "Line";
        $properties->line->description = "Line of unsupported construct";
        $properties->line->minimum = 0;
        $properties->byteIndex = Schema::integer();
        $properties->byteIndex->title = "Byte index";
        $properties->byteIndex->description = "Byte index of unsupported construct";
        $properties->byteIndex->minimum = 0;
        $properties->supportedConstructs = Schema::arr();
        $properties->supportedConstructs->items = Schema::string();
        $properties->supportedConstructs->items->minItems = 1;
        $properties->supportedConstructs->title = "Supported constructs";
        $ownerSchema->type = Schema::OBJECT;
        $ownerSchema->title = "XML: Unsupported construct error data";
        $ownerSchema->description = "Serves as error action specific data.";
        $ownerSchema->required = array(
            self::names()->column,
            self::names()->line,
            self::names()->byteIndex,
            self::names()->supportedConstructs,
        );
    }

    /**
     * @param int $column Column of unsupported construct
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
     * @param int $line Line of unsupported construct
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
     * @param int $byteIndex Byte index of unsupported construct
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
     * @param string[]|array $supportedConstructs
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setSupportedConstructs($supportedConstructs)
    {
        $this->supportedConstructs = $supportedConstructs;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */
}