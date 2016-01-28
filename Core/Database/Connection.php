<?php	
	namespace Sebastian\Core\Database;

	use Sebastian\Core\Utility\Utils;

	/**
	 * Connection
	 * 
	 * @author Tyler <tyler@sbstn.ca>
	 * @since  Oct. 2015
	 */
	class Connection {
		protected $driver;

		protected $hostname;
		protected $port;
		protected $dbname;
		protected $username;
		protected $password;
		protected $options;

		protected $context;

		public function __construct($context, $config = []) {
			$this->context = $context;

			$config = array_merge([
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
			], $config);

			$this->hostname = $config['hostname'];
			$this->port = $config['port'];
			$this->dbname = $config['dbname'];
			$this->username = $config['username'];
			$this->password = $config['password'];

			$this->options = $config['options'];

			$this->initializeDriver($config['driver']);
			$this->cm = $context->getCacheManager();
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

            $this->driver = new $classPath([
            	'hostname' => $this->hostname,
            	'port' => $this->port,
            	'dbname' => $this->dbname
            ], $this->username, $this->password);

            $this->driver->init();
		}

		public function prepare($name = null, $query, $params = []) {
			$this->connect();

			return $this->driver->prepare($name, $query, $params);
		}

		// should use prepared statements... maybe someday
		public function execute($query, $params = []) {
			if ($this->getStatus() !== $this->driver->getStatusOk()) {
				$this->connect();
			}
			
			$this->driver->preExecute($query, $params);

			/*$key = $this->cm->generateKey($query);
			var_dump($key);
			if ($this->cm->isCached($key)) {
				$result = $this->cm->load($key);
				var_dump($result);
			} else {
				$result = $this->driver->execute($query, $params);	
				$this->cm->cache($key, $result);
			}*/

			$result = $this->driver->execute($query, $params);	
			$this->driver->postExecute($query, $result);

			return $result;
		}

		public function getStatus() {
			return $this->driver->getStatus();
		}

		public function getConnection() {
			$this->connect();

			return $this->driver->getConnection();
		}

		private function connect() {
			$this->driver->connect();
		}

		public function isLazy() {
			return $this->options['lazy'];
		}

		// helpers
		public function getQueryBuilder($options = []) {
			$options = array_merge([
				'tagging' => $this->options['tagging']
			], $options);
			
			return new QueryBuilder($options);
		}
	}