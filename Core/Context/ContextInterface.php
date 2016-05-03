<?php
    namespace Sebastian\Core\Context;

    interface ContextInterface {
        public function __call($method, $arguments);
    }