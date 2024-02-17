<?php

namespace App\Helpers\CreateTask {

    use App\Dtos\Defs\Errors\XML\XMLSyntaxErrorErrorData as XMLXMLSyntaxErrorErrorData;
    use App\Dtos\Defs\Errors\XML\XMLUnsupportedConstructErrorData as XMLXMLUnsupportedConstructErrorData;
    use App\Dtos\Errors\ErrorResponse\BytePositionForGivenLineAndColumn;
    use App\Dtos\Errors\ErrorResponse\ErrorResponse;
    use App\Dtos\Errors\ErrorResponse\HintPosition;
    use App\Dtos\Errors\ErrorResponse\XMLInvalidElementErrorData;
    use App\Dtos\Errors\ErrorResponse\XMLSyntaxErrorErrorData;
    use App\Dtos\Errors\ErrorResponse\XMLUnsupportedConstructErrorData;
    use App\Dtos\Errors\XML\InvalidElement\InvalidElement;
    use App\Exceptions\ApplicationException;
    use App\Exceptions\InternalException;
    use App\Exceptions\InvalidArgumentException;
    use App\Exceptions\XMLInvalidElementException;
    use App\Exceptions\XMLParsingException;
    use App\Exceptions\XMLSyntaxErrorException;
    use App\Exceptions\XMLUnsupportedConstructException;
    use App\Helpers\CreateTask\Document\Document;
    use App\Helpers\CreateTask\TaskRes;
    use App\Types\BaseXMLParser;
    use App\Types\LibXmlParser;
    use App\Types\TrimType;
    use App\Types\XMLNodeBase;
    use App\Types\XMLContext;
    use App\Types\XMLContextBase;
    use App\Types\XMLParserAccuratePosTracker;
    use App\Types\XMLParserEvents;
    use App\Types\XMLUnsupportedConstructType;
    use App\Utils\DebugUtils;
    use App\Utils\DtoUtils;
    use App\Utils\StrUtils;
    use Exception;
    use Illuminate\Support\Str;
    use XMLParser;

    class ParseEntry implements XMLParserEvents
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
            report(new InternalException('ParseEntry constructor'));
            $this->context = null;
            report(new InternalException('Document::create'));
            $this->node = Document::create();
            report(new InternalException('Document::created'));
            $this->start = true;
            report(new InternalException('ParseEntry constructor end'));
        }

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
            if(!$node){
                // dump("Node '".$this->node->getName()."' does not have parent node");
                // dump($this->node);
            }
            $this->node = $node;
        }

        public function elementValueHandler(BaseXMLParser $parser, string $data): void
        {
            if($this->node === null){
                if(StrUtils::trimWhites($data,TrimType::TRIM_BOTH)){
                    // dump("WTF: $data");
                }
                $parser->getPos($column,$line,$byteIndex);
                // dump(":$line, $column, $byteIndex");
            }
            else{
            $this->node->appendValue(value: $data, context: $this->context);
            }
        }

        public function unsupportedConstructHandler(BaseXMLParser $parser, mixed $data, XMLUnsupportedConstructType $type): void
        {
           $name = match($type){
                XMLUnsupportedConstructType::EXTERNAL_ENTITY_REFERENCE => 'external entity reference',
                XMLUnsupportedConstructType::NOTATION_DECLARATION => 'notation declaration',
                XMLUnsupportedConstructType::PROCESSING_INSTRUCTION => 'processing instruction',
                XMLUnsupportedConstructType::START_NAMESPACE_DECLARATION => 'start namespace declaration',
                XMLUnsupportedConstructType::UNPARSED_ENTITY_DECLARATION => 'unparsed entity declaration',
                XMLUnsupportedConstructType::UNKNOWN_CONSTRUCT => 'unknown construct'
            };
            $this->unsupportedConstruct($parser,$name);
        }

       public function commentHandler(BaseXMLParser $parser, string $data): void
       {
        
       }

        /**
         * @param iterable<string> $data
         * @return TaskRes
         */
        public function parse(iterable $data): TaskRes
        {
            $parser = null;
            try {
                $parser = LibXmlParser::create($this);
                $this->context = new XMLContext($parser,new TaskRes());
                
                $error = null;
                // Parse
                
                foreach ($data as $dataPart) {
                   if(($error = $parser->parse($dataPart))){
                    break;
                   }
                }
                if($error || ($error = $parser->parse("",isFinal:true))){
                    $e = new XMLSyntaxErrorException(
                        description:$error->errorMessage,
                        errorData:XMLXMLSyntaxErrorErrorData::create()
                        ->setLine($error->line)
                        ->setColumn($error->column)
                        ->setByteIndex($error->byteIndex)
                    );
                    throw $e;
                }
            } catch (ApplicationException $e) {
                // TODO: REMOVE THIS catch
                $errorResponse = $e->getErrorResponse();
                DebugUtils::log("APP ERROR",
                fn()=>DtoUtils::dtoToJson($errorResponse,otherJsonOptions:JSON_PRETTY_PRINT)
            );
                $errorData = $errorResponse->error->details?->errorData;
                if ($errorData) {
                    $byteIndex = is_array($errorData) ? ($errorData['byteIndex'] ?? $errorData['eByteIndex'] ?? false) : ($errorData->{'byteIndex'} ?? $errorData->{'eByteIndex'} ?? false);
                    if (is_int($byteIndex) && $byteIndex >= 0) {
                        DebugUtils::log("CHAR AT BYTE INDEX",
                        fn()=>[
                            'char' => substr(implode("", $data), $byteIndex, 1),
                            'byteIndex' => $byteIndex
                        ]);
                    }
                    $byteLength =  is_array($errorData) ? ($errorData['byteLength'] ?? false) : ($errorData->{'byteLength'} ?? false);
                    if(is_int($byteLength)){
                        DebugUtils::log("STRING AT BYTE INDEX",
                        fn()=>[
                            'str' => substr(implode("", $data), $byteIndex, $byteLength),
                            'byteIndex' => $byteIndex
                        ]);
                    }
                }
                throw new Exception(previous: $e);
            } catch (InternalException $e) {
                DebugUtils::log("INTERNAL ERROR",
                fn()=> DebugUtils::jsonEncode([
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'context' => $e->context()
                ]));
                throw new Exception(previous: $e);
            } finally {
                DebugUtils::log("Parser free");
                // Free parser
                $parser?->free();
            }
            return $this->context->getTaskRes();
        }

        private function unsupportedConstruct(BaseXMLParser $parser,string $constructName): void
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
