<?php	
	namespace Sebastian\Core\Database;

	use Sebastian\Core\Context\Context;
	use Sebastian\Core\Utility\Utils;

	/**
	 * Connection
	 * 
	 * @author Tyler <tyler@sbstn.ca>
	 * @since  Oct. 2015
	 */
	class Connection extends Context {
		protected static $connection;
		protected $config;

		public function __construct($context) {
			parent::__construct($context);

			$this->config = $context->getConfig('database.connection');
			$this->lazy = $this->config['lazy'] ?: false;

			if (!Connection::$connection && !$this->lazy) {
				$this->connect();
			}
		}

		public function prepare($query) { throw new \Exception('prepare not implemented'); }

		// should use prepared statements... maybe someday
		public function execute($query, $params = []) {
			//print "{$query}<br/>";
			foreach ($params as $key => $parameter) {
				$parameter = Utils::escapeSQL($parameter);
				$query = preg_replace("(:{$key})", $parameter, $query);
			}

			$result = pg_query($this->getConnection(), $query);
			return new Result($this, $result);
		}

		public function getStatus() {
			return pg_connection_status(Connection::$connection);
		}

		public function getQueryBuilder($options = []) {
			$options = array_merge([
				'tagging' => $this->config['tagging']
			], $options);
			
			return new QueryBuilder($options);
		}

		// this is a wrapper for pg..connection
		public function getConnection() {
			$this->connect();

			return Connection::$connection;
		}

		// private methods
		private function connect() {
			if (!Connection::$connection || ($this->getStatus() != PGSQL_CONNECTION_OK)) {
				$connectionString = $this->getConnectionString();
				Connection::$connection = pg_connect($connectionString);
			}
		}

		private function getConnectionString() {
			$app = $this->getContext();
			$host = $app->getConfig('database.hostname');
			$port = $app->getConfig('database.port');
			$dbname = $app->getConfig('database.dbname');
			$user = $app->getConfig('database.username');
			$password = $app->getConfig('database.password');
			$connectTimeout = $app->getConfig('database.password', 5);

			return "host={$host} port={$port} dbname={$dbname} user={$user} password={$password} connect_timeout=5";
		}
	}