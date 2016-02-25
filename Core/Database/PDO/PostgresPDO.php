<?php
	namespace Sebastian\Core\Database\PDO;

	class PostgresPDO extends \PDO {
		public function __construct($username, $password, array $options) {
			parent::__construct('pgsql:', $username, $password, $options);
		}
	}