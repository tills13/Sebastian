<?php 
	namespace Sebastian\Utility\Form\Field;

	
	use Sebastian\Core\Http\Request;
	use Sebastian\Utility\Form\Form;
	use Sebastian\Utility\Collection\Collection;
	use Sebastian\Utility\Configuration\Configuration;

	/**
	 * FormPart
	 * 
	 * @author Tyler <tyler@sbstn.ca>
	 * @since  Oct. 2015
	 */
	abstract class Field {
		const TYPE_INPUT = 'input';
		const TYPE_PASSWORD = 'input';
		const TYPE_DEFAULT = Field::TYPE_INPUT;
		const TYPE_SELECT = 'select';
		const TYPE_CHECKBOX = 'checkbox';
		const TYPE_TEXT_AREA = 'textarea';

		protected $form;
		protected $name;
		protected $value;
		protected $validated;

		protected $type = Field::TYPE_DEFAULT;
		protected $tag = 'input';

		protected $errors;

		public function __construct(Form $form, $name, $attributes = []) {
			$this->form = $form;
			$this->name = $name;
			$this->validated = false;

			$this->attributes = new Configuration($attributes);

			//$this->attributes['type'] = isset($this->attributes['type']) ? $this->attributes['type'] : $type;
			//$this->attributes['name'] = isset($this->attributes['name']) ? $this->attributes['name'] : "{$parent->getName()}[{$name}]";

			$this->errors = new Collection();
			$this->constraints = new Collection();
		}

		public function getAttributes() {
			return $this->attributes;
		}

		public function getAttributesString() {
			$attributes = [];

			foreach ($this->getAttributes() as $key => $value) {
				$attributes[] = "{$key}=\"{$value}\"";
			}

			return implode(" ", $attributes);
		}

		public function setName($name = null) {
			if (!$name || $name == '') throw new \Exception("name cannot be blank", 1);
			
			$name = str_replace(" ", "_", $name);
			$this->name = $name;
		}

		public function getName() {
			return $this->name;
		}

		public function getForm() {
			return $this->form;
		}

		public function getFullName() {
			return "{$this->getForm()->getName()}[{$this->getName()}]";
		}

		public function getType() {
			return $this->type;
		}

		public function setValue($value) {
			$this->value = $value;
		}

		public function getValue() {
			return $this->value;
		}

		public function getTag() {
			return $this->tag;
		}

		public function validate() {
			$this->validated = true;
		}

		abstract public function render();
	}