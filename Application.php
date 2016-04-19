<?php
    namespace Sebastian;

    use Sebastian\Core\Cache\CacheManager;
    use Sebastian\Core\Component\Component;
    use Sebastian\Core\Database\Connection;
    use Sebastian\Core\Database\EntityManager;
    use Sebastian\Core\Exception\SebastianException;
    use Sebastian\Core\Http\Router;
    use Sebastian\Core\Http\Request;
    use Sebastian\Core\Http\Response\Response;
    use Sebastian\Core\Session\Session;
    use Sebastian\Core\Templating\SRender;

    use Sebastian\Utility\Exception\Handler\ExceptionHandlerInterface;
    use Sebastian\Utility\Logging\Logger;
    use Sebastian\Utility\Collection\Collection;
    use Sebastian\Utility\Configuration\Configuration;

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

        protected $extensions = [];

        public function __construct(Kernel $kernel, Configuration $config = null) {
            $this->kernel = $kernel;
            $this->config = $config;

            $this->components = [];
            $this->extensions = [];
            $this->exceptionHandlers = [];
            
            $this->logger = new Logger($this, $config->sub('logging'));
            $this->cacheManager = new CacheManager($this, $config->sub('cache'));
            $this->connection = new Connection($this, $config->sub('database'));
            $this->entityManager = new EntityManager($this, $config->sub('entity'));

            $this->session = Session::fromGlobals($this);
            $this->router = Router::getRouter($this);
        }

        public function preHandle() {
            $this->checkComponentRequirements();
            $this->router->loadRoutes();

            $this->extensions['templating'] = new SRender($this, null, array_map(function($component) {
                return $component->getResourceUri('views', true);
            }, $this->getComponents(true)));

            $this->registerServices();
        }

        public function handle(Request $request) {
            try {
                $resolved = $this->router->resolve($request);

                $controller = $resolved->get('controller');
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

        public function registerComponents(array $components) {
            foreach ($components as $component) {
                if (!$component instanceof Component) throw new SebastianException("component must extend Sebastian\Component");
                $this->registerComponent($component);
            }
        }

        public function checkComponentRequirements() {
            $context = $this;
            $this->components = array_filter($this->getComponents(true), function($component) use ($context) {
                return $component->checkRequirements($context);
            });
        }

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

        public function get($extension) {
            if (isset($this->extensions[$extension])) {
                return $this->extensions[$extension];
            }

            throw new SebastianException("Extension {$extension} not found");
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

        /**
         * @todo standardize weights - higher > lower or lower > higher?
         */
        public function getComponents($sortByWeight = false) {
            if ($sortByWeight) {
                uasort($this->components, function($componentA, $componentB) {
                    return $componentA->getWeight() > $componentB->getWeight();
                });
            }

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
