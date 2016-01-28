<?php
	namespace Sebastian\Core\Database\Query;

	class Query {
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

		public function __toString() {
			switch ($this->type) {
				case self::TYPE_SELECT:
					$query  = "SELECT\n";
					$query .= "\t" . implode($this->parts['select'], ",\n\t") . "\n";
					$query .= "FROM {$this->parts['from'][0]}" . (($this->parts['from'][1] != null) ? " AS {$this->parts['from'][1]}" : "") . "\n";

					foreach ($this->parts['join'] as $join) {
						$query .= "{$join['type']} JOIN {$join['table']}" . ($join['alias'] == null ? "" : " AS {$join['alias']} ");
						$query .= "{$join['conditionType']} " . implode($join['condition'], ' AND ') . "\n";
					}

					foreach ($this->parts['where'] as $where) {
						
					}

					$query .= (count($this->parts['groupBy']) > 0) ? ("GROUP BY\n\t" . implode($this->parts['groupBy'], ",\n\t") . "\n") : "";
					$query .= (count($this->parts['orderBy']) > 0) ? ("ORDER BY\n\t" . implode($this->parts['orderBy'], ",\n\t") . "\n") : "";

					$query .= ($this->parts['limit'] == null) ? "" : "LIMIT {$this->parts['limit']}" . "\n";
					$query .= ($this->parts['offset'] == null) ? "" : "OFFSET {$this->parts['offset']}" . "\n";
			}

			return $query;
		}
	}