<?php

namespace App\Helpers {

    use Illuminate\Http\Request;
    use Validator;

    class RequestHelper
    {
        public static function getData(Request $request):mixed{
           $validated = $request->validate([
                'data'=>'required'
                ]
            );
            return $validated['data'];
        }
    }
}