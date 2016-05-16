<?php
    namespace Sebastian\Core\Context;

    use Sebastian\Core\Exception\SebastianException;
    use Sebastian\Utility\Collection\Collection;
    use Sebastian\Utility\Utility\Utils;

    class Context implements ContextInterface {
        protected $extensions;

        public function __construct() {
            $this->extensions = new Collection();
        }

        public function __call($method, $arguments) {
            if ($method == "get") $method = $arguments[0];
            else if (Utils::startsWith($method, 'get')) {
                $method = substr($method, 3);
                $method[0] = strtolower($method);
            }

            return $this->extensions->get($method);
        }

        public function __get($offset) {
            return $this->extensions->get($offset);
        }

        public function __set($offset, $value) {
            $this->extensions->set($offset, $value);
        }
    }