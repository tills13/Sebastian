<?php
	namespace Sebastian\Core\Database\Query;

	use Sebastian\Utility\Collection\Collection;
	use Sebastian\Core\Database\Query\Part\Part;

	class InsertQuery extends Query {
		protected $columns;
		protected $values;

		protected $inserts;

		public function __construct() {
			parent::__construct();
			$this->inserts = [];
			$this->columns = [];
			$this->values = [];
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

			$query = $query . " (" . implode(',', $this->getValues()) . ");";
			return $query;
		}
	}