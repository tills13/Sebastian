<?php
	namespace Sebastian\Core\Database\Statement;

	use \PDO as PDO;
	use \PDOStatement as PDOStatement;
	use Sebastian\Core\Database\PDO\SebastianPDO;
	use Sebastian\Core\Database\Transformer\TransformerInterface;

	class Statement extends PDOStatement {
		protected $pdo;
		protected $columns;

		protected function __construct(SebastianPDO $pdo) {
			$this->pdo = $pdo;
		}

		public function getColumnType($column) {
			//if (is_string($column)) $column = 
		}

		public function fetchAll() {
			$results = parent::fetchAll(PDO::FETCH_ASSOC);
			return $results;
		}
	}
