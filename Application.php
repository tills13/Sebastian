<?php
    namespace Sebastian;

    use Sebastian\Core\Component\Component;
    use Sebastian\Core\Context\Context;
    use Sebastian\Core\Exception\SebastianException;
    use Sebastian\Core\Http\Exception\HttpException;
    use Sebastian\Core\Http\Router;
    use Sebastian\Core\Http\Request;
    use Sebastian\Core\Http\Response\JsonResponse;
    use Sebastian\Core\Http\Response\Response;
    use Sebastian\Core\Templating\SRender;

    use Sebastian\Utility\Exception\Handler\ExceptionHandlerInterface;
    //use Sebastian\Utility\Logger\Logger;
    use Sebastian\Utility\Collection\Collection;
    use Sebastian\Utility\Configuration\Configuration;
    use Sebastian\Utility\Utility\Utils;

    /**
     * Application
     * @author Tyler <tyler@sbstn.ca>
     * @since  Oct. 2015
     */
    class Application extends Context {
        protected $kernel;
        protected $config;

        protected $services;
        protected $exceptionHandlers;

        public function __construct(Kernel $kernel, Configuration $config = null) {
            parent::__construct();

            $this->kernel = $kernel;
            $this->config = $config;
            $this->exceptionHandlers = [];
        }

        public function __call($method, $args) {
            $extension = parent::__call($method, $args);

            if ($extension == null) {
                return $this->kernel->$method($args);
            }
        }

        public function __get($offset) {
            $extension = parent::__get($offset);

            if (!$extension) {
                return $this->kernel->{$offset};
            }
        }

        public function get($offset) {
            return $this->{$offset};
        }

        public function preHandle() {
            $this->registerServices();
        }

        public function shutdown(Request $request, Response $response) {
            $this->connection->close();
        }

        public function registerExceptionHandler(ExceptionHandlerInterface $handler) {
            $this->exceptionHandlers[] = $handler;
        }

        /*public function checkComponentRequirements() {
            $context = $this;
            $this->components = array_filter($this->getComponents(true), function($component) use ($context) {
                return $component->checkRequirements($context);
            });

            foreach ($this->components as $component) $component->setup();
        }*/

        public function registerServices() {
            $services = $this->config->sub('service');

            foreach ($services as $key => $serviceDefinition) {
                $params = $serviceDefinition->get('params');
                $class = $serviceDefinition->get('class');

                if (strpos($class, ':') >= 0) {
                    $class = explode(':', $class);
                    $component = $this->getComponent($class[0]);
                    $class = $class[1];

                    $classPath = "\\{$this->getNamespace()}\\{$component->getNamespacePath()}\\{$class}";
                    $service = new $classPath();
                } else {

                }

                $service->boot();
                $this->services[$key] = $service;
            }
        }

        public function getApplicationName() {
            return $this->config->get('application.name');
        }

        public function getComponent($name = null) {
            return $this->kernel->getComponent($name);
        }

        /**
         * @todo standardize weights - higher > lower or lower > higher?
         */
        public function getComponents($sortByWeight = false) {
            return $this->kernel->getComponents($sortByWeight);
        }

        public function getConfig() {
            return $this->config;
        }

        public function getNamespace() {
            return $this->config->get('application.namespace');
        }

        public function getRequest() {
            return $this->kernel->getRequest();
        }

        public function getService($name) {
            return isset($this->services[$name]) ? $this->services[$name] : null;
        }

        public function getDefaultLogPath() {
            $name = strtolower($this->config->get('application.name'));
            return "/var/log/{$name}";
        }

        public function getLogger() {
            //return $this->logger;
        }
    }
