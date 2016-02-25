<?php
	namespace Sebastian\Core\Database\Statement;

	use Sebastian\Core\Database\Connection;
	use Sebastian\Core\Database\Exception\DatabaseException;
	use Sebastian\Utility\Collection\Collection;

	class PreparedStatement extends Statement {
		protected $resource;
		protected $name;
		protected $parameterMap;

		public function __construct(Connection $connection, $name, $resource) {
			parent::__construct($connection);

			$this->name = $name;
			$this->resource = $resource;

			if ($this->resource == false || $this->resource == null) {
				throw new DatabaseException("failed to create the PreparedStatement");
			}
		}

		public function __invoke() {
			$connection = $this->getConnection();
			$driver = $connection->getDriver();
			$transformer = $driver->getTransformer();

			$params = func_get_args();
			foreach ($params as &$param) {
				$param = $transformer->transformToDBValue($param);
			}

			return $connection->executePrepared($this->getName(), $params);
		}

		/**
		 * @todo implement types
		 * @param  array  $params [description]
		 * @param  array  $types  [description]
		 * @return [type]         [description]
		 */
		public function execute($params = [], $types = []) {
			$connection = $this->getConnection();
			return $connection->executePrepared($this->getName(), $params);
		}

		public function getName() {
			return $this->name;
		}
	}