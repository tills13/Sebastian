<?php 
	namespace Sebastian\Core\Form\FormPart;

	use Sebastian\Core\Http\Request;

	/**
	 * FormPart
	 * 
	 * @author Tyler <tyler@sbstn.ca>
	 * @since  Oct. 2015
	 */
	class FormPart {
		protected $parent;
		protected $name;
		protected $type;
		protected $value;

		protected $errors;

		public function __construct(Form $parent, $name, $type, $attributes = []) {
			$this->parent = $parent;
			$this->name = $name;
			$this->type = $type;

			$this->attributes = $attributes;

			$this->attributes['type'] = isset($this->attributes['type']) ? $this->attributes['type'] : $type;
			$this->attributes['name'] = isset($this->attributes['name']) ? $this->attributes['name'] : "{$parent->getName()}[{$name}]";

			$this->errors = [];
		}

		public function setName($name = null) {
			if (!$name || $name == '') throw new Exception("name cannot be blank", 1);
			
			$name = str_replace(" ", "_", $name);
			$this->name = $name;
			
			return $this;
		}

		public function setType() {}

		public function setValue($value) {
			$this->value = $value;
			return $this;
		}
		
		public function getName() {
			return $this->name;
		}

		public function getType() {
			return $this->type;
		}

		public function getValue() {
			return $this->value;
		}

		public function getTag() {
			if ($this->getType() == 'select') return 'select';
			elseif ($this->getType() == 'number') return 'input';
			elseif ($this->getType() == 'textarea') return 'textarea';
			elseif ($this->getType() == 'checkbox') return 'input';
			else return 'input';
		}

		public function getAttributes() {
			$attributes = [];

			foreach ($this->attributes as $key => $value) {
				$attributes[] = "{$key}=\"{$value}\"";
			}

			$attributes = implode(" ", $attributes);

			return $attributes;
		}

		public function addErrorFromException($e) {
			$this->errors[] = $e;
		}

		public function render() {}
	}