<?php
	namespace Sebastian\Core\Component;

	use Sebastian\Application;
	use Sebastian\Core\Exception\SebastianException;
	use Sebastian\Utility\Collection\Collection;
	use Sebastian\Utility\Configuration\Configuration;
	use Sebastian\Utility\Utility\Utils;

	abstract class Component {
		protected $application;
		protected $name;
		protected $config;
		protected $requirements;
		protected $weight;
		protected $path;
		protected $enabled;
		protected $routePrefix;

		public function __construct(Application $application, $name, Configuration $config = null) {
			$this->application = $application;
			$this->name = $name;
			$this->config = $config ?: new Configuration();

			$this->requirements = new Collection();
			$this->weight = 0;
			$this->path = "";
			$this->enabled = true;
			$this->routePrefix = null;
		}

		public function setEnabled($enabled) {
			$this->enabled = $enabled;
		}

		public function getEnabled() {
			return $this->enabled;
		}

		public function setName($name) {
			$this->name = $name;
		}

		public function getName() {
			return $this->name;
		}

		public function setPath($path) {
			$this->path = $path;
		}

		public function getPath() {
			return DIRECTORY_SEPARATOR . $this->path;
		}

		public function setRequirements($requirements) {
			if ($requirements instanceof Collection) {
				$this->requirements = $requirements;
			} else if (is_array($requirements)) {
				$this->requirements = new Collection($requirements);
			} else {
				throw new \InvalidArgumentException("setRequirements requires either an array or ? extends Collection");
			}
		}

		public function getRequirements() {
			return $this->requirements;
		}

		public function hasRequirements() {
			return ($this->requirements != null && $this->requirements->count() != 0);
		}

		public function hasController($controller = null) {
			if (!Utils::endsWith($controller, 'Controller')) $controller = $controller . "Controller";
			if (!Utils::endsWith($controller, '.php')) $controller = $controller . ".php";

			$path = implode(DIRECTORY_SEPARATOR, [\APP_ROOT, $this->application->getNamespace(), $this->getNamespacePath(), "Controller", $controller]);
			return file_exists($path);
		}

		public function getController($controller) {
			if (!Utils::endsWith($controller, 'Controller')) $controller = $controller . "Controller";

			return implode('\\', [$this->application->getNamespace(), $this->getNamespacePath(), "Controller", $controller]);
		}

		public function getResourceUri($uri, $absolute = false) {
			if ($absolute) {
				return implode(DIRECTORY_SEPARATOR, [\APP_ROOT, $this->application->getNamespace(), $this->getNamespacePath(), "Resources", $uri]);
			} else {
				return implode(DIRECTORY_SEPARATOR, [$this->getNamespacePath(), "Resources", $uri]);
			}
			
			//return $this->getNamespacePath() . DIRECTORY_SEPARATOR . "Resources" . DIRECTORY_SEPARATOR 
		}

		public function getRoutingConfig() {
			$filename = implode(DIRECTORY_SEPARATOR, [\APP_ROOT, $this->application->getNamespace(), $this->getNamespacePath(), "routing.yaml"]);
			if (file_exists($filename)) return $filename;

			$filename1 = implode(DIRECTORY_SEPARATOR, [\APP_ROOT, $this->application->getNamespace(), $this->getNamespacePath(), "routing.yml"]);
			if (file_exists($filename1)) return $filename1;

			throw new SebastianException("routing file not found: {$filename} or {$filename1}");
		}

		public function setRoutePrefix($routePrefix) {
			$this->routePrefix = $routePrefix;
		}

		public function getRoutePrefix() {
			return $this->routePrefix;
		}

		public function setWeight($weight) {
			$this->weight = $weight;
		}

		public function getWeight() {
			return $this->weight;
		}

		public function getNamespacePath() {
			return str_replace('/', '\\', $this->path);
		}

		public function __toString() {
			return $this->name;
		}

		abstract public function checkRequirements(Application $context);
	}