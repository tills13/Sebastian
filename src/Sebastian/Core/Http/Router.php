<?php 	
	namespace Sebastian\Core\Http;

	use Sebastian\SEBASTIAN_ROOT;
	use Sebastian\Core\Exception\PageNotFoundException;

	use Sebastian\Core\Session\Session;
	use Sebastian\Core\Entity\Entity;
	use Sebastian\Core\Context\Context;
	use Sebastian\Core\Database\EntityManager;

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
	class Router extends Context {
		public static $tag = "ROUTER";
		public $routes;
		protected static $logger;

		public static function getRouter($context) {
			$router = new Router($context);
			$router->init($context);

			$cm = $context->getCacheManager();

			if ($cm->isCached($router)) {
				$router = $cm->load($router);
				$router->setContext($context);
			} else {
				$router->loadRoutes();
				$cm->cache($router);
			}

			return $router;
		}

		public function __construct($context) {
			parent::__construct($context);
		}

		public function init($app) {
			if (!Router::$logger) {
				Router::$logger = new Logger($app->getLogFolder(), null, ['filename' => 'routing']);
				Router::$logger->setTag(Router::$tag);
			}

			$this->routes = [];
			$this->em = $app->getEntityManager();
			$this->cm = $app->getCacheManager();
		}

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
				$routesFile = yaml_parse_file($path);

				if (!$routesFile) {
					Router::$logger->info("skipping empty file {$path}");
					continue;
				}

				foreach ($routesFile as $name => $route) {
					$this->addRoute($name, 
									$route['route'], 
									$route['controller'],
									$route['method'],
									array_values($route['methods']));
				}

				$count = count($routesFile);
				$time = microtime(true) - $startTime;

				Router::$logger->info("loaded {$count} routes from disk in {$time} µseconds");
			}
		}
		
		public function addRoute($name, $route, $controller, $method, $methods = ['GET','POST']) {
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

		public function resolve($request) {
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
	}