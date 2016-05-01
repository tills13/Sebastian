<?php
    namespace Sebastian;

    use Sebastian\Core\Cache\CacheManager;
    use Sebastian\Core\Component\Component;
    use Sebastian\Core\Context\ContextInterface;
    use Sebastian\Core\Database\Connection;
    use Sebastian\Core\Database\EntityManager;
    use Sebastian\Core\Exception\SebastianException;
    use Sebastian\Core\Http\Exception\HttpException;
    use Sebastian\Core\Http\Router;
    use Sebastian\Core\Http\Request;
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
    class Application implements ContextInterface {
        protected $kernel;
        protected $config;

        protected $router;
        protected $cacheManager;
        protected $connection;

        protected $components;
        protected $services;
        protected $exceptionHandlers;

        protected $extensions = [];

        public function __construct(Kernel $kernel, Configuration $config = null) {
            $this->kernel = $kernel;
            $this->config = $config;

            $this->components = [];
            $this->extensions = new Collection();
            $this->exceptionHandlers = [];
            
            //$this->logger = new Logger($this, $config->sub('logging'));
            $this->cacheManager = new CacheManager($this, $config->sub('cache'));
            $this->connection = new Connection($this, $config->sub('database'));
            //$this->entityManager = new EntityManager($this, $config->sub('entity'));

            $this->router = Router::getRouter($this);
        }

        public function preHandle() {
            $this->checkComponentRequirements();
            $this->router->loadRoutes();

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
                if ($e instanceof HttpException) {
                    $response = new Response($e->getMessage() ?: get_class($e));
                    $response->setResponseCode($e->getHttpResponseCode());
                    return $response;
                } else {
                    /*foreach ($this->exceptionHandlers as $handler) {
                        if ($handler->onException($e)) { // handled
                            break;
                        }
                    }*/

                    //if (isset($this->components['Internal'])) {
                        return new Response($this->get('templating')->render('exception/exception', [
                            'exception' => $e
                        ]));
                    //}

                    //die($e->getMessage());
                }
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

            foreach ($this->components as $component) $component->setup();
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

        public function __set($offset, $value) {
            $this->extensions->set($offset, $value);
        }

        public function get($offset) {
            return $this->{$offset};
        }

        public function __get($offset) {
            return $this->extensions->get($offset);
        }

        public function __call($method, $arguments = []) {
            if (Utils::startsWith($method, 'get')) {
                $method = substr($method, 3);
                $method[0] = strtolower($method);
                return $this->extensions->get($method);
            }

            if (!$this->extensions->has($method)) {
                throw new SebastianException();
            } else {
                return $this->extensions->get($method);
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

        public function getDefaultLogPath() {
            $name = strtolower($this->config->get('application.name'));
            return "/var/log/{$name}";
        }

        public function getLogger() {
            //return $this->logger;
        }
    }
