<?php
    namespace Sebastian;

    use \ReflectionClass;

    use Sebastian\Core\Component\Component;
    use Sebastian\Core\Context\Context;
    use Sebastian\Core\DependencyInjection\Injector;
    use Sebastian\Core\Exception\SebastianException;
    use Sebastian\Core\Http\Exception\HttpException;
    use Sebastian\Core\Http\Router;
    use Sebastian\Core\Http\Request;
    use Sebastian\Core\Http\Response\JsonResponse;
    use Sebastian\Core\Http\Response\Response;
    use Sebastian\Core\Service\ServiceInterface;

    use Sebastian\Utility\ClassMapper\ClassMapper;
    use Sebastian\Utility\Collection\Collection;
    use Sebastian\Utility\Configuration\Configuration;
    use Sebastian\Utility\Exception\Handler\ExceptionHandlerInterface;

    /**
     * Application
     * @author Tyler <tyler@sbstn.ca>
     * @since  Oct. 2015
     */
    class Application extends Context {
        protected $bootCalled = false;
        protected $config;
        protected $exceptionHandlers;
        protected $kernel;
        protected $services;

        public function __construct(Kernel $kernel, Configuration $config = null) {
            parent::__construct();

            $this->config = $config;
            $this->exceptionHandlers = [];
            $this->kernel = $kernel;
            $this->services = [];
        }

        public function boot() {
            $this->registerServices();
            $this->bootCalled = true;
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
            if (is_array($offset)) $offset = $offset[0];
            
            return $this->{$offset};
        }

        public function shutdown(Request $request, Response $response) {
            $this->connection->close();
        }

        public function registerExceptionHandler(ExceptionHandlerInterface $handler) {
            $this->exceptionHandlers[] = $handler;
        }

        public function registerServices() {
            foreach ($this->getConfig()->sub('services') as $name => $service) {
                if (!$service->get('lazy', false)) {
                    $service = Injector::instance($service->get('class'), 'Service');
                    $this->registerService($service, $name);
                }
            }
        }

        public function registerService(ServiceInterface $service, string $name, $aliases = []) {
            $this->services[$name] = $service;
            Injector::registerByClass($service);
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

        public function getDefaultLogPath() {
            $name = strtolower($this->config->get('application.name'));
            return "/var/log/{$name}";
        }

        public function getLogger() {
            //return $this->logger;
        }

        public function getNamespace() {
            return $this->config->get('application.namespace');
        }

        public function getRequest() {
            return $this->kernel->getRequest();
        }

        public function getService(string $name) {
            return $this->service[$name] ?? null;
        }
    }