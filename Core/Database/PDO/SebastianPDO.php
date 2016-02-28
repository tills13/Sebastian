<?php
	namespace Sebastian\Core\Database\PDO;

	use \PDO as PDO;
	use Sebastian\Core\Database\Connection;
	use Sebastian\Core\Database\Statement\Statement;
	use Sebastian\Core\Database\Transformer\TransformerInterface;
	use Sebastian\Utility\Configuration\Configuration;

	abstract class SebastianPDO extends PDO {
		protected $connection;
		protected $config;

		public function __construct(Connection $connection, $username, $password, Configuration $config) {
			$this->connection = $connection;
			$this->config = $config;

			$dns = "{$this->getDriverName()}:
						host={$config->get('hostname')};
						port={$config->get('port', 5432)};
						dbname={$config->get('dbname', 'postgres')};";

			parent::__construct($dns, $username, $password, []);
			
			$this->setAttribute(PDO::ATTR_STATEMENT_CLASS, [Statement::class, [$this]]);
			$this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // todo configurable
		}

		public function setDriverName($driverName) {
			$this->driverName = $driverName; 
		}

		public function getDriverName() {
			return $this->driverName;
		}
	}