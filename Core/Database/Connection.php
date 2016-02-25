<?php	
	namespace Sebastian\Core\Database;

	use Sebastian\Core\Exception\SebastianException;
	use Sebastian\Core\Database\Exception\DatabaseException;
	use Sebastian\Core\Database\Statement\PreparedStatement;
	use Sebastian\Utility\Configuration\Configuration;
	use Sebastian\Utility\Collection\Collection;

	/**
	 * Connection
	 * 
	 * @author Tyler <tyler@sbstn.ca>
	 * @since  Oct. 2015
	 */
	class Connection {
		protected $driver;
		protected $context;
		protected $config;
		protected $preparedStatements;

		public function __construct($context, Configuration $config) {
			$this->context = $context;
			$this->config = $config->extend([
				'driver' => 'Sebastian\Core:PostgresDriver',
				'hostname' => null,
				'port' => null,
				'dbname' => null,
				'username' => null,
				'password' => null, 
				'options' => [
					'tagging' => true,
					'lazy' => false,
					'caching' => false,
					'connection_timeout' => 5
				]
			]);

			$this->initializeDriver($config->get('driver'));
			$this->cm = $context->getCacheManager();
			$this->preparedStatements = new Collection();
		}

		// todo needs to handle overrides properly (for custom drivers)
		public function initializeDriver($driverClass) {
			$driverClass = explode(':', $driverClass);

            if (count($driverClass) != 2) {
                throw new SebastianException("Connection driver config must be of the form {Namespace}:{Class}");
            }

            $driverNamespace = $driverClass[0];
            $driverClassName = $driverClass[1];

            $classPath = "\\{$driverNamespace}\\Database\\Driver\\{$driverClassName}";

            $this->driver = new $classPath($this, [
            	'hostname' => $this->config->get('hostname'),
            	'port' => $this->config->get('port'),
            	'dbname' => $this->config->get('dbname')
            ], $this->config->get('username'), $this->config->get('password'));

            $this->driver->init();
		}

		public function close() {
			if (!$this->driver) return;
			$this->driver->close();
		}

		public function connect() {
			if ($this->getStatus() !== $this->driver->getStatusOk()) {
				$this->driver->connect();
			}
		}

		public function execute($query, $params = []) {
			$this->connect();
			$index = 1;
			$finalParams = [];
			foreach ($params as $key => $parameter) {
				$query = preg_replace("(:{$key})", "\\\${$index}", $query);
				$finalParams[] = $parameter;
				$index++;
			}

			$this->driver->preExecute($query, $finalParams);
			$ps = $this->prepare($query);
			$result = $this->executePrepared($ps->getName(), $finalParams);
			$this->driver->postExecute($query, $result);

			return $result;
		}

		public function executePrepared($name, $params) {
			$this->connect();
			$result = $this->driver->executePrepared($name, $params);

			if ($result == false || $result == null) {
				throw new DatabaseException($this->driver->getLastError(), $this->driver->getErrorCode());
			}

			$resultClass = $this->driver->getResultsClass();
			$mResult = new $resultClass($this, $result);

			return $mResult;
		}

		public function executeUpdate($query, $params) {
			$this->connect();

		}

		public function prepare($query, $name = null) {
			$this->connect();
			$name = $name ?: $this->generatePreparedStatementName();
			$resource = $this->driver->prepare($name, $query);

			if ($resource == false || $resource == null) {
				throw new DatabaseException($this->driver->getLastError(), $this->driver->getErrorCode());
			}

			$ps = new PreparedStatement($this, $name, $resource);
			$this->preparedStatements->set($ps->getName(), $ps);
			return $ps;
		}

		public function getConfig() {
			return $this->config;
		}

		public function getConnection() {
			$this->connect();

			return $this->driver->getConnection();
		}

		public function getDriver() {
			return $this->driver;
		}

		public function getStatus() {
			return $this->driver->getStatus();
		}

		//** private

		private function prepareQuery(&$query) {
			$index = 1;
			$finalParams = [];
			foreach ($params as $key => $parameter) {
				$query = preg_replace("(:{$key})", "\\\${$index}", $query);
				$finalParams[] = $parameter;
				$index++;
			}

			return $finalParams;
		}

		private function generatePreparedStatementName() {
			$count = $this->preparedStatements->count();
			do {
				$name = "st_{$count}";
				$count++;
			} while($this->preparedStatements->has($name));

			return $name;
		}
	}