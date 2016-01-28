<?php
	namespace Sebastian\Core\Database;

	class QueryBuilder {
		public static function getInstance() {
			return new QueryBuilder();
		}

		protected function __construct() {
			$this->query = new Query();
		}

		public function select($columns) {
			if (!is_array($columns)) $columns = explode(',', $columns);
			$this->columns = 
		}
	}