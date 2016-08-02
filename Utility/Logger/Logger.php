<?php
    namespace Sebastian\Utility\Logger;

    use \RuntimeException;
    use Sebastian\Core\Application;
    use Sebastian\Utility\Configuration\Configuration;
    use Sebastian\Utility\Logger\Handler\FileLogHandler;
    use Sebastian\Utility\Logger\Handler\LogHandlerInterface;

    /**
     * Logger
     *  
     * @author Tyler <tyler@sbstn.ca>
     * @since Oct. 2015
     */
    class Logger extends BaseLogger {
        const EMERGENCY = 'EMERGENCY';
        const ALERT = 'ALERT';
        const CRITICAL = 'CRITICAL';
        const ERROR = 'ERROR';
        const WARNING = 'WARNING';
        const NOTICE = 'NOTICE';
        const INFO = 'INFO';
        const DEBUG = 'DEBUG';

        private $logLevelThreshold = Logger::DEBUG;
        private $logLevels = [
            Logger::EMERGENCY => 0,
            Logger::ALERT => 1,
            Logger::CRITICAL => 2,
            Logger::ERROR => 3,
            Logger::WARNING => 4,
            Logger::NOTICE => 5,
            Logger::INFO => 6,
            Logger::DEBUG => 7
        ];

        protected $context;
        protected $config;
        protected $handlers;

        public function __construct(Application $context, Configuration $config = null) {
            $this->context = $context;

            if (!$config) $config = new Configuration();
            $this->config = $config->extend([
                'format' => "{date} [{level}{tag}] {message}",
                'date_format' => "m-d G:i:s",
                'threshold' => Logger::INFO
            ]);

            $this->handlers = [];
            $this->addHandlersFromConfig();
        }

        public function __destruct() {}

        private function addHandlersFromConfig() {
            $handlers = $this->config->sub('handlers');

            foreach ($handlers as $name => $config) {
                $type = $config->get('type', null);
                if ($type == "file") $mHandler = new FileLogHandler($this, $config, $name);
                else {
                    throw new RuntimeException("unknown log handler type {$type}");
                }

                $this->addHandler($mHandler);
            }
        }

        public function addHandler(LogHandlerInterface $handler) {
            $this->handlers[$handler->getName()] = $handler; 
        }

        public function getHandler($name) {
            return isset($this->handlers[$name]) ? $this->handlers[$name] : null;
        }

        public function setDateFormat($format) {
            $this->config->set('date_format', $format);
        }

        public function getDateFormat() {
            return $this->config->get('date_format', '');
        }

        public function setThreshold($threshold) {
            $this->config->set('threshold', $threshold);
        }

        public function getThreshold() {
            return $this->config->get('threshold', Logger::INFO);
        }

        public function log($handler = null, $level, $tag = null, $message) {
            $message = $this->formatMessage($level, $tag, $message);

            if ($handler) {
                if (!isset($this->handlers[$handler])) return;
                
                $handler = $this->handlers[$handler];
                if ($this->logLevels[$handler->getThreshold()] >= $this->logLevels[$level]) {
                    $handler->log($message);
                }
            } else {
                foreach ($this->handlers as $handler) {
                    if ($handler->isRestricted()) continue;
                    if ($this->logLevels[$handler->getThreshold()] >= $this->logLevels[$level]) {
                        // todo sendAnyways
                        $handler->log($message);    
                    }
                }
            }
        }

        public function getContext() {
            return $this->context;
        }

        private function formatMessage($level, $tag = '', $message) {
            $level = strtoupper($level);
            /*if (is_null($tag) && $this->options['tag']) {
                $tag = ':' . strtoupper($this->options['tag']);
            } else $tag = ":{$tag}";*/

            $tag = is_null($tag) ? '' : ":{$tag}"; 
            return "[{$this->getTimestamp()}] [{$level}{$tag}] {$message}" . PHP_EOL;
        }

        private function getTimestamp() {
            $originalTime = microtime(true);
            $date = new \DateTime(date('Y-m-d H:i:s', $originalTime));

            return $date->format($this->config->get('date_format'));
        }
    }

    class BaseLogger implements LoggerInterface {
        public function emergency($message, array $context = []) {

        }

        public function alert($message, array $context = []) {

        }

        public function critical($message, array $context = []) {

        }

        public function error($message, array $context = []) {

        }

        public function warning($message, array $context = []) {

        }

        public function notice($message, array $context = []) {

        }

        public function info($message, array $context = []) {

        }

        public function debug($message, array $context = []) {

        }

        public function log($level, $message, array $context = []) {

        }

        /*public function emergency($message, $handler = null, $tag = null) {
            $this->log($handler, Logger::EMERGENCY, $tag, $message);
        }

        public function alert($message, $handler = null, $tag = null) {
            $this->log($handler, Logger::ALERT, $tag, $message);
        }
        
        public function critical($message, $handler = null, $tag = null) {
            $this->log($handler, Logger::CRITICAL, $tag, $message);
        }

        public function error($message, $handler = null, $tag = null) {
            $this->log($handler, Logger::ERROR, $tag, $message);
        }
        
        public function warning($message, $handler = null, $tag = null) {
            $this->log($handler, Logger::WARNING, $tag, $message);
        }
        
        public function notice($message, $handler = null, $tag = null) {
            $this->log($handler, Logger::NOTICE, $tag, $message);
        }
        
        public function info($message, $handler = null, $tag = null) {
            $this->log($handler, Logger::INFO, $tag, $message);
        }
       
        public function debug($message, $handler = null, $tag = null) {
            $this->log($handler, Logger::DEBUG, $tag, $message);
        }*/
    }