<?php
namespace Dev\Utils {

    enum ScriptOptionType:string{
        case FLAG = "";
        case VALUE_REQUIRED = ":";
        case VALUE_OPTIONAL = "::";
    }
}