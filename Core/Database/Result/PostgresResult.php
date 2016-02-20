<?php
	namespace Sebastian\Core\Database\Result;

	class PostgresResult extends AbstractResult {
		public function fetchAll() {
			$result = pg_fetch_all($this->getResult());
			$result = $this->completeResults($result);
			return $result;
		}

		public function fetchFirst() {
			$result = pg_fetch_assoc($this->getResult());
			$result = $this->completeResults([$result])[0]; // wrap, then unwrap

			return $result;
		}

		public function fetchColumn($column) {
			return $this->fetchColumnInRow(0, $column);
		}

		public function fetchColumnInRow($row, $column) {
			$result = pg_fetch_result($this->getResult(), $row, $column);
			return $result;
			//$result = $this->completeResults($result); // wrap, then unwrap
		}

		public function getError() {
			return pg_last_error();
			//return pg_result_error($this->getResult());
		}

		public function getNumAffectedRows() {
			return pg_affected_rows($this->getResult());
		}

		public function getNumColumns() {
			return pg_num_fields($this->getResult());
		}

		public function getNumRows() {
			return pg_num_rows($this->getResult());
		}

		public function getColumnType($column) {
			return pg_field_type($this->getResult(), $column);
		}

		public function getStatus($long) {
			return pg_result_status($this->result, ($long ? PGSQL_STATUS_LONG : PGSQL_STATUS_STRING));
		}
	}