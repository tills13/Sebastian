<?php
	namespace Sebastian\Core\Database\Driver;

	abstract class AbstractDriver {
		const CONNECITON_BAD = 0;
		const CONNECTION_OK = 1;

		protected static $connection;

		protected $params;
		
		protected $username;
		protected $password;

		protected $resultsClass;
		protected $transformer;

		public function __construct($params, $username, $password) {
			$this->params = $params;
			$this->username = $username;
			$this->password = $password;
			$this->resultsClass = null;
			$this->transformer = null;
		}

		public function init() {}

		abstract public function connect();

		public function preExecute(&$query) {}

		abstract function prepare($name, $query);
		abstract public function execute($query, $params = []);

		public function postExecute($query, &$result) {}

		abstract public function getStatus();
		abstract public function getStatusOk();
		abstract public function getStatusBad();
		abstract public function close();

		abstract public function getConnectionString();

		public function setResultsClass($class) {
			$this->resultsClass = $class;
		}	

		public function getResultsClass() {
			return $this->resultsClass;
		}

		public function setTransformer($transformer) {
			$this->transformer = $transformer;
		}	

		public function getTransformer() {
			return $this->transformer;
		}
	}