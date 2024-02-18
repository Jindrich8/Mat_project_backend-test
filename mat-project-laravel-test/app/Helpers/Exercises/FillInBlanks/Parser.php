<?php

namespace App\Helpers\Exercises\FillInBlanks {

    use App\Dtos\InternalTypes\Combobox;
    use App\Dtos\InternalTypes\FillInBlanksContent;
    use App\Dtos\InternalTypes\TextInput;
    use App\Exceptions\InternalException;
    use App\Exceptions\XMLInvalidElementValueException;
    use App\Exceptions\XMLInvalidElementValuePartException;
    use App\MyConfigs\TaskSrcConfig;
    use App\Types\TrimType;
    use App\Types\XMLContextBase;
    use App\Types\XMLDynamicNodeBase;
    use App\Types\XMLValidParserPosition;
    use App\Utils\StrUtils;
    use App\Utils\Utils;

    enum State
    {
        case CMP;
        case TEXT;
    }

    abstract class Parser extends XMLDynamicNodeBase
    {
        private State $state;
        /**
         * @var array<string,int> $tokens
         */
        private array $tokens;

        protected ?FillInBlanksContent $content;
        private bool $hasUiCmp;
        private bool $trimming;

        /**
         * @var string $prevToken
         * Empty string means no token
         */
        private string $prevToken;
        private string $prevText;
        private XMLValidParserPosition $prevTokenPos;
        private XMLValidParserPosition $prevNotEscTokenPos;
        private bool $escapeNextChar;
        private XMLValidParserPosition $prevCmpStartTokenPos;

        private string $cmpStartToken;
        private string $cmpEndToken;
        private string $cmbValuesSepToken;
        private string $escapeToken;

        /**
         * @var array<string,string> $cmbValues
         */
        private array $cmbValues;
        private ?string $cmbSelectedValue;

        public function reset(): void
        {
            parent::reset();
            $config = TaskSrcConfig::get()->getFillInBlanksConfig();
            $this->cmpStartToken = $config->uiCmpStart;
            $this->cmpEndToken = $config->uiCmpEnd;
            $this->cmbValuesSepToken = $config->cmbValuesSep;
            $this->escapeToken = $config->escape;

            $this->tokens = [
                $this->escapeToken => $this->escapeToken,
                $this->cmpStartToken => $this->cmpStartToken,
                $this->cmpEndToken => $this->cmpEndToken
            ];
            $this->state = State::TEXT;
            $this->prevToken = "";
            $this->prevText = "";
            $this->prevTokenPos = new XMLValidParserPosition();
            $this->prevNotEscTokenPos = new XMLValidParserPosition();
            $this->prevCmpStartTokenPos = new XMLValidParserPosition();
            $this->cmbValues = [];
            $this->cmbSelectedValue = null;
            $this->content = null;
            $this->hasUiCmp = false;
            $this->trimming = true;
            $this->escapeNextChar = false;
        }

        protected function getContent(): ?FillInBlanksContent
        {
            if(!$this->content){
                $this->throwInternalException(
                    message:"Content should not be null!"
                );
            }
            return $this->content;
        }

        public function validateStart(iterable $attributes, XMLContextBase $context, ?string $name = null): void
        {
            parent::validateStart($attributes,$context,$name);
            $this->state = State::TEXT;
            $this->hasUiCmp = false;
            $this->trimming = true;
            $this->escapeNextChar = false;
            $this->prevTokenPos->setPos(1,1,0);
            $this->prevNotEscTokenPos->setPos(1,1,0);
        }

        /**
         * @throws XMLInvalidElementValuePartException
         */
        public function appendValue(string $value, XMLContextBase $context): void
        {
            if($this->trimming){
               $value = StrUtils::trimWhites($value,TrimType::TRIM_START);
               if(!$value){
                return;
               }
               $this->trimming = false;
            }
            $this->parse($value,$context);
        }


        /**
         * @throws XMLInvalidElementValueException|XMLInvalidElementValuePartException
         */
        protected function validate(XMLContextBase $context): void
        {
            parent::validate($context);
            $this->prevText = StrUtils::trimWhites($this->prevText,TrimType::TRIM_END);
            if ($this->prevText) {
                if($this->state !== State::TEXT){
                    $context->getPos(
                        column:$column,
                        line:$line,
                        byteIndex:$byteIndex
                    );
                    $this->prevCmpStartTokenPos->getPos(
                        column:$prevColumn,
                        line:$prevLine,
                        byteIndex:$prevByteIndex
                    );
                    $byteLength = $byteIndex - $prevByteIndex;

                    $this->missingCmpEndToken(
                    column:$prevColumn,
                    line:$prevLine,
                    byteIndex:$prevByteIndex,
                    byteLength:$byteLength
                );
                }
                $this->addItemToContent($this->prevText);
            }
            if (!$this->content->content) {
                $this->valueShouldNotBeEmpty(
                    description: "Fill in blanks exercise content should not be empty"
                );
            }
            if (!$this->hasUiCmp) {
                $this->invalidValue(
                    description: "Fill in blanks exercise content must contain at least one fillable component (combobox or text input)"
                );
            }
            $this->reset();
        }


        private function changeState(State $newState): void
        {
            if ($newState === $this->state) {
                return;
            }
            if ($newState === State::CMP) {
                $this->tokens[$this->cmbValuesSepToken] = $this->cmbValuesSepToken;
            } else {
                unset($this->tokens[$this->cmbValuesSepToken]);
            }
            $this->cmbSelectedValue = null;
            $this->cmbValues = [];
            $this->state = $newState;
        }

        private function getAndResetPrevText(): string
        {
            $prevText = $this->prevText;
            $this->prevText = "";
            return $prevText;
        }

        private function addItemToContent(string|Combobox|TextInput $item): void
        {
            if (!is_string($item)) {
                $this->hasUiCmp = true;
            } else if (!$item) {
                // empty string
                return;
            }
            $this->content->content ??= [];
            $this->content->content[] = $item;
        }

        private function currentCmpIsCombobox(): bool
        {
            return (bool)$this->cmbValues;
        }

        /**
         * @throws XMLInvalidElementValuePartException
         */
        private function addComboboxOptionWithoutPrevText(string $text, int $endByteIndex): void
        {
            $prevText = $this->getAndResetPrevText();
            $option = $prevText . $text;
            if (!$this->cmbValues) {
                $this->cmbSelectedValue = $option;
            } else if (Utils::arrayHasKey($this->cmbValues, $option)) {
                $this->prevNotEscTokenPos->getPos(
                    column: $column,
                    line: $line,
                    byteIndex: $byteIndex
                );
                ++$column;
                $byteIndex += strlen($this->prevToken);
                /* Prev token can be from previous append value call and
                 there can be extra characters between these two calls,
                    so we need to subtract byte position of prev text start from
                    end byte text position to get real byte length
                    */
                $byteLength = $endByteIndex - $byteIndex;
                $this->duplicateComboboxOption(
                    $option,
                    column: $column,
                    line: $line,
                    byteIndex: $byteIndex,
                    byteLength: $byteLength
                );
            }
            $this->cmbValues[$option] = $option;
        }

        private function addCurrentComboboxToContent(): void
        {
            sort($this->cmbValues,SORT_STRING);
            /**
             * @var int|false $index
             * This needs to be integer, because shuffle transforms underlying array to list
             */
            $index =  array_search($this->cmbSelectedValue, $this->cmbValues);
            if ($index === false) {
                // internal error - cmbSelectedValue not found
                $this->throwInternalException(
                    "SelectedCmbValue was not found!",
                    context: [
                        'cmbValues' => $this->cmbValues,
                        'selectedCmbValue' => $this->cmbSelectedValue,
                    ]
                );
            }
                $this->addItemToContent(Combobox::create()
                ->setSelectedIndex($index)
                ->setValues($this->cmbValues)
        );
            $this->cmbValues = [];
            $this->cmbSelectedValue = null;
        }

        private function addTextInputWithoutPrevTextToContent(string $text, int $endByteIndex): void
        {
            $prevText = $this->getAndResetPrevText();
            $correctText = $prevText . $text;
            $this->addItemToContent(
                TextInput::create()
                    ->setCorrectText($correctText)
            );
        }


        /**
         * @throws XMLInvalidElementValuePartException
         */
        protected function parse(string $input, XMLContextBase $context): void
        {
            $byteOffset = 0;

            $textByteOffset = $byteOffset;
            $context->getPos(
                column: $column,
                line: $line,
                byteIndex: $byteIndex
            );

            if ($this->escapeNextChar && $this->prevToken === $this->escapeToken) {
                $this->escapeNextChar = false;
                $ch = StrUtils::utf8GetCharAtIndex($input, index: 0);
                if (!Utils::arrayHasKey($this->tokens, $ch)) {
                    $this->prevTokenPos->getPos(
                        column:$prevColumn,
                        line:$prevLine,
                        byteIndex:$prevByteIndex
                    );

                    $this->invalidEscapeSequence(
                        ch: $ch,
                        column:$prevColumn,
                        line:$prevLine,
                        byteIndex:$byteIndex,
                        byteLength:$byteIndex-$prevByteIndex+strlen($ch)
                    );
                }
                $byteOffset = strlen($ch);
                ++$column;
            }

            // dump("VALUE: '$input'");
            $token = "";
            for (;; $textByteOffset = $byteOffset + strlen($token)) {
                if ($token) {
                    $this->prevTokenPos->setPos(
                        column: $column,
                        line: $line,
                        byteIndex: $byteIndex + $byteOffset
                    );
                    $byteOffset+=strlen($token);
                    ++$column;
                    $this->prevToken = $token;
                    if($token !== $this->escapeToken){
                        $this->prevNotEscTokenPos
                        ->setPosFromProvider($this->prevTokenPos);
                        if($token === $this->cmpStartToken){
                            $this->prevCmpStartTokenPos
                            ->setPosFromProvider($this->prevTokenPos);
                        }
                    }
                    else{
                        $ch=StrUtils::utf8GetCharAtIndex($input,0,$byteOffset);
                        $byteOffset+=strlen($ch);
                        ++$column;
                    }
                }
                // dump("Byte offset: '$byteOffset'");
                // dump($this->tokens);
                $token = StrUtils::utf8GetFirstSetKeyAndAdvancePos(
                    str: $input,
                    set: $this->tokens,
                    columnPos: $column,
                    linePos: $line,
                    byteOffset: $byteOffset
                );

                // dump(":$line, $column - token: $token");

                if (!$token) {
                    $this->prevText .= substr($input, $textByteOffset);
                    return;
                }
                if ($token === $this->escapeToken) {
                    $nextChar = StrUtils::utf8GetCharAtIndex($input, index: 1, byteOffset: $byteOffset);
                    if (!$nextChar) {
                        $this->escapeNextChar = true;
                        $this->prevText .= substr($input,$textByteOffset);
                        return;
                    }
                    if (!Utils::arrayHasKey($this->tokens, $nextChar)) {
                        $this->invalidEscapeSequence(
                            ch: $nextChar,
                            column: $column,
                            line: $line,
                            byteIndex: $byteIndex + $byteOffset,
                            byteLength:strlen($token)+strlen($nextChar)
                        );
                    }
                    $this->prevText .= StrUtils::substrAsciiBetween($input, $textByteOffset,$byteOffset);
                } else if ($token === $this->cmpStartToken) {
                    if ($this->state === State::CMP) {
                        // invalid open sequence
                        $this->invalidCmpStartToken(
                            column: $column,
                            line: $line,
                            byteIndex: $byteIndex
                        );
                    }
                    $text =$this->getAndResetPrevText()
                     .StrUtils::substrAsciiBetween($input, $textByteOffset, $byteOffset)
                        ;
                    $this->addItemToContent($text);
                    $this->changeState(State::CMP);
                } else if ($token === $this->cmbValuesSepToken) {
                    if ($this->state !== State::CMP) {
                        $this->throwInternalException(
                            message: "State mismatch!\nState should be '" . State::CMP->name . "'.",
                            context: [
                                'input' => $input,
                                'context' => $context,
                                'column' => $column,
                                'line' => $line,
                                'byteIndex' => $byteIndex,
                                'byteOffset' => $byteOffset
                            ]
                        );
                    }

                    $text = StrUtils::substrAsciiBetween($input, $textByteOffset, $byteOffset);
                    // dump("Adding option: prevText:'{$this->prevText}' text: '$text'");
                    $this->addComboboxOptionWithoutPrevText(
                        text: $text,
                        endByteIndex: $byteIndex + $byteOffset
                    );
                } else if ($token === $this->cmpEndToken) {
                    if ($this->state !== State::CMP) {
                        $this->invalidCmpEndToken(
                            column: $column,
                            line: $line,
                            byteIndex: $byteIndex + $byteOffset
                        );
                    }
                    $text = StrUtils::substrAsciiBetween($input, $textByteOffset, $byteOffset);
                    if ($this->currentCmpIsCombobox()) {
                        $this->addComboboxOptionWithoutPrevText(
                            text: $text,
                            endByteIndex: $byteIndex + $byteOffset
                        );
                        $this->addCurrentComboboxToContent();
                    } else {
                        $this->addTextInputWithoutPrevTextToContent(
                            text: $text,
                            endByteIndex: $byteIndex + $byteOffset
                        );
                    }
                    $this->changeState(State::TEXT);
                } else {
                    $this->throwInternalException(
                        message: "Unexpected token '$token'",
                        context: [
                            'input' => $input,
                            'context' => $context,
                            'column' => $column,
                            'line' => $line,
                            'byteIndex' => $byteIndex,
                            'byteOffset' => $byteOffset
                        ]
                    );
                }
            }
        }



        protected function throwInternalException(string $message, array $context = [])
        {
            throw new InternalException(
                message: $message,
                context: ['this' => $this, $context]
            );
        }

        /**
         * @throws XMLInvalidElementValuePartException
         */
        private function missingCmpEndToken(
            int $column,
            int $line,
            int $byteIndex,
            int $byteLength
        ): void
        {
            $this->invalidValuePart(
                column: $column,
                line: $line,
                byteIndex: $byteIndex,
                byteLength: $byteLength,
                description: "Close it with '{$this->cmpEndToken}' character.",
                message: "Unclosed fillable component"
            );
        }

        /**
         * @throws XMLInvalidElementValuePartException
         */
        private function invalidCmpStartToken(
            int $column,
            int $line,
            int $byteIndex
        ): void
        {
            $this->invalidValuePart(
                column: $column,
                line: $line,
                byteIndex: $byteIndex,
                byteLength: strlen($this->cmpStartToken),
                description: "Escape it with '{$this->escapeToken}' character.",
                message: "Invalid open fillable component character '{$this->cmpStartToken}'"
            );
        }

        /**
         * @throws XMLInvalidElementValuePartException
         */
        private function invalidCmpEndToken(
            int $column,
            int $line,
            int $byteIndex
        ): void
        {
            $this->invalidValuePart(
                column: $column,
                line: $line,
                byteIndex: $byteIndex,
                byteLength: strlen($this->cmpStartToken),
                description: "Escape it with '{$this->escapeToken}' character.",
                message: "Invalid close fillable component character '{$this->cmpEndToken}'"
            );
        }

        /**
         * @throws XMLInvalidElementValuePartException
         */
        private function invalidEscapeSequence(
            string $ch,
            int $column,
            int $line,
            int $byteIndex,
            int $byteLength
        ): void
        {
            $this->invalidValuePart(
                column: $column,
                line: $line,
                byteIndex: $byteIndex,
                byteLength: $byteLength,
                description: "There is no need to escape '{$ch}'.",
                message: "Unknown escape sequence '{$this->prevToken}{$ch}'"
            );
        }

        /**
         * @throws XMLInvalidElementValuePartException
         */
        private function duplicateComboboxOption(
            string $option,
            int $column,
            int $line,
            int $byteIndex,
            int $byteLength = -1
        ): void
        {
            if ($byteLength < 0) {
                $byteLength = strlen($option);
            }
            // duplicate combobox option
            $this->invalidValuePart(
                column: $column,
                line: $line,
                byteIndex: $byteIndex,
                byteLength: $byteLength,
                description: "Combobox needs to have unique options",
                message: "Combobox already has '{$option}' option"
            );
        }
    }
}
