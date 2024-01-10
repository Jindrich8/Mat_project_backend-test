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
 * Built from C:/Users/Jindra/source/repos/JS/Mat_project_backend-test/mat-project-laravel-test/schemas/defs/exercises/Doplnovacka/review_response.json
 */
class DoplnovackaReviewResponse extends ClassStructure
{
    /** @var string */
    public $exerType;

    /** @var DefsCmb[]|DefsTxtI[]|string[]|array */
    public $content;

    /**
     * @return static
     */
    public static function create()
    {
        $instance = parent::create();
        $instance->exerType = "Doplnovacka";
        return $instance;
    }

    /**
     * @param Properties|static $properties
     * @param Schema $ownerSchema
     */
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $properties->exerType = Schema::string();
        $properties->exerType->const = "Doplnovacka";
        $properties->content = Schema::arr();
        $properties->content->items = new Schema();
        $properties->content->items->oneOf[0] = DefsCmb::schema();
        $properties->content->items->oneOf[1] = DefsTxtI::schema();
        $propertiesContentItemsOneOf2 = Schema::string();
        $propertiesContentItemsOneOf2->setFromRef('#/$defs/value');
        $properties->content->items->oneOf[2] = $propertiesContentItemsOneOf2;
        $properties->content->items->minItems = 1;
        $properties->content->items->contains = (object)[
            'oneOf' => [
                (object)[
                    '$ref' => '#/$defs/cmb',
                ],
                (object)[
                    '$ref' => '#/$defs/txtI',
                ],
            ],
        ];
        $properties->content->items->defs = (object)[
            'cmb' => (object)[
                'required' => [
                    'userValue',
                ],
                'properties' => (object)[
                    'userValue' => (object)[
                        'type' => 'string',
                    ],
                    'correctValue' => (object)[
                        'type' => [
                            'string',
                        ],
                    ],
                ],
                'type' => 'object',
            ],
            'txtI' => (object)[
                'properties' => (object)[
                    'value' => (object)[
                        'type' => [
                            'string',
                        ],
                    ],
                ],
                'type' => 'object',
            ],
            'value' => (object)[
                'type' => 'string',
            ],
        ];
        $properties->content->defs = (object)[
            'cmb' => (object)[
                'required' => [
                    'userValue',
                ],
                'properties' => (object)[
                    'userValue' => (object)[
                        'type' => 'string',
                    ],
                    'correctValue' => (object)[
                        'type' => [
                            'string',
                        ],
                    ],
                ],
                'type' => 'object',
            ],
            'txtI' => (object)[
                'properties' => (object)[
                    'value' => (object)[
                        'type' => [
                            'string',
                        ],
                    ],
                ],
                'type' => 'object',
            ],
            'value' => (object)[
                'type' => 'string',
            ],
        ];
        $ownerSchema->type = Schema::OBJECT;
        $ownerSchema->schema = "http://json-schema.org/draft-07/schema#";
        $ownerSchema->required = array(
            self::names()->exerType,
            self::names()->content,
        );
        $ownerSchema->defs = (object)[
            'value' => (object)[
                'type' => 'string',
            ],
            'txtI' => (object)[
                'properties' => (object)[
                    'value' => (object)[
                        'type' => [
                            'string',
                        ],
                    ],
                ],
                'type' => 'object',
            ],
            'cmb' => (object)[
                'required' => [
                    'userValue',
                ],
                'properties' => (object)[
                    'userValue' => (object)[
                        'type' => 'string',
                    ],
                    'correctValue' => (object)[
                        'type' => [
                            'string',
                        ],
                    ],
                ],
                'type' => 'object',
            ],
        ];
        $ownerSchema->setFromRef('C:/Users/Jindra/source/repos/JS/Mat_project_backend-test/mat-project-laravel-test/schemas/defs/exercises/Doplnovacka/review_response.json');
    }

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
     * @param DefsCmb[]|DefsTxtI[]|string[]|array $content
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