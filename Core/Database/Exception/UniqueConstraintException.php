<?php
    namespace Sebastian\Core\Database\Exception;

    use \Exception;
    
    class UniqueConstraintException extends DatabaseException {
        const ERROR_CODE_UNKNOWN = -1;

        public function __construct($message, $code = self::ERROR_CODE_UNKNOWN, Exception $previous = null) {
            parent::__construct($message, $code, $previous);
        }
    }