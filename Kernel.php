<?php   
    namespace Sebastian;

    define('SEBASTIAN_ROOT', __DIR__);

    use APP_ROOT;
    use Sebastian\Core\Cache\CacheManager;
    use Sebastian\Core\Component\Component;
    use Sebastian\Core\Context\Context;
    use Sebastian\Core\Database\Connection;
    use Sebastian\Utility\Configuration\Configuration;
    use Sebastian\Utility\Configuration\Loader\YamlLoader;

    use Sebastian\Core\Exception\SebastianException;
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

        public function __construct($environment) {
            parent::__construct();

            $this->components = [];
            $this->configLoader = new YamlLoader($this);
            $this->environment = $environment;
            $this->request = Request::fromGlobals();
            $this->router = Router::getRouter($this);
        }

        public function boot() {
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
                //var_dump($e); die();
                if ($e instanceof HttpException) {
                    $response = new Response($e->getMessage() ?: get_class($e));
                    $response->setResponseCode($e->getHttpResponseCode());
                    return $response;
                } else {
                    $request = $this->getRequest();

                    if ($request->isXmlHttpRequest()) {
                        $code = ($e instanceof HttpException) ? $e->getHttpResponseCode() : Response::HTTP_INTERNAL_SERVER_ERROR;
                        return new JsonResponse([
                            'error' => $e->getMessage()
                        ], $code);
                    } else {
                        return new Response($this->get('templating')->render('exception/exception', [
                            'exception' => $e
                        ]));
                    }
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
            $this->components[strtolower($component->getName())] = $component;
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