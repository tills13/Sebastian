<?php
	namespace Sebastian\Core\Database;

	use Sebastian\Core\Utility\Utils;

	/**
	 * Result
	 * 
	 * @author Tyler <tyler@sbstn.ca>
	 * @since  Oct. 2015
	 */
	class Result {
		public $result;
		public $connection;

		public function __construct($connection, $result) {
			$this->connection = $connection;
			$this->result = $result;
		}

		public function fetch($type = null) {
			$result = pg_fetch_all($this->result);

			return $result ? $this->parseResult($result) : [];
		}

		public function fetchFirst() {
			$result = pg_fetch_assoc($this->result);

			return $result ? $this->parseResult([$result])[0] : [];
		}

		public function fetchColumn($row = 0, $field) {
			$result = pg_fetch_result($this->result, $row, $field);

			return $this->parseResult([[$result]])[0][0] ?: []; //christ
		}

		public function getNumAffectedRows() {
			return pg_affected_rows($this->result);
		}

		public function getNumColumns() {
			return pg_num_fields($this->result);
		}

		public function getNumRows() {
			return pg_num_rows($this->result);
		}

		public function getStatus($long = false) {
			return pg_result_status($this->result, ($long ? PGSQL_STATUS_LONG :  PGSQL_STATUS_STRING));
		}

		public function getError() {
			return pg_result_error($this->result);
		}

		public function getResult () {
			return $this->result;
		}

		public function getConnection() {
			return $this->connection;
		}

		//===
		
		private function parseResult($results = []) {
			foreach ($results as $index => &$row) {
				$rowIndex = 0;
				foreach ($row as $name => &$value) {
					$type = pg_field_type($this->result, $rowIndex++);
					$value = Utils::cast($value, $type);
				}
			}

			return $results;
		}
	}