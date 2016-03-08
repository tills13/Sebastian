<?php

	use \PDOException as PHPPDOException;

	class PDOException {
		protected $code;
		protected $message;

		public function __construct(PHPPDOException $e) {

		}
	}