<?php
	namespace Sebastian\Core\Database\Query\Part;

	class DirectionalJoin extends Join {
		const DIRECTION_LEFT = "LEFT";
		const DIRECTION_RIGHT = "RIGHT";

		protected $direction;

		public function __construct($direction, $table, $alias = null, $condition = null) {
			parent::__construct($table, $alias, $condition);

			$this->type = Join::TYPE_DIRECTIONAL;
			$this->direction = $direction;
		}

		public function getDirection() {
			return $this->direction;
		}

		public function __toString() {
			return $this->direction . " JOIN " . $this->getTable() 
				. ($this->hasTableAlias() ? (" AS " . $this->getTableAlias()) : "") . " ON " . $this->getCondition();
		}
	}