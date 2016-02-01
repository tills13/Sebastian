<?php
	namespace Sebastian\Core\Component;

	use Sebastian\Component\Collection\Collection;
	use Sebastian\Core\Configuration\Configuration;

	class Component {
		protected $name;
		protected $config;
		protected $requirements;
		protected $weight;
		protected $path;

		public function __construct($name, Configuration $config = null) {
			$this->name = $name;
			$this->config = $config ?: new Configuration();

			$this->requirements = new Collection();
			$this->weight = 0;
			$this->path = "";
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

		public function setWeight($weight) {
			$this->weight = $weight;
		}

		public function getWeight() {
			return $this->weight;
		}

		public function getNamespacePath() {
			return str_replace('/', '\\', $this->path);
		}
	}