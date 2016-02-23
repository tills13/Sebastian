<?php
	namespace Sebastian\Core\Database\Driver;

	use Sebastian\Core\Database\Result;
	use Sebastian\Core\Database\Result\PostgresResult;
	use Sebastian\Core\Database\Statement\PreparedStatement;
	use Sebastian\Core\Database\Transformer\PostgresTransformer;
	use Sebastian\Utility\Utility\Utils; // todo replace with database specific utils

	class PostgresDriver extends AbstractDriver {
		const CONNECITON_BAD = PGSQL_CONNECTION_BAD;
		const CONNECTION_OK = PGSQL_CONNECTION_OK;

		public function __construct($params, $username, $password) {
			parent::__construct($params, $username, $password);
			$this->setResultsClass(PostgresResult::class);
			$this->setTransformer(new PostgresTransformer($this));
		}

		public function connect() {
			if (!self::$connection || ($this->getStatus() != self::CONNECTION_OK)) {
				$connectionString = $this->getConnectionString();
				self::$connection = pg_connect($connectionString);
			}
		}

		public function preExecute(&$query) {}

		public function prepare($name, $query) {
			return new PreparedStatement($this, $name, pg_prepare(self::$connection, $name, $query));
		}

		public function execute($query, $params = []) {
			$this->connect();

			$result = pg_query(self::$connection, $query);
			$resultClass = $this->getResultsClass();
			return new $resultClass($this, $result);
		}

		public function executePrepared($name, $params = []) {
			$result = pg_execute(self::$connection, $name, $params);
			$resultClass = $this->getResultsClass();
			return new $resultClass($this, $result);
		}

		public function getStatus() {
			if (self::$connection == null) return $this->getStatusBad();

			return pg_connection_status(self::$connection);
		}

		public function getStatusOk() {
			return PGSQL_CONNECTION_OK;
		}

		public function getStatusBad() {
			return PGSQL_CONNECTION_BAD;
		}

		public function close() {
			if (self::$connection == null) return;
			return pg_close(self::$connection);
		}

		public function getConnectionString() {
			$host = $this->params['hostname'];
			$port = $this->params['port'];
			$dbname = $this->params['dbname'];
			//$connectTimeout = $this->params['connection_timeout'];

			return "host={$host}
					port={$port}
					dbname={$dbname}
					user={$this->username}
					password={$this->password}";
		}
	}