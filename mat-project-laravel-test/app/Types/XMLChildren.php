<?php

namespace App\Types {

    use App\Exceptions\InvalidArgumentException;
    use App\Types\XMLNodeBase;
    use App\Utils\Utils;
    use Iterator;
    use Ramsey\Collection\Exception\OutOfBoundsException;

    class XMLChildren
    {
/**
         * @var array<string,XMLNodeBase> $nonRequiredChildren
         */
        private array $nonRequiredChildren;

        /**
         * @var array<string,XMLNodeBase> $requiredChildren
         */
        private array $requiredChildren;

        private ?object $parent;

        public function __construct()
        {
            $this->nonRequiredChildren = array();
            $this->requiredChildren = array();
            $this->parent = null;
        }

        public static function construct():self{
            return new self();
        }

        /**
         * @return string[]
         */
        public function getNames():array{
            return  [
                ...array_keys($this->requiredChildren), 
            ...array_keys($this->nonRequiredChildren)
        ];
        }

        public function getParent():?object{
           return $this->parent;
        }

    
        private function checkChildNameAndGetItsParent(XMLNodeBase $child,string $name){
            $name = $child->getName();
            if(array_key_exists($name,$this->requiredChildren)){
                dump("DUPLICATE CHILD: $name");
                dump($this->requiredChildren[$name]);
            }
            if(array_key_exists($name,$this->nonRequiredChildren)){
                dump("DUPLICATE CHILD: $name");
                dump($this->nonRequiredChildren[$name]);
            }
            dump(
                "requiredChildrenCount : " . count($this->requiredChildren)."\n"
            ."nonRequiredChildrenCount : " . count($this->nonRequiredChildren)."\n"
        );
            if(array_key_exists($name,$this->requiredChildren) 
            || array_key_exists($name,$this->nonRequiredChildren)){
                throw new InvalidArgumentException(
                argumentName:"child",
                argumentValue:$child,
                isNotValidBecause:"child with same name already exists",
                context:[
                    'requiredChildren' => $this->requiredChildren,
                    'nonRequiredChildren' => $this->requiredChildren,
                    'newChild' => [$name => $child]
                ]);
            }
            $parent = $child->getParentObjectId();
            if($parent === null){
                throw new InvalidArgumentException(
                    argumentName:"child",
                    argumentValue:$child,
                    isNotValidBecause:"all children should have parent",
                    context:[
                        'requiredChildren' => $this->requiredChildren,
                        'nonRequiredChildren' => $this->requiredChildren,
                        'newChild' => [$name => $child]
                    ]);
            }
            return $parent;
        }
        /**
         * @param XMLNodeBase $child
         */
        public function addChild(XMLNodeBase $child,bool $required = false):self{
            $name = $child->getName();
           $parent = $this->checkChildNameAndGetItsParent($child,$name);
            $expectedParent = $this->getParent();
            if($expectedParent === null){
                $this->parent = $parent;
            }
            else if($parent !== $expectedParent){
                throw new InvalidArgumentException(
                argumentName:"child",
                argumentValue:$child,
                isNotValidBecause:"children should have same parent",
                context:[
                    'requiredChildren' => $this->requiredChildren,
                    'nonRequiredChildren' => $this->requiredChildren,
                    'newChild' => [$name => $child]
                ]);
            }

            if($required){
                $this->requiredChildren[$name] = $child;
            }
            else{
                $this->nonRequiredChildren[$name] = $child;
            }
            return $this;
        }

        public function addChildWithPossiblyDifferentParent(XMLNodeBase $child,bool $required = false){
            $name = $child->getName();
            $this->checkChildNameAndGetItsParent($child,$name);
            if($required){
                $this->requiredChildren[$name] = $child;
            }
            else{
                $this->nonRequiredChildren[$name] = $child;
            }
            return $this;
        }

        /**
         * @param string $name
         * @return XMLNodeBase|false
         */
        public function tryGetChild(string $name):XMLNodeBase|false{
            return $this->requiredChildren[$name] 
            ?? $this->nonRequiredChildren[$name] 
            ?? false;
        }
        

        /**
         * @return iterable<string,XMLNodeBase>
         */
        public function getRequiredChildren():iterable{
            foreach($this->requiredChildren as $name => $child){
                yield $name => $child;
            }
        }

        /**
         * @return iterable<string,XMLNodeBase>
         */
        public function getNonRequiredChildren():iterable{
            foreach($this->nonRequiredChildren as $name => $child){
                yield $name => $child;
            }
        }

         /**
         * @return iterable<string,array{XMLNodeBase,bool}>
         * bool - means required
         */
        public function getChildren():iterable{
            foreach($this->requiredChildren as $name => $child){
                yield $name => [$child,true];
            }
            foreach($this->nonRequiredChildren as $name => $child){
                yield $name => [$child,false];
            }
        }


    }
}