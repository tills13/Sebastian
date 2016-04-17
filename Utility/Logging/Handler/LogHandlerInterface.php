<?php
	namespace Sebastian\Utility\Logging\Handler;

	use Sebastian\Utility\Configuration\Configuration;
	use Sebastian\Utility\Logging\Logger;

	interface LogHandlerInterface {
		public function __construct(Logger $logger, $name, Configuration $config);
		public function __destruct();
		public function log($message);

		public function getLogger();

		public function setName($name);
		public function getName();

		public function setThreshold($threshold);
		public function getThreshold();

		public function isRestricted();
	}