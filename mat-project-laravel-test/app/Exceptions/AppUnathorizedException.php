<?php

namespace App\Exceptions {

    use App\Dtos\Defs\Errors\Access\UnauthorizedError;
    use App\Dtos\Defs\Types\Errors\UserSpecificPartOfAnError;
    use App\Dtos\Errors\ApplicationErrorInformation;
    use App\TableSpecificData\UserRole;
    use App\Utils\Utils;
    use Illuminate\Http\Response;

    class AppUnathorizedException extends ApplicationException
    {
        /**
         * @var string[] $allowedRoles
         */
        public function __construct(array $allowedRoles = [])
        {
            $userInfo = UserSpecificPartOfAnError::create()
                ->setMessage("You do not have required permissions.");
            if ($allowedRoles) {
                $userInfo->setDescription("You must be '" . Utils::arrayToStr($allowedRoles) . "'.");
            }
            parent::__construct(
                Response::HTTP_FORBIDDEN,
                ApplicationErrorInformation::create()
                    ->setUserInfo($userInfo)
                    ->setDetails(
                        UnauthorizedError::create()
                    )
            );
        }
    }
}
