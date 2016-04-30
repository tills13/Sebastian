<?php	
	namespace Sebastian\Core\Database;

	use \PDO;
	use Sebastian\Core\Exception\SebastianException;
	use Sebastian\Core\Database\Exception\DatabaseException;
	use Sebastian\Core\Database\Statement\PreparedStatement;
	use Sebastian\Utility\Configuration\Configuration;
	use Sebastian\Utility\Collection\Collection;
	use Sebastian\Utility\Logger\Logger;

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
		protected $logger;

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

			$this->logger = $context->getLogger();
			$this->cm = $context->getCacheManager();
			$this->preparedStatements = new Collection();
			$this->initializeDriver($config->get('driver'));
		}

		// todo needs to handle overrides properly (for custom drivers)
		public function initializeDriver($driverClass) {
			$driverClass = explode(':', $driverClass);

            if (count($driverClass) != 2) {
                throw new SebastianException("Connection driver config must be of the form {Namespace}:{Class}");
            }

            $driverNamespace = $driverClass[0];
            $driverClassName = $driverClass[1];

            $classPath = "\\{$driverNamespace}\\Database\\PDO\\{$driverClassName}";
            $this->driver = new $classPath(
            	$this, 
            	$this->config->get('username'), 
            	$this->config->get('password'), 
            	$this->config
            );
		}

		public function __call($name, $arguments) {
			$args = str_repeat('?, ', count($arguments) - 1) . " ?";
			$ps = $this->prepare("SELECT * FROM {$name}({$args})");
			$ps->execute($arguments);
			
			return $ps;
		}

		public function beginTransaction() {
			return $this->driver->beginTransaction();
		}

		public function commit() {
			return $this->driver->commit();
		}

		public function close() {
			if (!$this->driver) return;
			$this->driver = null;
		}

		public function execute($query, $params = []) {
			if (is_object($query)) $query = (string) $query;
			if ($params instanceof Collection) $params = $params->toArray();

			$ps = $this->prepare($query);
			$ps->execute($params);
			return $ps;
		}

		public function prepare($query, array $options = []) {
			$ps = $this->driver->prepare($query, $options);
			return $ps;
		}

		public function quote($string, $params = PDO::PARAM_STR) {
			return $this->driver->quote($string, $params);
		}

		public function rollback() {
			return $this->driver->rollback();
		}

		public function inTransaction() {
			return $this->driver->inTransaction();
		}

		public function getAttribute($attribute) {
			return $this->driver->getAttribute($attribute);
		}

		public function getConfig() {
			return $this->config;
		}

		public function getDriver() {
			return $this->driver;
		}

		public function getLastError() {
			return $this->driver->errorInfo();
		}

		public function getLastErrorCode() {
			return $this->driver->errorCode();
		}

		public function getLastId($name = null) {
			return $this->driver->lastInsertId($name);
		}

		public function setLogger(Logger $logger) {
			$this->logger = $logger;
		}

		public function getLogger() {
			return $this->logger;
		}
	}