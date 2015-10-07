<?php
	namespace Sebastian\Core\Service;

	/**
	 * BaseService
	 *
	 * base service class
	 * 
	 * @author Tyler <tyler@sbstn.ca>
	 * @since Oct. 2015
	 */
	class BaseService {
		public function __construct($app) {
			$this->conn = $app->getConnection();
		}

		public function getQueryBuilder($options = []) {
			return $this->conn->getQueryBuilder($options);
		}
	}