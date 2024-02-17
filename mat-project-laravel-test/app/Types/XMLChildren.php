<?php

namespace App\Types {

    use App\Exceptions\InternalException;
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
        //     if(array_key_exists($name,$this->requiredChildren)){
        //         dump("DUPLICATE CHILD: $name");
        //         dump($this->requiredChildren[$name]);
        //     }
        //     if(array_key_exists($name,$this->nonRequiredChildren)){
        //         dump("DUPLICATE CHILD: $name");
        //         dump($this->nonRequiredChildren[$name]);
        //     }
        //     dump(
        //         "requiredChildrenCount : " . count($this->requiredChildren)."\n"
        //     ."nonRequiredChildrenCount : " . count($this->nonRequiredChildren)."\n"
        // );
            if(array_key_exists($name,$this->requiredChildren) 
            || array_key_exists($name,$this->nonRequiredChildren)){
                $e = new InvalidArgumentException(
                argumentName:"child",
                argumentValue:$child,
                isNotValidBecause:"child with same name already exists",
                context:[
                    'requiredChildren' => $this->requiredChildren,
                    'nonRequiredChildren' => $this->requiredChildren,
                    'newChild' => [$name => $child]
                ]);
                report($e);
                throw $e;
            }
            
            //report(new InternalException($child->getName()." getting parent obj id"));
            $parent = $child->getParentObjectId();
            
            //report(new InternalException($child->getName()." got parent obj id"));
            if($parent === null){
                $e = new InvalidArgumentException(
                    argumentName:"child",
                    argumentValue:$child,
                    isNotValidBecause:"all children should have parent",
                    context:[
                        'requiredChildren' => $this->requiredChildren,
                        'nonRequiredChildren' => $this->requiredChildren,
                        'newChild' => [$name => $child]
                    ]);
                    report($e);
                    throw $e;
            }
            //report("child checking was successfull");
            return $parent;
        }
        /**
         * @param XMLNodeBase $child
         */
        public function addChild(XMLNodeBase $child,bool $required = false):self{
          //  report(new InternalException($child->getName()." addChild"));
            $name = $child->getName();
           // report(new InternalException($child->getName()." checking name and getting its parent"));
           $parent = $this->checkChildNameAndGetItsParent($child,$name);
          // report(new InternalException($child->getName()." getting expected parent"));
            $expectedParent = $this->getParent();
            if($expectedParent === null){
                $this->parent = $parent;
            }
            else if($parent !== $expectedParent){
                $e = new InvalidArgumentException(
                argumentName:"child",
                argumentValue:$child,
                isNotValidBecause:"children should have same parent",
                context:[
                    'requiredChildren' => $this->requiredChildren,
                    'nonRequiredChildren' => $this->requiredChildren,
                    'newChild' => [$name => $child]
                ]);
                report($e);
                throw $e;
            }
            //report(new InternalException($child->getName()." child adding"));
            if($required){
                $this->requiredChildren[$name] = $child;
            }
            else{
                $this->nonRequiredChildren[$name] = $child;
            }
           // report(new InternalException($child->getName()." child added successfully"));
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
         * @return iterable<string,array{0:XMLNodeBase,1:bool}>
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