<?php
	namespace Sebastian\Core\Database\Query\Part;

	class InnerJoin extends Join {
		public function __construct($table, $alias = null, $condition = null) {
			parent::__construct($table, $alias, $condition);
			$this->type = Join::TYPE_INNER;
		}

		public function getTable() {
			return $this->table;
		}

		public function getTableAlias() {
			return $this->alias;
		}

		public function getCondition() {
			return $this->condition;
		}
	}