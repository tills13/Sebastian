<?php
	namespace Sebastian\Core\Database\Query;

	use Sebastian\Core\Configuration\Configuration;
	use Sebastian\Core\Database\Query\Part\DirectionalJoin;
	use Sebastian\Core\Database\Query\Part\From;
	use Sebastian\Core\Database\Query\Part\InnerJoin;
	use Sebastian\Core\Database\Query\Part\Join;

	class QueryFactory {
		protected $config;
		protected $query;

		public static function getFactory(Configuration $config = null) {
			return new QueryFactory($config);
		}

		protected function __construct(Configuration $config = null) {
			$this->config = $config;
			$this->query = new Query();
		}

		public function select() {
			$this->query->setType(Query::TYPE_SELECT);

			$columns = func_get_args();
			foreach ($columns as $column) {
				if (is_array($column)) {
					$alias = array_pop(array_keys($column));
					$name = $column[$alias];

					$this->query->selectColumn($name, $alias);
				} else {
					$this->query->select([$column]);
				}
			}

			return $this;
		}

		public function selectColumn($column, $alias = null) {
			$this->query->setType(Query::TYPE_SELECT);

			$this->query->selectColumn($column, $alias);

			return $this;
		}

		public function from() {
			$froms = func_get_args();

			foreach (func_get_args() as $source) {
				if (is_array($source)) {
					$alias = array_pop(array_keys($source));
					$name = $source[$alias];
				} else {
					$name = $source;
					$alias = null;
				}

				$this->query->from(new From($name, $alias));
			}

			return $this;
		}

		public function join($type, $table, $on = null) {
			if (is_array($table)) {
				$tableAlias = array_pop(array_keys($table));
				$tableName = $table[$tableAlias];
			} else {
				$tableName = $table;
				$tableAlias = null;
			}

			switch ($type) {
				case Join::TYPE_INNER:
					$join = new InnerJoin($tableName, $tableAlias, $on);
					break;
				case Join::TYPE_LEFT:
					$join = new DirectionalJoin(DirectionalJoin::DIRECTION_LEFT, $tableName, $tableAlias, $on);
					break;
				case Join::TYPE_RIGHT:
					$join = new DirectionalJoin(DirectionalJoin::DIRECTION_RIGHT, $tableName, $tableAlias, $on);
					break;
			}
			
			$this->query->join($join);
			return $this;
		}

		public function innerJoin($table, $on = null) {
			$this->join(Join::TYPE_INNER, $table, $on);
		}

		public function leftJoin($table, $on = null) {
			$this->join(Join::TYPE_LEFT, $table, $on);
		}

		public function rightJoin($table, $on = null) {
			$this->join(Join::TYPE_RIGHT, $table, $on);
		}

		public function reset() {
			$this->query = new Query();
		}

		public function getQuery() {
			return $this->query;
		}
	}