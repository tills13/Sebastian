<?php 	
	namespace Sebastian\Core\Http;

	use Sebastian\SEBASTIAN_ROOT;

	use Sebastian\Application;
	use Sebastian\Utility\Collection\Collection;
	use Sebastian\Utility\Configuration\Configuration;
	use Sebastian\Core\Database\EntityManager;
	use Sebastian\Core\Entity\EntityInterface;
	use Sebastian\Core\Exception\PageNotFoundException;
	use Sebastian\Core\Http\Request;
	use Sebastian\Core\Session\Session;
	
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
		public static function getRouter(Application $context) {
			if (self::$router == null) {
				self::$router = new Router($context);
				self::$router->loadRoutes();
			}				

			return self::$router;
		}

		protected function __construct($context) {
			$this->context = $context;
			$this->routes = new Collection();

			$this->em = $context->getEntityManager();
			$this->cm = $context->getCacheManager();
			$this->logger = $context->getLogger(self::$tag);
		}

		/**
		 * load routes from the various routing files in component roots.
		 * @return void
		 */
		public function loadRoutes() {
			$components = $this->getContext()->getComponents();
			$namespace = $this->getContext()->getNamespace();

			$paths = [
				\APP_ROOT . "/../config/routing.yaml", // master routing file, if required
				SEBASTIAN_ROOT . "/Core/Resources/config/routing.yaml" // internal for css/js/font/assets
			]; // add default routes

			$paths = $paths + array_map(function($component) use ($namespace) {
				return \APP_ROOT."/{$namespace}{$component->getPath()}/routing.yaml";
			}, $components);

			foreach ($paths as $index => $path) {
				if (!file_exists($path)) continue;

				$startTime = microtime(true);
				$routes = Configuration::fromPath($path);

				if (!$routes || $routes->count() == 0) continue;

				foreach ($routes as $name => $mRoute) {
					if ($mRoute->has('type') || $mRoute->get('type') == 'group') {
						$this->addRouteGroup($name, $mRoute);
					} else {
						// required fields
						$route = $mRoute->get('route');
						$controller = $mRoute->get('controller');
						$method = $mRoute->get('method');

						// optional
						$methods = $mRoute->get('methods', ['GET', 'POST']);
						$requirements = $mRoute->get('requirements', []);

						$this->addRoute($name, $route, $controller, $method, $requirements, $methods);
					}
				}

				$count = count($routes);
				$time = microtime(true) - $startTime;
			}
		}

		public function addRouteGroup($groupName, $group) {
			$baseRoute = $group->get('route');

			foreach ($group->sub('routes') as $name => $mRoute) {
				$mName = "{$groupName}:{$name}";
				
				$route = $baseRoute . $mRoute->get('route');
				$controller = $mRoute->get('controller');
				$method = $mRoute->get('method');

				// optional
				$methods = $mRoute->get('methods', ['GET', 'POST']);
				$requirements = $mRoute->get('requirements', []);

				$this->addRoute($mName, $route, $controller, $method, $requirements, $methods);
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
		public function addRoute($name, $route, $controller, $method, $requirements, $methods = null) {
			$methods = ($methods !== null) ? array_map(function($value) { 
				return strtoupper($value); 
			}, $methods) : ['GET','POST'];

			if (strstr($controller, ':')) {
				$components = explode(':', $controller);	 
				$component = $components[0];
				$controller = $components[1];
			} else $component = null;
			
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

			return "$parsedRoute\/?";
		}

		/**
		 * resolve a request, returning the controller, method, and arguments
		 * 
		 * @param  Request $request the request
		 * @return array the controller, method, and arguments
		 */
		public function resolve(Request $request) {
			$components = $this->getContext()->getComponents(true);

			foreach ($this->routes as $index => $route) {
				if (!in_array($request->method(), $route['methods'])) continue;

				preg_match("/^{$route->get('match')}$/", $request->route(), $matches);

				if (count($matches) > 0) {
					$namespace = $this->getContext()->getNamespace();

					if ($route->has('component')) {
						$component = $this->getContext()->getComponent($route->get('component'));
						if (!$component) continue;

						$path = str_replace('/', '\\', $component->getPath());
						$controller = "\\{$namespace}{$path}\\Controller\\".$route['controller'];	
						$path = \APP_ROOT . '/'.str_replace('\\', '/', $controller) . '.php';

						if (!file_exists($path)) continue;
					} else {
						foreach ($components as $component) {
							$path = str_replace('/', '\\', $component->getPath());
							$controller = "\\{$namespace}{$path}\\Controller\\".$route['controller'];	
							$path = \APP_ROOT . '/'.str_replace('\\', '/', $controller) . '.php';

							if (file_exists($path)) break;
						}
					}

					/*try {

					} catch (ReflectionException $e) {

					}*/
					$method = $route->get('method') . 'Action';
					$reflection = new \ReflectionMethod($controller, $method);

					$parameters = $reflection->getParameters();
					$args = $this->parseArgs($matches, $parameters, $request);

					return new Collection([
						'controller' => $controller, 
						'method' => $method, 
						'arguments' => $args
					]);
				}
			}

			throw new PageNotFoundException("That page doesn't exist...", 404);
		}

		/**
		 * [parseArgs description]
		 * @param  [type] $attributes [description]
		 * @param  [type] $parameters [description]
		 * @param  [type] $request    [description]
		 * @return [type]             [description]
		 *
		 * @todo  when fetching an entity, don't just assume it's 'id'
		 */
		protected function parseArgs($attributes, $parameters, $request) {
			$arguments = new Collection();

			foreach ($parameters as $param) {
				$class = $param->getClass() ? $param->getClass()->getName() : null;

				if (array_key_exists($param->name, $attributes)) {
					if ($class) {
						$repo = $this->em->getRepository($this->em->getBestGuessClass($class));
						$value = $repo->get(['id' => $attributes[$param->name]]);
					} else {
						$value = $attributes[$param->name];	
					}
				} elseif ($class == Request::class) {
					$value = $request;
				} elseif ($class == Session::class) {
					$value = $this->getContext()->getSession();
				} elseif ($param->isDefaultValueAvailable()) {
					$value = $param->getDefaultValue();
				} else {
					throw new \Exception("No param set for {$param->name} {$request->route()}");
				}

				$arguments->set($param->name, $value);
				//$request->set($param->name, $value);
			}
    
    		return $arguments;
		}

		public function getRoutes() {
			return $this->routes;
		}

		public function getContext() {
			return $this->context;
		}
	}