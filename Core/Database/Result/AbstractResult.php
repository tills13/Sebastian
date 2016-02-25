<?php
	namespace Sebastian\Core\Database\Result;

	use Sebastian\Core\Database\Driver\AbstractDriver;

	abstract class AbstractResult {
		protected $driver;
		protected $result;

		public function __construct(Connection $connection, $result) {
			$this->connection = $connection;
			$this->result = $result;
		}

		abstract public function fetchAll();
		abstract public function fetchFirst();
		abstract public function fetchColumn($column);
		abstract public function fetchColumnInRow($row, $column);
		abstract public function getError();
		abstract public function getErrorCode();
		abstract public function getNumAffectedRows();
		abstract public function getNumColumns();
		abstract public function getNumRows();
		abstract public function getColumnType($column);
		abstract public function getStatus($long);

		/**
		 * returns the <? extends AbstractDriver> object used to fetch
		 * the result
		 * @return AbstractDriver driver
		 */
		public function getDriver() {
			return $this->driver;
		}

		/**
		 * fetches the raw result resource
		 * typically useless...
		 * @return resource the resource
		 */
		public function getResult() {
			return $this->result;
		}

		protected function completeResults($results = []) {
			if ($results == null || count($results) == 0) return null;
			if ($results[0] == false || !is_array($results[0])) return null;

			$index = 0;
			$typeMap = [];

			foreach ($results[0] as $column => $result) {
				$type = $this->getColumnType($index);
				$typeMap[$column] = $type;
				$index++;
			}

			$transformer = $this->getDriver()->getTransformer();
			foreach ($results as $index => &$result) {
				array_walk($result, function(&$value, $key) use ($typeMap, $transformer) {
					$type = $typeMap[$key];
					$value = $transformer->transformToPhpValue($value, $type);
				});
			}

			return $results;
		}
	}