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
            if (Utils::startsWith($method, 'get')) {
                $method = substr($method, 3);
                $method[0] = strtolower($method);
            }

            return $this->extensions->get($method);
        }
    }