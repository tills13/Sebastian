<?php
	namespace Sebastian\Utility\Logging\Handler;

	use Sebastian\Utility\Configuration\Configuration;
	use Sebastian\Utility\Logging\Logger;

	abstract class AbstractLogHandler implements LogHandlerInterface {
		protected $logger;
		protected $name;
		protected $config;

		public function __construct(Logger $logger, $name, Configuration $config) {
			$this->logger = $logger;
			$this->name = $name;
			$this->config = $config == null ? new Configuration() : $config;
			$this->config = $this->config->extend([
				'threshold' => Logger::INFO
			]);
		}

		abstract public function __destruct();
		abstract public function log($message);

		public function getLogger() {
			return $this->logger;
		}

		public function setName($name) {
			$this->name = $name;
		}

		public function getName() {
			return $this->name;
		}

		public function setThreshold($threshold) {
			$this->config->set('threshold', $threshold);
		}

		public function getThreshold() {
			return $this->config->get('threshold', $this->getLogger()->getThreshold());
		}

		public function isRestricted() {
			return $this->config->get('restricted', false);
		}
	}