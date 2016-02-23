<?php	
	namespace Sebastian\Core\Database;

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

            $this->driver = new $classPath([
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

		public function prepare($query, $name = null) {
			$this->connect();

			$name = $name ?: $this->generatePreparedStatementName();
			$ps = $this->driver->prepare($name, $query);
			$this->preparedStatements->set($ps->getName(), $ps);
			return $ps;
		}

		public function execute($query, $params = []) {
			//print ($query);
			$index = 1;
			$finalParams = [];
			foreach ($params as $key => $parameter) {
				//print ('$' . $index);
				$query = preg_replace("(:{$key})", ('$' . $index), $query);
				$finalParams[] = $parameter;
				$index++;
			}

			//print ($query);



			//die();


			//$ps = $this->pre


			$this->driver->preExecute($query, $params);
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
			if ($this->getStatus() !== $this->driver->getStatusOk()) {
				$this->driver->connect();
			}
		}

		public function isLazy() {
			return $this->config->get('lazy');
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