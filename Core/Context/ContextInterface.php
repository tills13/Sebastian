<?php
    namespace Sebastian\Core\Context;

    interface ContextInterface {
        public function __call($method, $arguments);
        public function get($id);
        public function has($id): boolean;
    }