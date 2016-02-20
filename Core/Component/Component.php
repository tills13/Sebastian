<?php
	namespace Sebastian\Core\Component;

	use Sebastian\Application;
	use Sebastian\Utility\Collection\Collection;
	use Sebastian\Utility\Configuration\Configuration;

	abstract class Component {
		protected $name;
		protected $config;
		protected $requirements;
		protected $weight;
		protected $path;
		protected $enabled;

		public function __construct($name, Configuration $config = null) {
			$this->name = $name;
			$this->config = $config ?: new Configuration();

			$this->requirements = new Collection();
			$this->weight = 0;
			$this->path = "";
			$this->enabled = true;
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

		public function setWeight($weight) {
			$this->weight = $weight;
		}

		public function getWeight() {
			return $this->weight;
		}

		public function getNamespacePath() {
			return str_replace('/', '\\', $this->path);
		}

		abstract public function checkRequirements(Application $context);
	}