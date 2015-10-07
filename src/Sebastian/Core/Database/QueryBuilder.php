<?php
	namespace Sebastian\Core\Database;

	use Sebastian\Core\Utility\Utils;
	
	/**
	 * QueryBuilder
	 * 
	 * @author Tyler <tyler@sbstn.ca>
	 * @since  Oct. 2015
	 */
	class QueryBuilder {
		private $options;

		protected $mode = 'select';
		public $select = [];
		public $tables = [];
		public $where = [];
		public $whereMode = 'and';
		public $groupBy = [];
		public $limit = null;
		public $offset = null;
		public $orderBy = [];
		public $having = [];
		public $index = null;
		public $insertFields = [];

		public $returns;

		public $set = [];
		public $setDuplicateKey = [];

		public $union = [];
		public $parameters = [];

		public $queryResult = null;

		public function __construct($options = []) {
			// resolve defaults
			$options = array_merge([
				'tag' => false
			], $options);

			$this->options = $options;
		}

		public function select($expression, $as = false) {
			$this->mode = "select";

			if ($as !== false) $this->select[$as] = $expression;
			else {
				$expressions = (array) $expression;
				foreach ($expressions as $expression) {
					if (!empty($expression)) $this->select[] = $expression;
				}
			}

			return $this;
		}

		public function from($table, $on = false, $type = false) {
			if (!empty($type) or !empty($on)) $this->tables[] = strtoupper($type ? $type : "inner") . " JOIN $table" . (!empty($on) ? " ON ($on)" : "");
			else array_unshift($this->tables, $table);

			return $this;
		}

		public function where($predicate, $value = false) {
			if (empty($predicate)) return $this;

			if ($value !== false) $predicate = array($predicate => $value);

			$predicates = (array)$predicate;
			foreach ($predicates as $field => $predicate) {
				if (!is_numeric($field)) {
					$i = count($this->where);
					$this->where[] = "$field=:where$i";
					$this->bind(":where$i", $predicate);
				} else $this->where[] = $predicate;
			}

			return $this;
		}

		/*public function where($predicate, $value = false) {
			if (empty($predicate)) return $this;
			$param = ("{$predicate}" . round(rand(0,1000)));
			$this->where[$this->whereMode][] = "$predicate=:{$param}";
			$this->where[array_shift(array_diff(array_keys($this->where), [$this->whereMode]))][] = null;
			$this->bind(":{$param}", $predicate);

			return $this;
		}

		public function andWhere($predicate, $value = false) {
			$currentMode = $this->whereMode;
			$this->whereMode = 'and';
			$this->where($predicate, $value);
			$this->whereMode = $currentMode;
			return $this;
		}

		public function orWhere($predicate, $value = false) {
			$currentMode = $this->whereMode;
			$this->whereMode = 'or';
			$this->where($predicate, $value);
			$this->whereMode = $currentMode;
			return $this;
		}*/

		public function groupBy($expression) {
			$expressions = (array) $expression;
			foreach ($expressions as $expression) {
				if (!empty($expression)) $this->groupBy[] = $expression;
			}

			return $this;
		}

		public function orderBy($expression) {
			$expressions = (array) $expression;
			foreach ($expressions as $expression) {
				if (!empty($expression)) $this->orderBy[] = $expression;
			}

			return $this;
		}

		public function having($expression) {
			$expressions = (array)$expression;
			foreach ($expressions as $expression) {
				if (!empty($expression)) $this->having[] = $expression;
			}

			return $this;
		}

		public function limit($limit) {
			$this->limit = $limit;
			return $this;
		}

		public function offset($offset) {
			$this->offset = $offset;
			return $this;
		}

		public function update($table) {
			$this->mode = "update";
			$this->tables[] = $table;

			return $this;
		}

		public function set($field, $value = false, $sanitize = true) {
			if (!is_array($field)) $field = array($field => $value);
			foreach ($field as $field => $value) {
				$value = $sanitize ? Utils::escapeSQL($value) : $value;

				if ($this->mode == "update") $this->set[$field] = $value;
				else {
					$this->insertFields[] = $field;
					$this->set[0][] = $value;
				}
			}

			return $this;
		}

		public function insert($table) {
			$this->mode = "insert";
			$this->tables[] = $table;

			return $this;
		}

		public function setMultiple($fields, $valueSets) {
			$this->insertFields = $fields;
			foreach ($valueSets as &$row) {
				foreach ($row as &$value) {
					$value = Utils::escapeSQL($value);
				}
			}

			$this->set = $valueSets;

			return $this;
		}

		public function replace($table) {
			$this->mode = "replace";
			$this->tables[] = $table;

			return $this;
		}

		public function delete($table = null) {
			$this->mode = "delete";
			if ($table) $this->select[] = $table;

			return $this;
		}

		public function union($query) {
			$this->mode = "union";
			$this->union[] = $query;

			return $this;
		}

		public function returning($cols) {
			$this->returns = $cols;

			return $this;
		}

		protected function getWhere() {
			$where = "";


			foreach ($this->where as $where) {
				//if ($where[])
				//$where .= "WHERE"
			}



			return count($this->where) ? "\nWHERE (".implode(")\n\t{$this->whereMode} (", $this->where).")" : "";
		}

		protected function getOrderBy() {
			return count($this->orderBy) ? "\nORDER BY ".implode(", ", $this->orderBy) : "";
		}

		protected function getSelect() {
			$select = [];
			foreach ($this->select as $k => $v) {
				if (!is_numeric($k)) $select[] = "$v AS $k";
				else $select[] = $v;
			}

			$select = "SELECT " . implode(", \n\t", $select);

			$from = count($this->tables) ? "\nFROM " . implode("\n\t", $this->tables) : "";
			$index = $this->index ? "\nUSE INDEX ($this->index)" : "";
			$having = count($this->having) ? "\nHAVING (" . implode(") AND (", $this->having).")" : "";
			$groupBy = count($this->groupBy) ? "\nGROUP BY " . implode(", ", $this->groupBy) : "";
			$limit = $this->limit ? "\nLIMIT $this->limit" : "";
			$offset = $this->offset ? "\nOFFSET $this->offset" : "";

			return $select.$from.$index.$this->getWhere().$groupBy.$this->getOrderBy().$limit.$offset;
		}

		protected function getUpdate() {
			$tables = implode(", ", $this->tables);

			$set = [];
			foreach ($this->set as $k => $v) $set[] = "$k=$v";
			$set = implode(", ", $set);

			return "UPDATE $tables SET $set " . $this->getWhere();
		}

		protected function getInsert() {
			$tables = implode(", ", $this->tables);
			$fields = implode(", ", $this->insertFields);
			$rows = [];

			foreach ($this->set as $row) $rows[] = "(".implode(", ", $row).")";

			$values = implode(", ", $rows);

			return "INSERT INTO $tables ($fields) VALUES $values";
		}

		protected function getReplace() {
			$query = $this->getInsert();
			$query = "REPLACE" . substr($query, 6);

			return $query;
		}

		protected function getDelete() {
			$tables = implode(", ", $this->select);
			$from = implode("\n\t", $this->tables);

			return "DELETE $tables FROM $from " . $this->getWhere();
		}

		protected function getUnion() {
			$selects = $this->union;
			foreach ($selects as &$sql) $sql = "\t(" . $sql->get() . ")";

			$selects = implode("\nUNION\n", $selects);

			$limit = $this->limit ? "\nLIMIT $this->limit" : "";
			$offset = $this->offset ? "\nOFFSET $this->offset" : "";

			return $selects . $this->getOrderBy() . $limit . $offset;
		}

		protected function getReturn() {
			if ($this->returns) {
				if (is_array($this->returns)) {
					return " returning " . implode(',', array_map(function($key, $value) {
						return "{$key} as {$value}";
					}, array_keys($this->returns), array_values($this->returns)));
				} else {
					return " returning {$this->returns}";
				}
			} else return "";
		}

		public function getQuery() {
			switch ($this->mode) {
				case "select": $query = $this->getSelect(); break;
				case "update": $query = $this->getUpdate(); break;
				case "insert": $query = $this->getInsert(); break;
				case "replace": $query = $this->getReplace(); break;
				case "delete": $query = $this->getDelete(); break;
				case "union": $query = $this->getUnion(); break;
				default: $query = "";
			}

			$query .= $this->getReturn();

			$self = $this;
			$query = preg_replace_callback('/(:[A-Za-z0-9_]+)/', function ($matches) use ($self) {
				return array_key_exists($matches[1], $self->parameters)
					? Utils::escapeSQL($self->parameters[$matches[1]][0], $self->parameters[$matches[1]][1])
					: $matches[1];
			}, $query);

			return $this->tag($query);
		}

		public function tag($query, $depth = 1, $limit = 4) {
			if (!$this->options['tag']) return $query;

			$trace = debug_backtrace();
        	$caller = $trace[min($depth, count($trace) - 1)];
        
	        while(isset($caller['class']) && ($caller['class'] == __CLASS__)) {
				if (++$depth >= count($trace)) break;
				$caller = $trace[$depth];
			}

			$queryTag = '';
			for ($i = $depth; $i < ($depth + $limit); $i++) { 
				$caller = $trace[$i];

				$bt = $caller['function'] . '()';
				if (isset($caller['class'])) $bt = $caller['class'] . '::' . $bt;

				$queryTag .= '-- ' . $bt . "\n";
				if (strpos($caller['function'], '{closure}') === false) {
					$line = empty($caller['line']) ? '(unknown)' : $caller['line'];
					$file = empty($caller['file']) ? '(unknown file)' : $caller['file'];
					$bt .= '-- ' . $file . ' line ' . $line . "\n";
				}
			}

			return "\n" . $queryTag . $query . "\n";
		}

		public function count($as = false) {
			$this->select("count(*)", $as);

			return $this;
		}

		public function bind($parameter, $value, $dataType = null) {
			$this->parameters[$parameter] = array($value, $dataType);
			return $this;
		}

		// other
		// 
		
		public function clean($options = null) {
			return new QueryBuilder($options ?: $this->options);
		}

		public function setWhereMode($mode = 'AND') {
			$this->whereMode = $mode;
		}
	}