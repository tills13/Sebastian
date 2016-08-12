<?php
    namespace Sebastian\Core\DependencyInjection;

    use \ReflectionClass;
    use \ReflectionMethod;
    use \ReflectionParameter;

    use Sebastian\Utility\ClassMapper\ClassMapper;
    use Sebastian\Core\Http\Request;

    class Injector {
        protected static $instance;

        protected $dependencies;
        protected $reflection;

        public function __constructor() {
            $this->dependencies = [];
            $this->reflection = null; 
        }

        public static function getInstance() : Injector {
            if (!Injector::$instance) {
                Injector::$instance = new Injector();
            }

            return Injector::$instance;
        }

        public static function register(array $variables = []) {
            $variables = array_change_key_case($variables, CASE_LOWER);

            $instance = Injector::getInstance();
            $instance->setDependencies(array_merge(
                $instance->getDependencies() ?? [], 
                $variables
            ));
        }

        public static function resolve(string $location, string $extra = null) {
            list($component, $class, $method) = explode(':', $location);

            $class = ClassMapper::parse("{$component}:{$class}", $extra);
            $reflection = new ReflectionClass($class);

            $method = $method ? $reflection->getMethod($method) : $reflection->getConstructor();
            return self::resolveMethod($method);
        }

        public static function resolveMethod(ReflectionMethod $method) {
            $instance = Injector::getInstance();
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

        public function setDependencies($dependencies) {
            $this->dependencies = $dependencies;
        }
        
        public function getDependencies() {
            return $this->dependencies;
        }

        public function getDependency($dependency) {
            return $this->getDependencies()[strtolower($dependency)];
        }

        public function hasDependency($dependency) {
            return isset($this->getDependencies()[$dependency]);
        }






        /*public static function create(string $class) {
            $reflection = new ReflectionClass($class);
            $constructor = $reflection->getConstructor();

            $dependencies = self::resolveMethod($constructor);
            return $reflection->newInstanceArgs($dependencies);
        }

        public static function resolveClass() {
            
        }

        public static function resolveParameter(ReflectionParameter $param) {

        }

        public function resolveParameters() {

        }*/
    }