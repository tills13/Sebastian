<?php
	namespace Sebastian\Core\Database\Exception;

	use Sebastian\Core\Exception\SebastianException;

	class DatabaseException extends SebastianException {
		const ERROR_CODE_UNKNOWN = -1;
		protected $errorCode;

		public function __construct($error, $errorCode = DatabaseException::ERROR_CODE_UNKNOWN) {
			parent::__construct("ERROR: {$error}");

			$this->errorCode = $errorCode;
		}
	}