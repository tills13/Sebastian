<?php
	namespace Sebastian\Core\Database\Transformer;

	use Sebastian\Core\Database\Driver\AbstractDriver;

	abstract class AbstractTransformer {
		protected $driver;

		public function __construct(AbstractDriver $driver) {
			$this->driver = $driver;
		}

		abstract public function getDatabaseType($phpType);
		abstract public function getPhpType($databaseType);
		abstract public function transformToPhpValue($value, $dbType);
		abstract public function transformToDBValue($value, $phpType);
	}