<?php
/**
 * @file ATTENTION!!! The code below was carefully crafted by a mean machine.
 * Please consider to NOT put any emotional human-generated modifications as the splendid AI will throw them away with no mercy.
 */

namespace App\Dtos\Task\Evaluate\Response;

use Swaggest\JsonSchema\Constraint\Properties;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\Structure\ClassStructure;


class DefsExerciseAllOf0 extends ClassStructure
{
    /** @var mixed */
    public $type;

    /** @var string */
    public $exerType;

    /** @var DefsExerciseAllOf0Instructions */
    public $instructions;

    /** @var mixed */
    public $content;

    /**
     * @param Properties|static $properties
     * @param Schema $ownerSchema
     */
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $properties->type = new Schema();
        $properties->type->const = "exercise";
        $properties->exerType = Schema::string();
        $properties->instructions = DefsExerciseAllOf0Instructions::schema();
        $properties->content = Schema::object();
        $ownerSchema->type = Schema::OBJECT;
        $ownerSchema->required = array(
            self::names()->exerType,
            self::names()->instructions,
            self::names()->content,
        );
    }

    /**
     * @param mixed $type
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */

    /**
     * @param string $exerType
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setExerType($exerType)
    {
        $this->exerType = $exerType;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */

    /**
     * @param DefsExerciseAllOf0Instructions $instructions
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setInstructions(DefsExerciseAllOf0Instructions $instructions)
    {
        $this->instructions = $instructions;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */

    /**
     * @param mixed $content
     * @return $this
     * @codeCoverageIgnoreStart
     */
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }
    /** @codeCoverageIgnoreEnd */
}