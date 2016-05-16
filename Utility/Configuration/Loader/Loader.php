<?php
    namespace Sebastian\Utility\Configuration\Loader;

    use \Exception;
    use Sebastian\Utility\Utility\Utils;
    use Sebastian\Core\Context\ContextInterface;

    abstract class Loader {
        protected $context;
        protected $directory;

        public function __construct(ContextInterface $context) {
            $this->context = $context;
        }

        public abstract function load($filename);
        protected abstract function processNode($key, $value);
        protected abstract function getValidExtensions();

        protected function getProperFilename($filename) {
            if (!Utils::endsWith($filename, $this->getValidExtensions())) {
                $filename = "{$filename}.{$this->getValidExtensions()[0]}"; // just use the first
            }

            if (strstr($filename, ':')) {
                if (count($this->context->getComponents()) == 0) {
                    throw new Exception("Cannot ");
                }
                //var_dump($this->context->getComponents());
                //if ($this->context->getComponents() )
                $component = substr($filename, 0, strpos($filename, ':'));
                $filename = substr($filename, strpos($filename, ':') + 1);
                var_dump($component);
                var_dump($filename);
                die();
            }

            if (!file_exists($filename)) {
                if (!is_null($this->directory) && file_exists($this->directory . DIRECTORY_SEPARATOR . $filename)) {
                    $filename = $this->directory . DIRECTORY_SEPARATOR . $filename;
                } else {
                    throw new \Exception("{$filename} does not exist");
                }
            }

            return $filename;
        }
    }