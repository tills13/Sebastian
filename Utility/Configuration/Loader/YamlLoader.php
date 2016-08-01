<?php
    namespace Sebastian\Utility\Configuration\Loader;

    use Sebastian\Core\Context\ContextInterface;
    use Sebastian\Utility\Configuration\Configuration;

    class YamlLoader extends Loader {
        protected $directory;

        public function load($filename, $directory = null) {
            $filename = $this->getProperFilename($filename, $directory);

            $this->directory = dirname(realpath($filename));
            $config = yaml_parse_file($filename);

            foreach ($config as $key => &$value) {
                $value = $this->processNode($key, $value);
            }

            return new Configuration($config);
        }

        public function processNode($key, $value) {
            if (is_array($value)) {
                foreach ($value as $key => &$mValue) {
                    $mValue = $this->processNode($key, $mValue);
                }
            } else if (is_string($value)) {
                $matches = [];
                if (preg_match('/\\@import ?\\(?\"?([^\"\\)]+)\"?\\)?/', $value, $matches)) {
                    $file = $matches[1];
                    $value = $this->load($file);
                }
            }            
            
            return $value;
        }

        public function getValidExtensions() {
            return ['yaml', 'yml'];
        }
    }