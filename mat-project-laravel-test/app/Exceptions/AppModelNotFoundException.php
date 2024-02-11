<?php


namespace App\Exceptions {
    class AppModelNotFoundException extends NotFoundException
    {
        public function __construct(string $modelName,array $withProperties = [],string $in = "",string $description = ""){
            $message = $modelName;
            if($withProperties){
                $message.=" with ";
                reset($withProperties);
               $name = key($withProperties);
               $value = $withProperties[$name];
                $message.="$name '$value'";
                while(next($withProperties) !== false && ($name = key($withProperties)) !== null){
                    $value = $withProperties[$name];
                    $message .= ", $name '$value'";
                }
            }
            parent::__construct($message,in:$in,description:$description);
        }
    }
}