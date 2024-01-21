<?php

namespace App\Helpers {

    use App\Exceptions\InternalException;
    use App\Utils\Utils;
    use Illuminate\Http\Request;
    use Validator;

    class RequestHelper
    {
        public static function getData(Request $request):mixed{
            echo "\nVALIDATION";
           $validated = $request->validate([
                'data'=>'required|array'
                ]
            );
            echo "\nVALIDATED";
            return Utils::recursiveAssocArrayToStdClass($validated['data'],canChange:true);
        }

        public static function getQuery(Request $request):mixed{
            $query = $request->query() ?? [];
           $validated = Validator::validate($query,[
                'data'=>'required|array'
            ]);
            unset($query);
            return Utils::recursiveAssocArrayToStdClass($validated['data'],canChange:true);
        }
    }
}