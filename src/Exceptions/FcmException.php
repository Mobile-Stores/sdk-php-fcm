<?php
namespace Firebase\Exceptions;

use Exception;

class FcmException extends Exception{
    
    const TOKEN_EMPTY = 1;
    const PROJECT_NAME_EMPTY = 2;
    const DEVELOPER_KEY_EMPTY = 3;
    const ERROR_GENERATE_TOKEN = 4;
    const TO_EMPTY = 5;
    const TITLE_EMPTY = 6;
    const BODY_EMPTY = 7;
    const INVALID_TO_FORMAT = 8;
    
    public function __construct(string $message = "", int $code = 0, \Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
        
}
