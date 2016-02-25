<?php
	namespace Sebastian\Core\Database\Statement;

	use \PDO as PDO;
	use \PDOStatement as PDOStatement;
	use Sebastian\Core\Database\Transformer\TransformerInterface;

	class Statement extends PDOStatement {
		protected $pdo;
		protected $transformer;

		protected function __construct(PDO $pdo) {
			$this->pdo = $pdo;
			$this->transformer = $pdo->getTransformer();
		}

		public function fetchAll() {
			$results = parent::fetchAll(PDO::FETCH_ASSOC);

			if ($this->getTransformer()) $results = $this->completeResults($results);

			return $results;
		}

		private function completeResults(array $results = []) {
			if ($results == null || count($results) == 0) return null;
			if ($results[0] == false || !is_array($results[0])) return null;

			$index = 0;
			$typeMap = [];

			foreach ($results[0] as $column => $result) {
				$meta = $this->getColumnMeta($index);
				$typeMap[$column] = $meta['native_type'];
				$index++;
			}

			$transformer = $this->getTransformer();
			foreach ($results as $index => &$result) {
				array_walk($result, function(&$value, $key) use ($typeMap, $transformer) {
					$type = $typeMap[$key];
					$value = $transformer->transformToPhpValue($value, $type);
				});
			}

			return $results;
		}

		public function setTransformer(TransformerInterface $transformer) {
			$this->transformer = $transformer;
		}

		public function getTransformer() {
			return $this->transformer;
		}
	}
