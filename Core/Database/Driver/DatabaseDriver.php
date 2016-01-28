<?php
	namespace Sebastian\Core\Database\Driver;

	abstract class DatabaseDriver {
		const CONNECITON_BAD = 0;
		const CONNECTION_OK = 1;

		protected static $connection;

		protected $params;
		
		protected $username;
		protected $password;

		public function __construct($params, $username, $password) {
			$this->params = $params;
			$this->username = $username;
			$this->password = $password;
		}

		public function init() {}

		abstract public function connect();

		public function preExecute(&$query) {}

		abstract function prepare($name, $query, $params);

		abstract public function execute($query, $params);

		public function postExecute($query, &$result) {}

		abstract public function getStatus();
		abstract public function getStatusOk();
		abstract public function getStatusBad();

		abstract public function getConnectionString();
	}