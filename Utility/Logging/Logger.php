<?php
    namespace Sebastian\Utility\Logging;

    use Sebastian\Core\Application;
    use Sebastian\Utility\Configuration\Configuration;

    /**
     * Logger
     *
     * logging
     *  
     * @author Tyler <tyler@sbstn.ca>
     * @since Oct. 2015
     */
    class Logger extends LoggingInterface {
        const EMERGENCY = 'emergency';
        const ALERT     = 'alert';
        const CRITICAL  = 'critical';
        const ERROR     = 'error';
        const WARNING   = 'warning';
        const NOTICE    = 'notice';
        const INFO      = 'info';
        const DEBUG     = 'debug';

        private $options = [
            'extension' => 'log',
            'dateFormat' => 'm-d G:i:s',
            'filename' => false,
            'flushFrequency' => false,
            'prefix' => 'log_',
            'tag' => false
        ];

        private $logFilePath;
        private $logLevelThreshold = Logger::DEBUG;
        private $logLineCount = 0;
        private $logLevels = [
            Logger::EMERGENCY => 0,
            Logger::ALERT     => 1,
            Logger::CRITICAL  => 2,
            Logger::ERROR     => 3,
            Logger::WARNING   => 4,
            Logger::NOTICE    => 5,
            Logger::INFO      => 6,
            Logger::DEBUG     => 7
        ];

        private $fileHandle;
        private $lastLine = '';
        private $defaultPermissions = 0777;

        //public function __construct($logDirectory, $logLevelThreshold = Logger::DEBUG, $options = []) {
        public function __construct($context, Configuration $config = null) {
            $this->context = $context;

            if (!$config) $config = new Configuration();
            $this->config = $config->extend([
                'directory' => $context->getDefaultLogPath(),
                'filename' => $context->getDefaultLogFilename(),
                'threshold' => 6
            ]);

            $logDirectory = rtrim(
                $this->config->get('directory') . $this->config->get('filename'),
                DIRECTORY_SEPARATOR
            );

            if (!file_exists($logDirectory)) {
                mkdir($logDirectory, $this->defaultPermissions, true);
            }

            if ($logDirectory === "php://stdout" || $logDirectory === "php://output") {
                $this->setLogToStdOut($logDirectory);
                $this->setFileHandle('w+');
            } else {
                $this->setLogFilePath($logDirectory);

                if (file_exists($this->logFilePath) && !is_writable($this->logFilePath)) {
                    throw new \RuntimeException('The file could not be written to. Check that appropriate permissions have been set.');
                }

                $this->setFileHandle('a');
            }

            if (!$this->fileHandle) {
                throw new \RuntimeException('The file could not be opened. Check permissions.');
            }
        }

        public function __destruct() {
            if ($this->fileHandle) fclose($this->fileHandle);
        }

        public function setLogToStdOut($stdOutPath) {
            $this->logFilePath = $stdOutPath;
        }

        public function setLogFilePath($logDirectory) {
            if ($this->options['filename']) {
                if (strpos($this->options['filename'], '.log') !== false || strpos($this->options['filename'], '.txt') !== false) {
                    $this->logFilePath = $logDirectory . DIRECTORY_SEPARATOR . $this->options['filename'];
                } else {
                    $this->logFilePath = $logDirectory . DIRECTORY_SEPARATOR . $this->options['filename'] . $this->options['prefix'].date('Y-m-d') . '.' . $this->options['extension'];
                }
            } else {
                $this->logFilePath = $logDirectory . DIRECTORY_SEPARATOR . $this->options['prefix'].date('Y-m-d') . '.' . $this->options['extension'];
            }
        }

        public function setFileHandle($writeMode) {
            $this->fileHandle = fopen($this->logFilePath, $writeMode);
        }

        public function setDateFormat($dateFormat) {
            $this->options['dateFormat'] = $dateFormat;
        }

        public function setLogLevelThreshold($logLevelThreshold) {
            $this->logLevelThreshold = $logLevelThreshold;
        }

        public function setTag($tag) {
            $this->options['tag'] = $tag;
        }

        public function log($level, $tag = null, $message) {
            if ($this->logLevels[$this->logLevelThreshold] < $this->logLevels[$level]) {
               return;
            }

            $message = $this->formatMessage($level, $tag, $message);   
            $this->write($message);
        }

        public function write($message) {
            if (null !== $this->fileHandle) {
                if (fwrite($this->fileHandle, $message) === false) {
                    throw new \RuntimeException('The file could not be written to. Check that appropriate permissions have been set.');
                } else {
                    $this->lastLine = trim($message);
                    $this->logLineCount++;

                    if ($this->options['flushFrequency'] && $this->logLineCount % $this->options['flushFrequency'] === 0) {
                        fflush($this->fileHandle);
                    } elseif (!$this->options['flushFrequency']) { fflush($this->fileHandle); }
                }
            }
        }

        public function getLogFilePath() {
            return $this->logFilePath;
        }

        public function getLastLogLine() {
            return $this->lastLine;
        }

        private function formatMessage($level, $tag = '', $message) {
            $level = strtoupper($level);
            if (is_null($tag) && $this->options['tag']) {
                $tag = ':' . strtoupper($this->options['tag']);
            } else $tag = ":{$tag}";

            return "[{$this->getTimestamp()}] [{$level}{$tag}] {$message}" . PHP_EOL;
        }

        private function getTimestamp() {
            $originalTime = microtime(true);

            $date = new \DateTime(date('Y-m-d H:i:s', $originalTime));

            return $date->format($this->options['dateFormat']);
        }

        private function indent($string, $indent = '\t') {
            return $indent . str_replace("\n", "\n".$indent, $string);
        }
    }

    class LoggingInterface {
        public function emergency($message, $tag = null) {
            $this->log(Logger::EMERGENCY, $tag, $message);
        }

        public function alert($message, $tag = null) {
            $this->log(Logger::ALERT, $tag, $message);
        }
        
        public function critical($message, $tag = null) {
            $this->log(Logger::CRITICAL, $tag, $message);
        }

        public function error($message, $tag = null) {
            $this->log(Logger::ERROR, $tag, $message);
        }
        
        public function warning($message, $tag = null) {
            $this->log(Logger::WARNING, $tag, $message);
        }
        
        public function notice($message, $tag = null) {
            $this->log(Logger::NOTICE, $tag, $message);
        }
        
        public function info($message, $tag = null) {
            $this->log(Logger::INFO, $tag, $message);
        }
       
        public function debug($message, $tag = null) {
            $this->log(Logger::DEBUG, $tag, $message);
        }
    }