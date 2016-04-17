<?php
	namespace Sebastian\Core\Database\Statement;

	use \PDO as PDO;
	use \PDOStatement as PDOStatement;
	use \PDOException as PDOException;
	use Sebastian\Core\Database\PDO\SebastianPDO;
	use Sebastian\Core\Database\Transformer\TransformerInterface;

	class Statement extends PDOStatement {
		protected $pdo;
		protected $columns;
		protected $logger;

		protected function __construct(SebastianPDO $pdo) {
			$this->pdo = $pdo;
			$this->logger = $pdo->getLogger();
		}

		public function execute(array $params = []) {
			$startTime = microtime(true);
			try { parent::execute($params); } catch (PDOException $e) {
				$this->logger->info("query failed", "db_log");
				throw $e;
			}

			$diff = microtime(true) - $startTime;
			$this->logger->info("query completed in {$diff} seconds", "db_log", "QUERY");
		}

		public function getColumnType($column) {
			//if (is_string($column)) $column = 
		}

		public function fetchAll() {
			$results = parent::fetchAll(PDO::FETCH_ASSOC);
			return $results;
		}
	}
