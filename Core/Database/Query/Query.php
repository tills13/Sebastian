<?php
	namespace Sebastian\Core\Database\Query;

	use Sebastian\Component\Collection\Collection;
	use Sebastian\Core\Database\Query\Part\Part;

	class Query implements Part {
		const TYPE_SELECT = 0;
		const TYPE_DELETE = 1;
		const TYPE_UPDATE = 2;
		const TYPE_INSERT = 3;

		private $parts = [
			'select'	=> [],
			'update'	=> [],
			'from'		=> [],
			'into'		=> [],
			'join' 		=> [],
			'set'		=> [],
			'where'		=> [],
			'groupBy' 	=> [],
			'orderBy'	=> [],
			'offset'	=> null,
			'limit'		=> null
		];



		protected $columns;
		protected $columnAliases;

		protected $froms;

		protected $joins;

		public function __construct() {
			$this->type = self::TYPE_SELECT;

			$this->columns = new Collection();
			$this->columnAliases = new Collection();
			$this->froms = new Collection();

			$this->joins = new Collection();
		}

		public function select(array $columns) {
			$this->columns->extend($columns);
		}

		public function selectColumn($column, $alias) {
			$this->columns->set(null, $column);
			$this->columnAliases->set($column, $alias);
		}

		public function from(Part $from) {
			$this->froms->set(null, $from);
		}

		public function join($join) {
			$key = $join->getTable();
			$key = preg_replace('/\./', '_', $key);
			$this->joins->set($key, $join);
		}






		public function getColumns() {
			return $this->columns;
		}

		public function getColumnAliases() {
			return $this->columnAliases;
		}

		public function getType() {
			return $this->type;
		}

		public function setType($type) {
			$this->type = $type;
		}

		public function __toString() {
			switch ($this->type) {
				case self::TYPE_SELECT:
					$query  = "SELECT \n";
					$query .= $this->columnsToString() . "\n";
					$query .= "FROM " . $this->fromsToString() . "\n";

					foreach ($this->joins as $m => $join) {
						$query .= $join . "\n";
					}
			}

			return $query;
		}

		protected function columnsToString() {
			$aliases = $this->columnAliases;
			$cols = array_map(function($column) use ($aliases) {
				if ($aliases->has($column)) {
					return "{$column} AS {$aliases->get($column)}";
				} else return $column;
			}, $this->columns->toArray());

			return implode(',', $cols);
		}

		protected function fromsToString() {
			/*$aliases = $this->tableAliases;
			$tables = array_map(function($table) use ($aliases) {
				if ($aliases->has($table)) {
					return "{$table} AS {$aliases->get($table)}";
				} else return $table;
			}, $this->tables->toArray());*/

			return implode(',', $this->froms->toArray());
		}
	}