<?php
    namespace Sebastian;

    use Sebastian\Core\Cache\CacheManager;
    use Sebastian\Core\Component\Component;
    use Sebastian\Core\Configuration\Configuration;
    use Sebastian\Core\Database\Connection;
    use Sebastian\Core\Database\EntityManager;
    use Sebastian\Core\Exception\SebastianException;
    use Sebastian\Core\Http\Router;
    use Sebastian\Core\Http\Request;
    use Sebastian\Core\Http\Response\Response;
    use Sebastian\Core\Session\Session;

    use Sebastian\Component\Collection\Collection;
    use Sebastian\Component\Exception\Handler\ExceptionHandlerInterface;
    use Sebastian\Component\Logging\Logger;


    /**
     * Application
     * @author Tyler <tyler@sbstn.ca>
     * @since  Oct. 2015
     */
    abstract class Application {
        protected $kernel;
        protected $config;

        protected $cacheManager;
        protected $connection;
        protected $entityManager;

        protected $components;
        protected $services;
        protected $exceptionHandlers;

        public function __construct(Kernel $kernel, $config) {
            $this->kernel = $kernel;
            $this->config = $config;
            $this->exceptionHandlers = [];

            $this->components = [];
            
            //$this->logger = new Logger($this, $config->sub('application.logging'));
            $this->loggers = new Collection();
            $this->cacheManager = new CacheManager($this, $config->sub('cache'));
            $this->connection = new Connection($this, $config->sub('database'));
            $this->entityManager = new EntityManager($this, $config->sub('entity'));

            $this->session = Session::fromGlobals($this);
            $this->registerComponents();

            if (count($this->components) == 0) {
                throw new SebastianException("At least one component must be registered");
            }

            $this->router = Router::getRouter($this);
            $this->registerServices();
        }

        public function handle(Request $request) {
            try {
                $resolved = $this->router->resolve($request);

                $controllerClass = $resolved->get('controller');
                $controller = new $controllerClass($this);

                $method = $resolved->get('method');
                $arguments = $resolved->get('arguments');

                if (!method_exists($controller, $method)) {
                    throw new SebastianException("The requested method (<strong>{$method}</strong>) doesn't exist", 400);
                }
                
                $response = call_user_func_array([$controller, $method], $arguments->toArray());

                if ($response == null || !$response instanceof Response) {
                    throw new SebastianException("Controller must return a response.", 1);
                } else return $response;
            } catch (SebastianException $e) {
                foreach ($this->exceptionHandlers as $handler) {
                    if ($handler->onException($e)) { // handled
                        break;
                    }
                }
                
                die();
                //return new Response($e->getMessage());
            }
        }

        public function shutdown(Request $request, Response $response) {
            
        }

        public function registerExceptionHandler(ExceptionHandlerInterface $handler) {
            $this->exceptionHandlers[] = $handler;
        }

        abstract function registerComponents();

        public function registerComponent(Component $component) {
            $this->components[strtolower($component->getName())] = $component;
        }

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

        public function getComponent($name) {
            $name = strtolower($name);
            return isset($this->components[$name]) ? $this->components[$name] : null;
        }

        public function getCacheManager() {
            return $this->cacheManager;
        }

        public function getComponents() {
            return $this->components;
        }

        public function getConnection() {
            return $this->connection;
        }

        public function getEntityManager() {
            return $this->entityManager;
        }

        public function getNamespace() {
            return $this->config->get('application.namespace');
        }

        public function getRequest() {
            return $this->kernel->getRequest();
        }

        public function getRouter() {
            return $this->router;
        }

        public function getService($name) {
            return isset($this->services[$name]) ? $this->services[$name] : null;
        }

        public function getSession() {
            return $this->session;
        }

        public function getDefaultLogFilename() {
            return "{$this->config->get('application.name')}.log";
        }

        public function getDefaultLogPath() {
            return "/var/log/{$this->config->get('application.name')}/";
        }

        public function getLogger($name = 'default', $options = []) {
            if ($this->loggers->has($name)) {
                return $this->loggers->get($name);
            } else {
                $this->loggers->set($name, new Logger($this, new Configuration([])));
                return $this->getLogger($name);
            }
        }
    }
