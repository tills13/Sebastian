<?php 
    namespace Sebastian\Core\Exception;

    /**
     * NotPermissibleException
     * 
     * @author Tyler <tyler@sbstn.ca>
     * @since  Oct. 2015
     */
    class NotPermissibleException extends SebastianException {
        public function __construct() {
            parent::__construct("You do not have permission to do that...");
        }
    }