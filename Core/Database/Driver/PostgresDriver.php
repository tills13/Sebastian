<?php
	namespace Sebastian\Core\Database\Driver;

	use Sebastian\Core\Utility\Utils;

	use Sebastian\Core\Database\Result;

	class PostgresDriver extends DatabaseDriver {
		const CONNECITON_BAD = PGSQL_CONNECTION_BAD;
		const CONNECTION_OK = PGSQL_CONNECTION_OK;

		public function connect() {
			if (!self::$connection || ($this->getStatus() != CONNECTION_OK)) {
				$connectionString = $this->getConnectionString();
				self::$connection = pg_connect($connectionString);
			}
		}

		public function preExecute(&$query) {}	

		public function prepare($name = null, $query, $params) {
			return pg_prepare(self::$connection, $name, $query);
		}

		public function execute($query, $params = []) {
			foreach ($params as $key => $parameter) {
				$parameter = Utils::escapeSQL($parameter);
				$query = preg_replace("(:{$key})", $parameter, $query);
			}

			$result = pg_query(self::$connection, $query);
			return new Result($this, $result);
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