<?php

namespace App\Exceptions {

    use App\Dtos\Defs\Types\Errors\UserSpecificPartOfAnError;
    use App\Dtos\Errors\ErrorResponse;
    use Illuminate\Http\Response;
    use Illuminate\Support\Str;

    class NotFoundException extends ApplicationException
    {
        public function __construct(string $what,string $in = "",string $description = ""){
            parent::__construct(Response::HTTP_NOT_FOUND,
            ErrorResponse::create()
            ->setUserInfo(
                UserSpecificPartOfAnError::create()
                ->setMessage(Str::ucfirst($what)." was not found".($in ? " in $in":"").".")
                ->setDescription($description)
                )
        );
        }
    }
}