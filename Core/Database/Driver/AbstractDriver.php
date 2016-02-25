<?php
	namespace Sebastian\Core\Database\Driver;

	use Sebastian\Core\Database\Connection;

	abstract class AbstractDriver {
		const CONNECITON_BAD = 0;
		const CONNECTION_OK = 1;

		protected $connectionResource; // todo should this be static?
		protected $connection;
		protected $params;
		protected $username;
		protected $password;
		protected $resultsClass;
		protected $transformer;

		public function __construct(Connection $connection, $params, $username, $password) {
			$this->connection = $connection;
			$this->params = $params;
			$this->username = $username;
			$this->password = $password;
			$this->resultsClass = null;
			$this->transformer = null;
		}

		abstract public function connect();
		abstract public function close();
		abstract public function execute($query, $params = []);
		abstract public function prepare($name, $query);

		abstract public function getConnectionString();
		abstract public function getStatus();
		abstract public function getStatusOk();
		abstract public function getStatusBad();

		public function init() {}
		public function preExecute(&$query) {}
		public function postExecute($query, &$result) {}

		public function getConnection() {
			return $this->connection;
		}

		public function setConnectionResource($resource) {
			$this->connectionResource = $resource;
		}

		public function getConnectionResource() {
			return $this->connectionResource;
		}

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