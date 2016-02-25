<?php
	namespace Sebastian\Core\Database\Transformer;

	use Sebastian\Core\Database\Connection;

	abstract class AbstractTransformer implements TransformerInterface {
		protected $connection;

		public function __construct(Connection $connection) {
			$this->connection = $connection;
		}

		abstract public function getDatabaseType($phpType);
		abstract public function getPhpType($databaseType);
		abstract public function transformToPhpValue($value, $dbType);
		abstract public function transformToDBValue($value, $phpType);
	}