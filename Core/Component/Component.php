<?php
    namespace Sebastian\Core\Component;

    use Sebastian\Core\Exception\SebastianException;
    use Sebastian\Core\Context\ContextInterface;
    use Sebastian\Utility\Collection\Collection;
    use Sebastian\Utility\Configuration\Configuration;
    use Sebastian\Utility\Utility\Utils;

    use \ReflectionClass;

    class Component {
        protected $config;
        protected $context;
        protected $name;
        protected $namespace;
        protected $requirements;
        protected $weight;
        protected $path;
        protected $routePrefix;

        private $reflection;

        public function __construct(ContextInterface $context, $name, Configuration $config = null) {
            $this->context = $context;
            $this->name = $name;
            $this->requirements = new Collection();
            $this->routePrefix = null;

            $this->config = $config ?? $context->getConfig()->sub("components.{$this->name}", []);
            $this->weight = $this->config->get('weight', 0);
            $this->reflection = new ReflectionClass(get_class($this));
        }

        public function setup() {
            
        }

        public function shutdown() {

        }

        public function setConfig(Configuration $config) {
            $this->config = $config;
        }

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

        public function getComponentDirectory(bool $trailingSlash = true) : string {
            if ($this->path === null) {
                $this->path = dirname($this->reflection->getFileName());
            }

            return $this->path . ($trailingSlash ? DIRECTORY_SEPARATOR : "");
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
            if ($this->namespace === null) {
                $this->namespace = $this->reflection->getNamespaceName();
            }

            return $this->namespace;
        }

        public function getNamespacePath() {
            return str_replace('\\', '/', $this->getNamespace());
        }

        public function getResourceUri($uri, $absolute = false) {
            if ($absolute) {
                return implode(DIRECTORY_SEPARATOR, [$this->getComponentDirectory(false), "Resources", $uri]);
            } else {
                return implode(DIRECTORY_SEPARATOR, [$this->getNamespacePath(), "Resources", $uri]);
            }
        }

        public function hasRoutingFile() {
            return file_exists($this->getComponentDirectory() . "routing.yaml") || 
                   file_exists($this->getComponentDirectory() . "routing.yml");
        }

        public function getRoutingFile() {
            if (file_exists($this->getComponentDirectory() . "routing.yaml")) {
                return $this->getComponentDirectory() . "routing.yaml";
            } else if (file_exists($this->getComponentDirectory() . "routing.yml")) {
                return $this->getComponentDirectory() . "routing.yml";
            }

            throw new SebastianException("Routing file not found, expecting {$this->getComponentDirectory()}.[yaml|yml]");
        }

        public function setRoutePrefix($routePrefix) {
            $this->routePrefix = $routePrefix;
        }

        public function getRoutePrefix() : string {
            return $this->routePrefix;
        }

        public function setWeight($weight) {
            $this->weight = $weight;
        }

        public function getWeight() : int {
            return $this->weight;
        }

        public function __toString() {
            return $this->name;
        }

        public function checkRequirements() {
            return true;
        }
    }