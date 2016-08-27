<?php
    namespace Sebastian\Core\Context;

    use Sebastian\Core\Exception\SebastianException;
    use Sebastian\Utility\Collection\Collection;
    use Sebastian\Utility\Utility\Utils;

    class Context implements ContextInterface,\ArrayAccess {
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

        public function get($id) {
            return $this->extensions->get($id);
        }

        public function __get($offset) {
            return $this->extensions->get($offset);
        }

        public function __set($offset, $value) {
            $this->extensions->set($offset, $value);
        }

        public function has($id): boolean {
            return $this->extensions->has($id);
        }

        public function offsetExists($offset) {
            return $this->extensions->has($offset);
        }

        public function offsetGet($offset) {
            return $this->extensions->get($offset);
        }

        public function offsetSet($offset, $value) {
            $this->extensions->set($offset, $value);
        }

        public function offsetUnset($offset) {
            $this->extensions->remove($offset);
        }
    }