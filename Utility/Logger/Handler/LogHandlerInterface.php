<?php
    namespace Sebastian\Utility\Logger\Handler;

    use Sebastian\Utility\Configuration\Configuration;
    use Sebastian\Utility\Logger\LoggerInterface;

    interface LogHandlerInterface {
        public function __construct(LoggerInterface $logger, Configuration $config, $name);
        public function __destruct();
        public function log($message);
        public function setLogger(LoggerInterface $logger);
        public function getLogger();
        public function setName($name);
        public function getName();
        public function setThreshold($threshold);
        public function getThreshold();
        public function isRestricted();
    }