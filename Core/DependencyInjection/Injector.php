<?php
    namespace Sebastian\Core\DependencyInjection;

    use \ReflectionClass;
    use \ReflectionMethod;
    use \ReflectionParameter;

    use Sebastian\Core\Http\Request;

    class Injector {
        //protected static $dependencies;
        //protected static $context;

        //protected $symbol;
        //
        protected static $globalDependencies;
        protected $reflection;

        public static function init($globals) {
            self::$globalDependencies = $globals;
        }

        public static function create(string $class) {
            $reflection = new ReflectionClass($class);
            $constructor = $reflection->getConstructor();

            $dependencies = self::resolveMethod($constructor);
            return $reflection->newInstanceArgs($dependencies);
        }

        public static function resolveMethod(ReflectionMethod $method) {
            $parameters = ($method == null) ? [] : $method->getParameters();

            $dependencies = [];
            foreach ($parameters as $parameter) {
                $name = $parameter->getName();
                $class = $parameter->getClass();
                $className = $class ? $class->getShortName() : "";

                if (isset(self::$globalDependencies["@{$className}"])) {
                    $dependencies[] = self::$globalDependencies["@{$className}"];
                } else if (isset(self::$globalDependencies["{$name}"])) {
                    $dependencies[] = self::$globalDependencies["{$name}"];
                } else if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    $dependencies[] = null;
                }
            }

            return $dependencies;
        }

        public static function resolveParameter(ReflectionParameter $param) {

        }

        public function resolveParameters() {

        }
    }