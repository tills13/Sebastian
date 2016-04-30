<?php
	namespace Sebastian\Utility\Logger\Handler;

	use Sebastian\Utility\Configuration\Configuration;
	use Sebastian\Utility\Logger\Logger;
	use Sebastian\Utility\Logger\LoggerInterface;

	abstract class AbstractLogHandler implements LogHandlerInterface {
		protected $logger;
		protected $name;
		protected $config;

		public function __construct(LoggerInterface $logger, Configuration $config = null, $name) {
			$this->setLogger($logger);
			$this->setName($name);

			$this->config = $config == null ? new Configuration() : $config;
			$this->config = $this->config->extend([
				'threshold' => Logger::INFO
			]);
		}

		abstract public function __destruct();
		abstract public function log($message);

		public function setLogger(LoggerInterface $logger) {
			$this->logger = $logger;
		}

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