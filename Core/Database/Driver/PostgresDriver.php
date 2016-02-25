<?php
	namespace Sebastian\Core\Database\Driver;

	use Sebastian\Core\Database\Connection;
	use Sebastian\Core\Database\Exception\DatabaseException;
	use Sebastian\Core\Database\Result;
	use Sebastian\Core\Database\Result\PostgresResult;
	use Sebastian\Core\Database\Transformer\PostgresTransformer;
	use Sebastian\Utility\Utility\Utils; // todo replace with database specific utils

	class PostgresDriver extends AbstractDriver {
		const CONNECITON_BAD = PGSQL_CONNECTION_BAD;
		const CONNECTION_OK = PGSQL_CONNECTION_OK;

		public function __construct(Connection $connection, $params, $username, $password) {
			parent::__construct($connection, $params, $username, $password);
			$this->setResultsClass(PostgresResult::class);
			$this->setTransformer(new PostgresTransformer($this));
		}

		public function connect() {
			if (!$this->getConnectionResource() || ($this->getStatus() != self::CONNECTION_OK)) {
				$connectionString = $this->getConnectionString();
				$this->setConnectionResource(pg_connect($connectionString));
			}
		}

		public function prepare($name, $query) {
			return pg_prepare($this->getConnectionResource(), $name, $query);
		}

		public function execute($query, $params = []) {
			return pg_query($this->getConnectionResource(), $query);
		}

		public function executePrepared($name, $params = []) {
			return pg_execute($this->getConnectionResource(), $name, $params);
		}

		public function getErrorCode() {
			//return pg_last_error($this->getConnectionResource());
			return 0;
		}

		public function getLastError() {
			return pg_last_error($this->getConnectionResource());
		}

		public function getStatus() {
			if ($this->getConnectionResource() == null) return $this->getStatusBad();

			return pg_connection_status($this->getConnectionResource());
		}

		public function getStatusOk() {
			return PGSQL_CONNECTION_OK;
		}

		public function getStatusBad() {
			return PGSQL_CONNECTION_BAD;
		}

		public function close() {
			if ($this->getConnectionResource() == null) return;
			return pg_close($this->getConnectionResource());
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