<?php 	
	namespace Sebastian\Core\Http;

	use Sebastian\SEBASTIAN_ROOT;
	use Sebastian\Core\Exception\PageNotFoundException;

	use Sebastian\Core\Entity\Entity;
	use Sebastian\Core\Context\Context;
	use Sebastian\Core\Database\EntityManager;
	use Sebastian\Core\Http\Request;
	use Sebastian\Core\Session\Session;

	use Sebastian\Core\Utility\Logger;
	use Sebastian\Core\Utility\Utils;
	
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
		public $routes;

		protected $context;
		protected static $logger;

		/**
		 * attempts to load the router (plus routes) from cache, if it's not
		 * cached, load it normally
		 * 
		 * @param  Kernel $context application context
		 * @return Router the router
		 */
		public static function getRouter($context) {
			$router = new Router($context);
			$router->init($context);

			$cm = $context->getCacheManager();

			if ($cm->isCached($router)) {
				$router = $cm->load($router);
				// the context will be incorrect
				$router->setContext($context); 
			} else {
				$router->loadRoutes();
				$cm->cache($router);
			}

			return $router;
		}

		protected function __construct($context) {}

		/**
		 * initialize the router object
		 * @param  Kernel $app the application context, this should eventually be Application
		 * @return void
		 */
		public function init($app) {
			if (!Router::$logger) {
				Router::$logger = new Logger($app->getLogFolder(), null, ['filename' => 'routing']);
				Router::$logger->setTag(Router::$tag);
			}

			$this->routes = [];
			$this->em = $app->getEntityManager();
			$this->cm = $app->getCacheManager();
		}

		/**
		 * load routes from the various routing files in component roots.
		 * @return void
		 */
		public function loadRoutes() {
			$components = $this->getContext()->getComponents();
			$namespace = $this->getContext()->getAppNamespace();

			$paths = [
				\APP_ROOT . "/config/routing.yaml", // master routing file, if required
				SEBASTIAN_ROOT . "/Core/Resources/config/routing.yaml" // internal for css/js/font/assets
			]; // add default routes

			$paths = $paths + array_map(function($component) use ($namespace) {
				return \APP_ROOT."/{$namespace}{$component['path']}/routing.yaml";
			}, $components);

			foreach ($paths as $index => $path) {
				if (!file_exists($path)) {
					Router::$logger->info("skipping non-existent file {$path}");
					continue;
				}

				$startTime = microtime(true);
				$routes = yaml_parse_file($path);

				if (!$routes) {
					Router::$logger->info("skipping empty file {$path}");
					continue;
				}

				foreach ($routes as $name => $route) {
					$this->addRoute(
						$name,
						$route['route'], 
						$route['controller'],
						$route['method'],
						array_values($route['methods'])
					);
				}

				$count = count($routes);
				$time = microtime(true) - $startTime;

				Router::$logger->info("loaded {$count} routes from disk in {$time} µseconds");
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
		public function addRoute($name, $route, $controller, $method, $methods) {
			$methods = $methods ?: ['get','post'];

			preg_match_all('/\{([^:\/]*?)(?:\:[^\/]*?)?\}/', $route, $args);
			array_shift($args); // get rid of the full string
			$args = $args[0]; // weird embedded array shit

			if (strstr($controller, ':')) {
				$components = explode(':', $controller);	 
				$component = $components[0];
				$controller = $components[1];
			} else $component = null;
			
			$this->routes[Utils::sanitize($name)] = [
				'match' => $route,
				'component' => $component,
				'controller' => $controller,
				'method' => $method,
				'args' => $args,
				'methods' => $methods
			];
		}

		/**
		 * parse a route from its raw definition into regex
		 * @param  string $route the route definition
		 * @return string the regex'd route
		 */
		public function parseRoute($route) {
			$route = preg_replace('/\//', '\/', $route);
			$parsedRoute = preg_replace_callback('/\{([^:]*?)(?:\:(.*?))?\}/', function($matches) {
				$type = $matches[2];
				if (!$type) $type = 'string';

				if ($type === 'string') {
					return "(?P<{$matches[1]}>[^\/]*)";
				} else if ($type === 'int') {
					return "(?P<{$matches[1]}>\d*)";
				}
			}, $route, -1, $count);

			if ($count == 0) $parsedRoute = $route;

			$parsedRoute = $parsedRoute . "(?:\/p(?P<page>\d+))?"; //. "(?:\?.*)?";

			return $parsedRoute;
		}

		/**
		 * resolve a request, returning the controller, method, and arguments
		 * 
		 * @param  Request $request the request
		 * @return array the controller, method, and arguments
		 */
		public function resolve(Request $request) {
			$mRoute = $request->route();
			$components = $this->getContext()->getComponents(true);
			
			Router::$logger->info("resolving: {$mRoute}");

			foreach ($this->routes as $index => $route) {
				$route['methods'] = array_map(function($value) { 
					return strtoupper($value); 
				}, $route['methods']);

				if (!in_array($request->method(), $route['methods'])) continue;

				$parsedRoute = $this->parseRoute($route['match']);
				$numMatches = preg_match("/^{$parsedRoute}$/", $mRoute, $matches);

				if ($numMatches > 0) {
					$namespace = $this->getContext()->getAppNamespace();
					if (isset($route['component'])) {
						$controller = "\\{$namespace}\\{$route['component']}\\Controller\\".$route['controller'];
					} else {
						foreach ($components as $component) {
							$path = str_replace('/', '\\', $component['path']);
							$controller = "\\{$namespace}{$path}\\Controller\\".$route['controller'];	
							$path = \APP_ROOT.'/'.str_replace('\\', '/', $controller).'.php';

							if (file_exists($path)) break;
						}
					}

					$method = $route['method'].'Action';
					$mMethod = new \ReflectionMethod($controller, $method);

					$parameters = $mMethod->getParameters();
					$args = $this->parseArgs($matches, $parameters, $request);

					Router::$logger->info("matched: {$route['match']} -> {$parsedRoute}");
					Router::$logger->info("\t\t_c: {$controller}");
					Router::$logger->info("\t\t_m: {$method}");

					return [$controller, $method, $args];
				}
			}

			Router::$logger->error("could not find route for: {$mRoute}");
			throw new PageNotFoundException("That page doesn't exist...", 404);
		}

		protected function parseArgs($attributes, $parameters, $request) {
			$arguments = [];
			foreach ($parameters as $param) {
				$class = $param->getClass() ? $param->getClass()->getName() : null;

				if (array_key_exists($param->name, $attributes)) {
					if ($class) {
						$repo = $this->em->getRepository($this->em->getBestGuessClass($class));
						// todo fix me to not just use id
						// something like $repo->getAvailabled keys ??????
						$attributes[$param->name] = $repo->get(['id' => $attributes[$param->name]]);
					}

					$arguments[$param->name] = $attributes[$param->name];
				} elseif ($class == Request::class) {
					$arguments['request'] = $request;
				} elseif ($class == Session::class) {
					$arguments['session'] = $this->getContext()->getSession();
				} elseif ($param->name === 'page') {
					$arguments['page'] = $attributes['page'] ?: 1;
				} elseif ($param->isDefaultValueAvailable()) {
					$arguments[$param->name] = $param->getDefaultValue();
				} else {
					throw new \Exception("No param set for {$param->name} {$request->route()}");
				}
			}

			$arguments['page'] = @$arguments['page'] ?: @$attributes['page'] ?: 1;
    
    		return $arguments;
		}

		public function getRoutes() {
			return $this->routes;
		}

		public function getContext() {
			return $this->context;
		}

		public function setContext($context) {
			$this->context = $context;
		}
	}