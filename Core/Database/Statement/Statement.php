<?php
	namespace Sebastian\Core\Database\Statement;

	use Sebastian\Core\Database\Driver\AbstractDriver;

	class Statement {
		protected $driver;
		protected $query;
		protected $params;

		public function __construct(AbstractDriver $driver) {
			$this->driver = $driver;
		}

		public function getConnection() {
			return $this->connection;
		}

		public function getParams() {
			return $this->params;
		}

		public function getQuery() {
			return $this->query;
		}

		public function execute($query, $params) {
			$this->query = $query;
			$this->params = $params;
		}
	}