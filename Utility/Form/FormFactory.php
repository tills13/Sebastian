<?php
	namespace Sebastian\Utility\Form;

	use Sebastian\Application;
	use Sebastian\Core\Entity\Entity;
	use Sebastian\Utility\Configuration\Configuration;
	use Sebastian\Utility\Form\Field;

	class FormFactory {
		protected $context;
		protected $config;
		protected $form;

		public static function getFactory(Application $context, Configuration $config = null) {
			$factory = new FormFactory($context, $config);
			return $factory;
		}

		protected function __construct($context, $config) {
			$this->context = $context;
			$this->config = $config ?: new Configuration();
		}

		public function buildFromConfig(Configuration $config = null) {
			if ($config == null) return null;

			$this->form->setName($config->get('name'));
			$this->form->setMethod($config->get('attributes.method'));
			$this->form->setClass($config->get('attributes.class'));

			// @todo need to support dynamic url generation
			$this->form->setAction($config->get('attributes.action')); 

			foreach ($config->sub('fields') as $name => $field) {
				$type = $field->get('type');
				$this->add($name, $type, []);
			}

			return $this;
		}

		public function createNamedForm($name) {
			$this->form = new Form($name);
			return $this;
		}

		public function createForEntity(Entity $entity) {

		}

		public function add($name, $type, $params = []) {
			if ($this->form->getField($name) != null) throw new \Exception();

			if ($type == "select") $field = new Field\SelectField($this->form, $name, $params);
			else if ($type == "textarea") $field = new Field\TextAreaField($this->form, $name, $params);
			else if (in_array($type, ["input", "text"])) $field = new Field\InputField($this->form, $name, $params);
			else if ($type == "checkbox") $field = new Field\CheckboxField($this->form, $name, $params);
			else throw new \Exception("Cannot find field of type {$type}", 1);
			
			$this->form->addField($field);
			return $this;
		}

		public function method($method) {
			$this->form->setMethod($method);
			return $this;
		}

		public function action($action) {
			$this->form->setAction($action);
			return $this;
		}

		public function attribute($attribute, $value) {
			$this->form->setAttribute($attribute, $value);
			return $this;
		}

		public function addFormConstraint($constraint) {

		}

		public function addFieldConstraint($fieldName, $constraint) {
			$field = $this->form->get($fieldName);
			if (!$field) throw new \Exception("Field {$fieldName} does not exist");

			$constraint = new $constraintName($field);
			$this->form->addConstraint($constraint, $field);
		}

		public function getForm() {
			return $this->form;
		}
	}