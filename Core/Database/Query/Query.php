<?php
	namespace Sebastian\Core\Database\Query;

	use Sebastian\Utility\Collection\Collection;
	use Sebastian\Core\Database\Query\Part\Part;

	class Query implements Part {
		const TYPE_SELECT = 0;
		const TYPE_DELETE = 1;
		const TYPE_UPDATE = 2;
		const TYPE_INSERT = 3;

		protected $columns;
		protected $columnAliases;

		protected $froms;
		protected $joins;
		protected $where;
		protected $limit;
		protected $offset;
		protected $orderBy;
		protected $into;

		protected $binds;

		public function __construct() {
			$this->binds = new Collection();

			$this->columns = new Collection();
			$this->columnAliases = new Collection();
			$this->froms = new Collection();

			$this->joins = new Collection();
			$this->where = null;
			$this->limit = null;
			$this->offset = 0; 
			$this->orderBy = new Collection();
		}

		public function addBind($key, $value) {
			$this->binds->set($key, $value);
		}

		public function setBinds($binds) {
			$binds = ($binds instanceof Collection) ? $binds : new Collection($binds);
			$this->binds = $binds;
		}

		public function getBinds() {
			return $this->binds;
		}

		public function select(array $columns) {
			$this->columns->extend($columns);
		}

		public function selectColumn($column, $alias) {
			$this->columns->set(null, $column);
			$this->columnAliases->set($column, $alias);
		}

		public function addFrom(Part $from) {
			$this->setFrom($from);
		}

		public function setFrom(Part $from) {
			$this->froms->set(null, $from);
		}

		public function getFroms() {
			return $this->forms;
		}

		public function setInto($identifier) {
			$this->into = $identifier;
		}

		public function getInto() {
			return $this->into;
		}

		public function join($join) {
			$this->joins->set(null, $join);
		}

		public function setLimit($limit) {
			$this->limit = $limit;
			return $this;
		}

		public function getLimit() {
			return $this->limit;
		}

		public function setOffset($offset) {
			$this->offset = $offset;
			return $this;
		}

		public function getOffset() {
			return $this->offset;
		}

		public function addOrderBy($column, $direction) {
			$this->orderBy->set($column, $direction);
		}

		public function setOrderBy($column, $direction) {
			$this->orderBy = new Collection([$column => $direction]);
		}

		public function getOrderBy() {
			return $this->orderBy;
		}

		public function setWhere($expression) {
			$this->where = $expression;
			return $this;
		}

		public function getWhere() {
			return $this->where;
		}

		public function getColumns() {
			return $this->columns;
		}

		public function getColumnAliases() {
			return $this->columnAliases;
		}

		public function __toString() {
			$query  = "SELECT \n";
			$query .= $this->columnsToString() . "\n";
			$query .= "FROM " . $this->fromsToString() . "\n";

			foreach ($this->joins as $m => $join) {
				$query .= $join . "\n";
			}

			if ($this->where !== null) {
				$query .= "WHERE " . $this->where . "\n";
			}

			$orderBy = $this->getOrderBy();
			if ($orderBy && $orderBy->count() != 0) {
				$query = $query . "ORDER BY ";

				$index = 0;
				foreach ($orderBy as $column => $direction) {
					$direction = strtoupper($direction);
					$query = $query . "{$column} {$direction}";
					if (++$index != $orderBy->count()) $query = $query . ",";
					else $query = $query . "\n";
				}
			}

			if ($this->limit) $query .= "LIMIT {$this->limit}\n";
			if ($this->offset) $query .= "OFFSET {$this->offset}\n";
			
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