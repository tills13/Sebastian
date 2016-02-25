<?php
	namespace Sebastian\Core\Database\PDO;

	use Sebastian\Core\Database\Connection;
	use Sebastian\Core\Database\Statement\Statement;
	use Sebastian\Core\Database\Transformer\PostgresTransformer;
	use Sebastian\Utility\Configuration\Configuration;

	class PostgresPDO extends SebastianPDO {
		public function __construct(Connection $connection, $username, $password, Configuration $config) {
			$this->setDriverName('pgsql');
			$this->setTransformer(new PostgresTransformer($connection));

			parent::__construct($connection, $username, $password, $config);
		}
	}