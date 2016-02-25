<?php
	namespace Sebastian\Core\Database\Query;

	use Sebastian\Utility\Collection\Collection;
	use Sebastian\Core\Database\Query\Part\Part;

	class InsertQuery extends Query {
		protected $columns;
		protected $values;

		protected $inserts;
		protected $returning;

		public function __construct() {
			parent::__construct();
			$this->inserts = [];
			$this->columns = [];
			$this->values = [];
			$this->returning = [];
		}

		public function setColumns($columns) {
			$this->columns = $columns;
		}

		public function addColumn($column) {
			$this->columns = $this->columns + [$column];
		}

		public function getColumns() {
			return $this->columns;
		}

		public function addInsert($column = null, $value) {
			if ($column != null) $this->columns[] = $column;
			$this->values[] = $value;
			//$this->inserts[$column] = $value;
		}

		public function getNumColumns() {
			return $this->columns ? count($this->columns) : 0;
		}

		public function addReturning(array $returning) {
			$this->returning[] = $returning;
		}

		public function setReturning($returning) {
			//if (!is_array($returning) || (count($returning) == 1 && !is_array($returning[0]))) {
			//	throw new \Exception();//QueryException("");
			//}

			$this->returning = $returning;
		}

		public function getReturning() {
			return $this->returning;
		}

		/**
		 * [getReturningString description]
		 * @return [type] [description]
		 */
		public function getReturningString() {
			$string = "";
			$returning = $this->getReturning();
			if ($returning != null && count($returning) != 0) {
				$string = $string . " RETURNING ";

				for ($i = 0; $i < count($this->getReturning()); $i++) {
					$key = array_keys($returning[$i])[0];
					$value = $returning[$i][$key];

					$string .= $key . (($value != null) ? " AS {$value}" : "");
					if ($i != count($returning) - 1) $string .= ",";
				}
			}

			return $string;
		}

		public function setValues($values) {
			$this->values = $values;
			return $this;
		}

		public function addValue($value) {
			$this->values = $this->values + [$value];
		}

		public function getValues() {
			return $this->values;
		}

		public function __toString() {
			$query = "INSERT INTO {$this->getInto()}";

			if ($this->getNumColumns() != 0) {
				$query = $query . " (" . implode(',', $this->getColumns()) . ") VALUES ";
			}

			$query = $query . " (" . implode(',', $this->getValues()) . ")";
			$query = $query . $this->getReturningString();

			return $query;
		}
	}