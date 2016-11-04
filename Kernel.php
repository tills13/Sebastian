<?php   
    namespace Sebastian;

    define('SEBASTIAN_ROOT', __DIR__);

    use \Exception;

    use Sebastian\Core\Cache\CacheManager;
    use Sebastian\Core\Component\Component;
    use Sebastian\Core\Context\Context;
    use Sebastian\Core\Event\Event;
    use Sebastian\Core\Event\EventBus;
    use Sebastian\Core\Event\ViewEvent;
    use Sebastian\Core\Database\Connection;
    use Sebastian\Core\DependencyInjection\Injector;
    use Sebastian\Core\Exception\SebastianException;
    use Sebastian\Core\Http\Exception\HttpException;
    use Sebastian\Core\Http\Firewall;
    use Sebastian\Core\Http\Response\JsonResponse;
    use Sebastian\Core\Http\Response\Response;
    use Sebastian\Core\Http\Request;
    use Sebastian\Core\Http\Router;    

    use Sebastian\Utility\ClassMapper\ClassMapper;
    use Sebastian\Utility\Configuration\Configuration;
    use Sebastian\Utility\Configuration\Loader\YamlLoader;

    /**
     * Kernel
     * @author Tyler <tyler@sbstn.ca>
     * @since  Oct. 2015
     */
    class Kernel extends Context {
        protected $application;
        protected $config;
        protected $components;
        protected $environment;
        protected $mapper;
        protected $request;
        protected $router;

        public function __construct($environment = "prod") {
            parent::__construct();

            $this->components = [];
            $this->config = Configuration::fromFilename("config_{$environment}.yaml");
            $this->environment = $environment;
            $this->request = Request::fromGlobals();
            $this->router = Router::getRouter($this);

            Injector::register([
                '@kernel,@context,@contextinterface,$container' => $this,
                '@request' => $this->request,
                '@session' => $this->request->getSession(),
                '@router' => $this->router
            ]);

            $this->registerComponents([
                new Core\CoreComponent($this, "Sebastian\\Core"),
                new Internal\InternalComponent($this, "Sebastian\\Internal")
            ]);
        }

        /**
         * boot is run after all components have been registered.
         */
        public function boot() {
            $this->mapper = ClassMapper::getInstance($this);
            Firewall::init($this, $this->config->sub('firewall', []));

            try {
                $this->cacheManager = new CacheManager($this, $this->config->get('cache'));
                $this->connection = new Connection($this, $this->config->sub('database'));

                $this->setupComponents();
                $this->router->loadRoutes();

                if ($this->config->has('application.app_class')) {
                    $namespace = $this->config->get('application.namespace');
                    $appClass = $this->config->get('application.app_class');
                    $applicationPath = "\\{$namespace}\\{$appClass}";

                    $this->application = new $applicationPath($this, $this->config);
                } else {
                    $this->application = new Application($this, $this->config);
                }

                Injector::register(['@application' => $this->application]);
                $this->application->boot();
            } catch (Exception $e) {
                if ($this->templating) {
                    return new Response($this->templating->render('exception/exception', [
                        'exception' => $e
                    ]));
                } else {
                    return new Response($e->getMessage());
                }
            }
        }

        public function handle(Request $request) {
            if (($response = Firewall::handle($request)) instanceof Response) {
                return $response;
            }

            try {
                list($controller, $method, $arguments) = $this->router->resolve($request); 

                if (!method_exists($controller, $method)) {
                    throw new SebastianException("The requested method (<strong>{$controller}:{$method}</strong>) doesn't exist", 400);
                }

                $response = call_user_func_array([$controller, $method], $arguments);
                
                if ($response === null || !$response instanceof Response) {
                    $event = new ViewEvent($this->request, $response);
                    EventBus::trigger(Event::VIEW, $event);

                    $response = $event->getResponse();

                    if ($response === null || !$response instanceof Response) {
                        throw new SebastianException("Controller must return a response or a view");
                    }
                }

                Injector::register(['@response' => $response]);
                return $response;
            } catch (Exception $e) {
                $request = $this->getRequest();

                if ($request->isXmlHttpRequest() || strpos($request->route(), '/api/') === 0) {
                    $code = ($e instanceof HttpException) ? $e->getHttpResponseCode() : Response::HTTP_INTERNAL_SERVER_ERROR;
                    return new JsonResponse([
                        'error' => $e->getMessage(),
                        'code' => $code
                    ], $code);
                } else {
                    $errorTemplate = $this->getConfig()->get('components.Sebastian\Extra.templating.error_template', 'internal');
                    return new Response($this->get('templating')->render('exception/exception', [
                        'errorTemplate' => $errorTemplate, 
                        'exception' => $e
                    ]));
                }
            }
        }

        public function run($params = null) {
            EventBus::trigger(Event::PRE_REQUEST, new Event());
            return $this->handle($this->request);
        }

        public function shutdown(Response $response) {
            $this->application->shutdown($this->request, $response);
            $this->request->getSession()->close();            
            EventBus::trigger(Event::SHUTDOWN, null, $this->request, $response);
        }

        public function getApplication() {
            return $this->application;
        }

        public function registerComponents(array $components) {
            foreach ($components as $component) {
                if (!$component instanceof Component) {
                    throw new SebastianException("Component must extend Sebastian\Component");
                }

                $this->registerComponent($component);
            }
        }

        public function registerComponent(Component $component) {
            $this->components[$component->getName()] = $component;
        }

        /** @todo do better */
        public function setupComponents() {
            uasort($this->components, function($componentA, $componentB) {
                return $componentA->getWeight() > $componentB->getWeight();
            });

            foreach ($this->getComponents() as $key => $component) {
                $component->setup();
            }
        }

        public function getComponent($name) {
            return $this->components[$name];
        }

        public function getComponents() {
            return $this->components;
        }

        public function getConfig() {
            return $this->config;
        }

        public function getEnvironment() {
            return $this->environment;
        }

        public function getRequest() {
            return $this->request;
        }

        public function getRouter() {
            return $this->router;
        }
    }