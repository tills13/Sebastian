<?php
    namespace Sebastian\Core\DependencyInjection;

    use \ReflectionClass;
    use \ReflectionFunction;
    use \ReflectionMethod;
    use \ReflectionParameter;

    use Sebastian\Utility\ClassMapper\ClassMapper;
    use Sebastian\Core\Http\Request;
    use Sebastian\Core\Model\EntityInterface;

    class Injector {
        protected static $instance;

        protected $dependencies;
        protected $reflection;

        public function __constructor() {
            $this->dependencies = [];
            $this->reflection = null; 
        }

        public static function getInstance() : Injector {
            if (!self::$instance) {
                Injector::$instance = new Injector();
            }

            return self::$instance;
        }

        public static function instance(string $class, $dependencies = []) {
            $reflection = new ReflectionClass($class);
            $constructor = $reflection->getConstructor();

            return $reflection->newInstanceArgs(self::resolveMethod($constructor, $dependencies));
        }

        public static function register(array $dependencies = []) {
            $instance = Injector::getInstance();
            $instance->addDependencies($dependencies);
        }

        public static function resolve(string $location, string $extra = null) {
            list($component, $class, $method) = ClassMapper::parse($location, $extra);
            $reflection = new ReflectionClass($class);

            $method = $method ? $reflection->getMethod($method) : $reflection->getConstructor();
            return self::resolveMethod($method);
        }

        public static function resolveMethod(ReflectionMethod $method, array $dependencies = []) {
            $instance = Injector::getInstance();
            $instance->addDependencies($dependencies);
            $parameters = ($method == null) ? [] : $method->getParameters();

            return $instance->resolveParameters($parameters);
        }

        public static function resolveCallable(Callable $callable, array $dependencies = []) {
            $instance = Injector::getInstance();
            $instance->addDependencies($dependencies);

            $reflection = new ReflectionFunction($callable);
            $parameters = $reflection->getParameters();

            return $instance->resolveParameters($parameters);
        }

        public function resolveParameters(array $parameters) {
            $dependencies = [];
            foreach ($parameters as $index => $parameter) {
                $name = $parameter->getName();
                $class = $parameter->getClass();
                $param = $class ? $class->getShortName() : $name;

                if ($class && is_subclass_of($class->name, EntityInterface::class)) {
                    /*if ($this->entityManager) {
                        $repo = $this->entityManager->getRepository($class->name);
                        $dependency = $repo->get($this->getDependency($name));
                        $dependencies[] = $dependency;
                    }*/
                } else {
                    $dependency = $this->getDependency("@{$param}") ??
                                $this->getDependency("{$param}") ??
                                ($parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null);

                    $dependencies[] = $dependency;
                }
            }

            return $dependencies;
        }

        public function addDependencies(array $dependencies = []) {
            $dependencies = array_change_key_case($dependencies, CASE_LOWER);

            $this->setDependencies(array_merge(
                $this->getDependencies() ?? [], 
                $dependencies
            ));
        }

        public function setDependencies($dependencies) {
            $this->dependencies = $dependencies;
        }
        
        public function getDependencies() {
            return $this->dependencies;
        }

        public function getDependency(string $dependency) {
            return $this->getDependencies()[strtolower($dependency)] ?? null;
        }

        public function hasDependency($dependency) {
            return isset($this->getDependencies()[$dependency]);
        }
    }