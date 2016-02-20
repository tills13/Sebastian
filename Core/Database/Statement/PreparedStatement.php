<?php
	namespace Sebastian\Core\Database\Statement;

	use Sebastian\Core\Database\Driver\AbstractDriver;
	use Sebastian\Core\Database\Exception\DatabaseException;
	use Sebastian\Utility\Collection\Collection;

	class PreparedStatement extends Statement {
		protected $driver;
		protected $resource;
		protected $name;
		protected $parameterMap;

		public function __construct(AbstractDriver $driver, $name, $resource) {
			parent::__construct($driver);

			$this->driver = $driver;
			$this->name = $name;
			$this->resource = $resource;

			if ($this->resource == false || $this->resource == null) {
				throw new DatabaseException("failed to create the PreparedStatement");
			}
		}

		public function __invoke() {
			$params = func_get_args();
			foreach ($params as &$param) {
				$param = $this->driver->getTransformer()->transformToDBValue($param);
			}

			return $this->driver->executePrepared($this->name, $params);
		}

		public function execute($params = [], $types = []) {
			return $this->driver->executePrepared($this->name, $params);
		}

		public function getName() {
			return $this->name;
		}
	}