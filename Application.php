<?php
    namespace Sebastian;

    use Sebastian\Core\Cache\CacheManager;
    use Sebastian\Core\Component\Component;
    use Sebastian\Utility\Configuration\Configuration;
    use Sebastian\Core\Database\Connection;
    use Sebastian\Core\Database\EntityManager;
    use Sebastian\Core\Exception\SebastianException;
    use Sebastian\Core\Http\Router;
    use Sebastian\Core\Http\Request;
    use Sebastian\Core\Http\Response\Response;
    use Sebastian\Core\Session\Session;

    use Sebastian\Utility\Exception\Handler\ExceptionHandlerInterface;
    use Sebastian\Utility\Logging\Logger;
    use Sebastian\Utility\Collection\Collection;

    /**
     * Application
     * @author Tyler <tyler@sbstn.ca>
     * @since  Oct. 2015
     */
    class Application {
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
            
            $this->logger = new Logger($this, $config->sub('logging'));
            $this->cacheManager = new CacheManager($this, $config->sub('cache'));
            $this->connection = new Connection($this, $config->sub('database'));
            $this->entityManager = new EntityManager($this, $config->sub('entity'));

            $this->session = Session::fromGlobals($this);
            $this->registerComponents();

            //print (count($this->components) . " components loaded");
            //print ("using " . $this->getComponent()->getName());
            //$this->checkComponentRequirements();

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
            } catch (\Exception $e) {
                foreach ($this->exceptionHandlers as $handler) {
                    if ($handler->onException($e)) { // handled
                        break;
                    }
                }
                
                die();
            }
        }

        public function shutdown(Request $request, Response $response) {
            $this->connection->close();
        }

        public function registerExceptionHandler(ExceptionHandlerInterface $handler) {
            $this->exceptionHandlers[] = $handler;
        }

        public function registerComponents() {
            // todo
        }

        public function checkComponentRequirements() {
            foreach ($this->components as $component) {
                if (!$component->hasRequirements()) continue;
                if (!$component->checkRequirements($this)) $component->setEnabled(false);
            }
        }

        public function registerComponent(Component $component) {
            if ($component->checkRequirements($this)) {
                $this->components[strtolower($component->getName())] = $component;
            }
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

        public function getApplicationName() {
            return $this->config->get('application.name');
        }

        public function getComponent($name = null) {
            if (is_null($name)) {
                $component = null;
                foreach ($this->getComponents() as $mComponent) {
                    if (!$component || $mComponent->getWeight() > $component->getWeight()) {
                        $component = $mComponent;
                    }
                }

                return $component;
            } else {
               $name = strtolower($name);
                return isset($this->components[$name]) ? $this->components[$name] : null; 
            }
        }

        public function getCacheManager() {
            return $this->cacheManager;
        }

        public function getComponents() {
            return $this->components;
        }

        public function getConfig() {
            return $this->config;
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

        public function getDefaultLogPath() {
            $name = strtolower($this->config->get('application.name'));
            return "/var/log/{$name}";
        }

        public function getLogger() {
            return $this->logger;
        }
    }
