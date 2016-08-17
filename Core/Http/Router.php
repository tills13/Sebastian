<?php   
    namespace Sebastian\Core\Http;

    use Sebastian\SEBASTIAN_ROOT;

    use Sebastian\Core\Component\Component;
    use Sebastian\Core\Context\ContextInterface;
    use Sebastian\Core\Database\EntityManager;
    use Sebastian\Core\DependencyInjection\Injector;
    use Sebastian\Core\Exception\SebastianException;
    use Sebastian\Core\Http\Exception\HttpException;
    use Sebastian\Core\Http\Request;
    use Sebastian\Core\Session\Session;
    use Sebastian\Utility\ClassMapper\ClassMapper;
    use Sebastian\Utility\Collection\Collection;
    use Sebastian\Utility\Configuration\Configuration;
    use Sebastian\Utility\Utility\Utils;

    use \ReflectionClass;
    use \ReflectionMethod;
    
    /**
     * Router
     *
     * various methods for adding and resolving routes. 
     * 
     * @author Tyler <tyler@sbstn.ca>
     * @since Oct. 2015
     */
    class Router {
        public static $tag = "ROUTER";
        protected static $router;

        protected $routes;
        protected $context;

        /**
         * attempts to load the router (plus routes) from cache, if it's not
         * cached, load it normally
         * 
         * @param  Kernel $context application context
         * @return Router the router
         */
        public static function getRouter(ContextInterface $context) {
            if (self::$router == null) {
                self::$router = new Router($context);
            }

            return self::$router;
        }

        protected function __construct($context) {
            $this->context = $context;
            $this->routes = new Collection();

            $this->em = $context->getEntityManager();
            $this->cm = $context->getCacheManager();
            //$this->logger = $context->getLogger();
        }

        public function attachComponent(Component $component) {
            $prefix = $component->getRoutePrefix();
        }

        /**
         * load routes from the various routing files in component roots.
         * @return void
         */
        public function loadRoutes() {
            $components = $this->getContext()->getComponents();

            $paths = [
                \APP_ROOT . DIRECTORY_SEPARATOR . "../config/routing.yaml", // master routing file, if required
            ]; // add default routes

            $paths = $paths + array_map(function($component) {
                return $component->getRoutingConfig();
            }, $components);

            foreach ($paths as $index => $path) {
                if (!file_exists($path)) continue;
                
                $routes = Configuration::fromPath($path);

                if (!$routes || $routes->count() == 0) continue;

                foreach ($routes as $name => $mRoute) {
                    if ($mRoute->has('type') && $mRoute->get('type') === 'group') {
                        $this->addRouteGroup($name, $mRoute);
                    } else {
                        if (($use = $mRoute->get('use')) !== null) { // require use
                            list($component, $controller, $method) = ClassMapper::parse($use, 'Controller');

                            $route = $mRoute->get('route');
                            $methods = $mRoute->get('methods', ['GET', 'POST']);
                            $requirements = $mRoute->get('requirements', []);

                            $this->addRoute($name, $route, $component, $controller, $method, $requirements, $methods);
                        }
                    }
                }
            }
        }

        public function addRouteGroup($groupName, $group) {
            $baseRoute = $group->get('route');

            foreach ($group->sub('routes') as $name => $mRoute) {
                if (($use = $mRoute->get('use')) !== null) { // require use
                    list($component, $controller, $method) = ClassMapper::parse($use, 'Controller');

                    $route = $baseRoute . $mRoute->get('route');
                    $methods = $mRoute->get('methods', ['GET', 'POST']);
                    $requirements = $mRoute->get('requirements', []);

                    $this->addRoute("{$groupName}:{$name}", $route, $component, $controller, $method, $requirements, $methods);
                }
            }
        }
        
        /**
         * add a route to the router
         * @param string $name       the name of the route
         * @param string $route      the route definition
         * @param string $controller the controller defined by the route
         * @param string $method     the method in the controller to be used
         * @param array $methods     [get,post]
         */
        public function addRoute($name, $route, $component, $controller, $method, $requirements, $methods = null) {
            $methods = array_map(function($value) { 
                return strtoupper($value); 
            }, $methods ?? ['GET','POST']);
            
            $this->routes->set($name, [
                'match' => $this->generateRouteRegex($route, $requirements),
                'route' => $route,
                'component' => $component,
                'controller' => $controller,
                'method' => $method,
                'methods' => $methods
            ]);
        }

        /**
         * parse a route from its raw definition into regex
         * @param  string $route the route definition
         * @return string the regex'd route
         *
         * @todo  validate regex?????
         */
        public function generateRouteRegex($route, $requirements) {
            $route = preg_replace('/\//', '\/', $route);

            $parsedRoute = preg_replace_callback('/\{([^:]*?)(?:\:(.*?))?\}/', function($matches) use ($requirements) {
                $param = $matches[1];
                $type = count($matches) >= 3 ? $matches[2] : "string"; 

                if (isset($requirements[$param])) return "(?P<{$matches[1]}>{$requirements[$param]})";

                if (in_array($type, ['text', 'string'])) return "(?P<{$param}>[^\/]+)";
                else if (in_array($type, ['int', 'number', 'integer'])) return "(?P<{$param}>\d+)";
            }, $route, -1, $count);

            return "{$parsedRoute}\/?";
        }

        /**
         * resolve a request, returning the controller, method, and arguments
         * 
         * @param  Request $request the request
         * @return array the controller, method, and arguments
         */
        public function resolve(Request $request) {
            foreach ($this->routes as $index => $route) {
                if (!in_array($request->method(), $route['methods'] ?? ['GET', 'POST'])) continue;

                preg_match("/^{$route->get('match')}$/", $request->route(), $matches);

                if (count($matches) > 0) {
                    $component = $route->get('component');
                    $method = $route->get('method') . 'Action';

                    $controller = Injector::instance($route->get('controller'), [
                        '@component' => $component
                    ]);
                    
                    $reflection = new ReflectionMethod($controller, $method);
                    $params = Injector::resolveMethod($reflection, array_merge(
                        $request->params(), $matches
                    ));

                    return [
                        $controller,
                        $method,
                        $params
                    ];
                }
            }

            throw HttpException::notFoundException();
        }

        public function getRoutes() {
            return $this->routes;
        }

        public function getRoute($name) {
            return $this->routes->get($name, null);
        }

        public function getContext() {
            return $this->context;
        }

        public function generateUrl($name, $args = []) {
            $route = $this->getRoute($name);

            if (!$this->getRoute($name)) {
                throw new SebastianException("Route {$name} does not exist.");
            }
            
            $mRoute = $route['route'];

            foreach ($args as $key => $arg) {
                $match = preg_match("/{($key(?:\:[^\}]*)?)}/", $mRoute);

                if ($match != 0) {
                    $mRoute = preg_replace("/{($key(?:\:[^\}]*)?)}/", $arg, $mRoute);
                    unset($args[$key]);
                }
            }

            if (count($args) > 0) {
                $mRoute .= "?" . http_build_query($args);
            }
            
            return $mRoute;
        }
    }