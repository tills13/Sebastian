<?php
    namespace Sebastian\Core\DependencyInjection;

    class Dependency {
        protected $isCallable = false;
        protected $defaultValue = null;

        public function __construct(ReflectionParameter $param) {
            $this->defaultValue = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null;
            $this->isCallable = $context->isCallable();
        }

        public function getValue() {
            
        }
    }