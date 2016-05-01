<?php
    namespace Sebastian\Core\Component;

    use Sebastian\Core\Exception\SebastianException;
    use Sebastian\Core\Context\ContextInterface;
    use Sebastian\Utility\Collection\Collection;
    use Sebastian\Utility\Configuration\Configuration;
    use Sebastian\Utility\Utility\Utils;

    use \ReflectionClass;

    abstract class Component {
        protected $context;
        protected $name;
        protected $config;
        protected $requirements;
        protected $weight;
        protected $path;
        protected $enabled;
        protected $routePrefix;

        private $reflection;

        public function __construct(ContextInterface $context, $name, Configuration $config = null) {
            $this->context = $context;
            $this->name = $name;
            $this->config = $config ?: new Configuration();

            $this->requirements = new Collection();
            $this->weight = 0;
            $this->enabled = true;
            $this->routePrefix = null;

            $this->reflection = new ReflectionClass(get_class($this));
        }

        public function setup() {}

        public function getConfig() {
            return $this->config;
        }

        public function getContext() {
            return $this->context;
        }

        public function setEnabled($enabled) {
            $this->enabled = $enabled;
        }

        public function getEnabled() {
            return $this->enabled;
        }

        public function hasClass($class) {
            $classFile = str_replace('\\', DIRECTORY_SEPARATOR, $class);
            if (!Utils::endsWith($classFile, '.php')) $classFile = $classFile . ".php";
            $classFile = implode(DIRECTORY_SEPARATOR, [$this->getComponentDirectory(false), $classFile]);
            return file_exists($classFile);
        }

        public function getClass($class) {
            return implode('\\', [$this->getNamespace(), $class]);
        }

        public function getComponentDirectory($trailingSlash = true) {
            $location = $this->reflection->getFileName();
            $location = substr($location, 0, strrpos($location, '/') + ($trailingSlash ? 1 : 0));

            return $location;
        }

        public function hasController($controller = null) {
            if (!Utils::endsWith($controller, 'Controller')) $controller = $controller . "Controller";
            if (!Utils::endsWith($controller, '.php')) $controller = $controller . ".php";

            $path = implode(DIRECTORY_SEPARATOR, [$this->getComponentDirectory(), "Controller", $controller]);
            return file_exists($path);
        }

        public function getController($controller) {
            if (!Utils::endsWith($controller, 'Controller')) $controller = $controller . "Controller";

            return implode('\\', [$this->getNamespace(), "Controller", $controller]);
        }

        public function setName($name) {
            $this->name = $name;
        }

        public function getName() {
            return $this->name;
        }

        public function getNamespace() {
            return $this->reflection->getNamespaceName();
        }

        public function getNamespacePath() {
            return str_replace('/', '\\', $this->getNamespace());
        }

        public function setRequirements($requirements) {
            if ($requirements instanceof Collection) {
                $this->requirements = $requirements;
            } else if (is_array($requirements)) {
                $this->requirements = new Collection($requirements);
            } else {
                throw new \InvalidArgumentException("setRequirements requires either an array or ? extends Collection");
            }
        }

        public function getRequirements() {
            return $this->requirements;
        }

        public function hasRequirements() {
            return ($this->requirements != null && $this->requirements->count() != 0);
        }

        public function getResourceUri($uri, $absolute = false) {
            if ($absolute) {
                return implode(DIRECTORY_SEPARATOR, [$this->getComponentDirectory(false), "Resources", $uri]);
            } else {
                return implode(DIRECTORY_SEPARATOR, [$this->getNamespacePath(), "Resources", $uri]);
            }
        }

        public function getRoutingConfig() {
            $location = $this->getComponentDirectory() . "routing.yaml";
            return $location;

            throw new SebastianException("routing file not found: {$filename} or {$filename1}");
        }

        public function setRoutePrefix($routePrefix) {
            $this->routePrefix = $routePrefix;
        }

        public function getRoutePrefix() {
            return $this->routePrefix;
        }

        public function setWeight($weight) {
            $this->weight = $weight;
        }

        public function getWeight() {
            return $this->weight;
        }

        public function __toString() {
            return $this->name;
        }

        abstract public function checkRequirements(ContextInterface $context);
    }