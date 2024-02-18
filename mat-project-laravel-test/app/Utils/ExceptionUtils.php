<?php

namespace App\Utils {

    use Illuminate\Http\Request;
    use Illuminate\Http\Response;
    use Throwable;

    class ExceptionUtils
    {
       
        public static function isRenderable(Throwable $e):bool{
            return method_exists($e,'render');
        }

        public static function tryRender(Throwable $e,Request $request):Response|null{
            if(self::isRenderable($e)){
                try{
                   $response = $e->{'render'}($request);
                   if($response instanceof Response){
                    return $response;
                   }
                }
                catch(Throwable $exc){

                }
            }
            return null;
        }
    }
}