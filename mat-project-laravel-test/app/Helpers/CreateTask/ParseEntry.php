<?php

namespace App\Helpers\CreateTask {

    use App\Dtos\Defs\Errors\XML\XMLSyntaxErrorErrorData as XMLXMLSyntaxErrorErrorData;
    use App\Dtos\Defs\Errors\XML\XMLUnsupportedConstructErrorData as XMLXMLUnsupportedConstructErrorData;
    use App\Exceptions\ApplicationException;
    use App\Exceptions\InternalException;
    use App\Exceptions\XMLInvalidAttributeException;
    use App\Exceptions\XMLInvalidElementException;
    use App\Exceptions\XMLMissingRequiredAttributesException;
    use App\Exceptions\XMLSyntaxErrorException;
    use App\Exceptions\XMLUnsupportedConstructException;
    use App\Helpers\CreateTask\Document\Document;
    use App\Types\StopWatchTimer;
    use App\Types\XML\BaseXMLParser;
    use App\Types\XML\LibXmlParser;
    use App\Types\XML\XMLNodeBase;
    use App\Types\XML\XMLContext;
    use App\Types\XML\XMLParserEventsInterface;
    use App\Types\XML\XMLUnsupportedConstructTypeEnum;
    use App\Utils\DebugUtils;
    use App\Utils\DebugLogger;
    use App\Utils\DtoUtils;
    use Exception;

    class ParseEntry implements XMLParserEventsInterface
    {
        private ?XMLNodeBase $node;
        private ?XMLContext $context;
        private bool $start;

        /**
         * @var string[] $supportedConstructs
         */
        private array $supportedConstructs = ["element", "attribute", "comment", "element value"];



        public function __construct()
        {
            $this->context = null;
            $this->node = Document::create();
            $this->start = true;
        }

        /**
         * @throws XMLInvalidElementException
         * @throws XMLInvalidAttributeException
         * @throws XMLMissingRequiredAttributesException
         */
        public function elementStartHandler(BaseXMLParser $parser, string $name, array $attributes): void
        {
            // dump("START - ".$this->node->getName()." -> $name");
            if (!$this->start) {
                $this->node = $this->node->getChild($name, $this->context);
            } else {
                $this->start = false;
            }

            $this->node->validateStart(
                name: $name,
                attributes: $attributes,
                context: $this->context
            );
        }

        public function elementEndHandler(BaseXMLParser $parser, string $name): void
        {
            $node = $this->node->validateAndMoveUp($this->context);
            // dump("END - ".$name." -> ".$node?->getName());
            //            if(!$node){
            //                // dump("Node '".$this->node->getName()."' does not have parent node");
            //                // dump($this->node);
            //            }
            $this->node = $node;
        }

        public function elementValueHandler(BaseXMLParser $parser, string $data): void
        {
            if ($this->node === null) {
                //                if(StrUtils::trimWhites($data,TrimTypeEnum::TRIM_BOTH)){
                //                    // dump("WTF: $data");
                //                }
                $parser->getPos($column, $line, $byteIndex);
                // dump(":$line, $column, $byteIndex");
            } else {
                $this->node->appendValuePart(value: $data, context: $this->context);
            }
        }

        /**
         * @throws XMLUnsupportedConstructException
         */
        public function unsupportedConstructHandler(BaseXMLParser $parser, mixed $data, XMLUnsupportedConstructTypeEnum $type): void
        {
            $name = match ($type) {
                XMLUnsupportedConstructTypeEnum::EXTERNAL_ENTITY_REFERENCE => 'external entity reference',
                XMLUnsupportedConstructTypeEnum::NOTATION_DECLARATION => 'notation declaration',
                XMLUnsupportedConstructTypeEnum::PROCESSING_INSTRUCTION => 'processing instruction',
                XMLUnsupportedConstructTypeEnum::START_NAMESPACE_DECLARATION => 'start namespace declaration',
                XMLUnsupportedConstructTypeEnum::UNPARSED_ENTITY_DECLARATION => 'unparsed entity declaration',
                XMLUnsupportedConstructTypeEnum::UNKNOWN_CONSTRUCT => 'unknown construct'
            };
            $this->unsupportedConstruct($parser, $name);
        }

        public function commentHandler(BaseXMLParser $parser, string $data): void
        {
        }

        /**
         * @param iterable<string> $data
         * @return TaskRes
         * @throws Exception
         */
        public function parse(iterable $data): TaskRes
        {
            return StopWatchTimer::run(self::class . "::parse", function () use ($data) {
                $parser = null;
                try {
                    $parser = LibXmlParser::create($this);
                    $this->context = new XMLContext($parser, new TaskRes());

                    $error = null;
                    // Parse

                    foreach ($data as $dataPart) {
                        if (($error = $parser->parse($dataPart))) {
                            break;
                        }
                    }
                    if ($error || ($error = $parser->parse("", isFinal: true))) {
                        $e = new XMLSyntaxErrorException(
                            description: $error->errorMessage,
                            errorData: XMLXMLSyntaxErrorErrorData::create()
                                ->setLine($error->line)
                                ->setColumn($error->column)
                                ->setByteIndex($error->byteIndex)
                        );
                        throw $e;
                    }
                } finally {
                    DebugLogger::log("Parser free");
                    // Free parser
                    $parser?->free();
                }
                return $this->context->getTaskRes();
            });
        }

        /**
         * @throws XMLUnsupportedConstructException
         */
        private function unsupportedConstruct(BaseXMLParser $parser, string $constructName): void
        {

            $errorData = XMLXMLUnsupportedConstructErrorData::create();

            $parser
                ->getPos(
                    column: $errorData->column,
                    line: $errorData->line,
                    byteIndex: $errorData->byteIndex
                );

            throw new XMLUnsupportedConstructException(
                constructName: $constructName,
                errorData: $errorData
                    ->setSupportedConstructs($this->supportedConstructs)
            );
        }
    }
}
