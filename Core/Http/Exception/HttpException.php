<?php
	namespace Sebastian\Core\Http\Exception;

	use Sebastian\Core\Http\Response\Response;
	use \Exception;

	class HttpException extends Exception {
		protected $responseCode;

		public function __construct($message, $code) {
			parent::__construct($message, $code);
			
			$this->responseCode = $code;
			$this->message = $message;
		}

		public static function notFoundException($message = null) {
			return new HttpException($message ?: $message, Response::HTTP_NOT_FOUND);
		}

		public function getHttpResponseCode() {
			return $this->responseCode;
		}
	}