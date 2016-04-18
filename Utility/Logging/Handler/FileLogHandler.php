<?php
	namespace Sebastian\Utility\Logging\Handler;

	use \RuntimeException;
	use Sebastian\Utility\Configuration\Configuration;
	use Sebastian\Utility\Logging\Logger;

	class FileLogHandler extends AbstractLogHandler {
		const STD_OUT_HANDLE = "php://stdout";

		protected $fileHandle;
		protected $filePath;

		public function __construct(Logger $logger, $name, Configuration $config = null) {
			parent::__construct($logger, $name, $config);
	
			$this->config = $this->config->extend([
				'dir' => $this->logger->getContext()->getDefaultLogPath(),
	            'filename' => "{app_name}_{name}",
	            'extension' => "log",
	            'permissions' => 0777,
	            'truncation' => '10MB'
			]);

			$this->setLogFilePath($this->config->get('dir'));
			$this->setFileHandle('a');
		}

		public function __destruct() {
			if ($this->fileHandle) fclose($this->fileHandle);
		}

		public function setFileHandle($writeMode = 'w+') {
            $this->fileHandle = fopen($this->filePath, $writeMode);
        }

        public function setLogFilePath($directory) {
        	$filename = $this->generateFileName();
        	$extension = $this->config->get('extension', 'log');

        	if (!file_exists($directory)) {
                mkdir($directory, $this->config->get('permissions'), true);
            }

        	if ($filename && $directory) {
                if (strpos($filename, '.log') !== false || strpos($filename, '.txt') !== false) {
                    $this->filePath = $directory . DIRECTORY_SEPARATOR . $filename;
                } else {
                    $this->filePath = $directory . DIRECTORY_SEPARATOR . "{$filename}.{$extension}";
                }
            }
        }

		public function log($message) {
			if (!fwrite($this->fileHandle, $message)) {
				throw new RuntimeException("{$this->filePath} could not be written to. Check that appropriate permissions have been set.");
			}
		}

		private function generateFileName() {
			$fileNameTemplate = $this->config->get('filename');
			$fields = ['app_name', 'name', 'date'];

            foreach ($fields as $field) {
                $logger = $this->getLogger();
                $handler = $this;
                $fileNameTemplate = preg_replace_callback("/\{{$field}\}/", function($matches) use ($logger, $handler, $field) {
                    if ($field == 'app_name') return $logger->getContext()->getApplicationName();
                    else if ($field == 'name') return $handler->getName();
                    else if ($field == 'date') return date('Y-m-d');
                    else return $field;
                }, $fileNameTemplate);
            }

            return $fileNameTemplate;
		}
	}