<?php   
    namespace Sebastian;

    define('SEBASTIAN_ROOT', __DIR__);

    use APP_ROOT;
    use Sebastian\Core\Cache\CacheManager;
    use Sebastian\Core\Component\Component;
    use Sebastian\Core\Context\Context;
    use Sebastian\Core\Database\Connection;
    use Sebastian\Core\DependencyInjection\Injector;
    use Sebastian\Utility\ClassMapper\ClassMapper;
    use Sebastian\Utility\Configuration\Configuration;
    use Sebastian\Utility\Configuration\Loader\YamlLoader;

    use Sebastian\Core\Exception\SebastianException;
    use Sebastian\Core\Http\Exception\HttpException;
    use Sebastian\Core\Http\Firewall;
    use Sebastian\Core\Http\Response\JsonResponse;
    use Sebastian\Core\Http\Response\Response;
    use Sebastian\Core\Http\Request;
    use Sebastian\Core\Http\Router;

    /**
     * Kernel
     * @author Tyler <tyler@sbstn.ca>
     * @since  Oct. 2015
     */
    class Kernel extends Context {
        protected $application;
        protected $components;
        protected $config;
        protected $configLoader;
        protected $environment;
        protected $request;
        protected $router;

        public function __construct($environment = "prod") {
            parent::__construct();

            $this->components = [];
            $this->configLoader = new YamlLoader($this);
            $this->environment = $environment;
            $this->request = Request::fromGlobals();
            $this->router = Router::getRouter($this);

            Injector::init([
                '@request' => $this->request,
                '@Request' => $this->request,
                '@router' => $this->router,
                '@Router' => $this->router,
            ]);
        }

        public function boot() {
            ClassMapper::init($this->getComponents());
            Firewall::init($this);

            try {
                $this->config = Configuration::fromFilename("config_{$this->environment}.yaml");
                $this->cacheManager = new CacheManager($this, $this->config->sub('cache'));
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
            } catch (Exception $e) {
                if ($this->templating) {
                    return new Response($this->get('templating')->render('exception/exception', [
                        'exception' => $e
                    ]));
                } else {
                    return new Response($e->getMessage());
                }
            }
        }

        public function handle(Request $request) {
            if ($response = Firewall::handle($request) instanceof Response) {
                return $response;
            }

            try {
                $resolved = $this->router->resolve($request);

                $controller = $resolved->get('controller');
                $method = $resolved->get('method');
                $arguments = $resolved->get('arguments');

                if (!method_exists($controller, $method)) {
                    throw new SebastianException("The requested method (<strong>{$controller}:{$method}</strong>) doesn't exist", 400);
                }

                $response = call_user_func_array([$controller, $method], $arguments->toArray());

                if ($response == null || !$response instanceof Response) {
                    throw new SebastianException("Controller must return a response.", 1);
                } else return $response;
            } catch (\Exception $e) {
                $request = $this->getRequest();

                if ($request->isXmlHttpRequest() || strpos($request->route(), '/api/') === 0) {
                    $code = ($e instanceof HttpException) ? $e->getHttpResponseCode() : Response::HTTP_INTERNAL_SERVER_ERROR;
                    return new JsonResponse([
                        'error' => $e->getMessage(),
                        'code' => $code
                    ], $code);
                } else {
                    return new Response($this->get('templating')->render('exception/exception', [
                        'exception' => $e
                    ]));
                }
            }
        }

        public function run($params = null) {
            $this->application->preHandle();
            return $this->handle($this->request);
        }

        public function shutdown(Response $response) {
            $this->application->shutdown($this->request, $response);
        }

        public function getApplication() {
            return $this->application;
        }

        public function registerComponents(array $components) {
            foreach ($components as $component) {
                if (!$component instanceof Component) throw new SebastianException("component must extend Sebastian\Component");
                $this->registerComponent($component);
            }
        }

        public function registerComponent(Component $component) {
            $this->components[$component->getName()] = $component;
            $this->components[strtolower($component->getName())] = $component; // @todo to be case insensitive?
        }

        public function setupComponents() {
            uasort($this->components, function($componentA, $componentB) {
                return $componentA->getWeight() > $componentB->getWeight();
            });

            foreach ($this->getComponents() as $key => $component) {
                if (!$component->checkRequirements($this)) {
                    unset($this->components[$key]);
                    continue;
                }
                
                $component->setup($this->config);
            }
        }

        public function getComponent($name) {
            return $this->components[strtolower($name)];
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