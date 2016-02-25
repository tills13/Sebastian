<?php
	namespace Sebastian\Core\Database\Statement;

	use Sebastian\Core\Database\Connection;

	class Statement {
		protected $connection;
		protected $query;
		protected $params;

		public function __construct(Connection $connection) {
			$this->connection = $connection;
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