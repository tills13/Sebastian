<?php
	namespace Sebastian\Core\Form;

	use Sebastian\Core\Http\Request;
	use Sebastian\Core\Form\Exception\FormException;

	use Sebastian\Core\Entity\Entity;

	/**
	 * Form
	 * 
	 * @author Tyler <tyler@sbstn.ca>
	 * @since  Oct. 2015
	 */
	class Form {
		protected $name;
		protected $context;

		protected $method;
		protected $action;

		protected $attrs;
		protected $fields;

		protected $constraints;
		protected $errors;

		public static function fromConfig($config = [], $context) {
			$form = new Form($config['name'], $context);
			$form->setMethod($config['attributes']['method']);
			$form->setClass($config['attributes']['class']);
			//$form->setAction($config['attributes']['action']);//hmmm
			$fields = $config['fields'];

			foreach ($fields as $name => $field) {
				$form->put($name, $field['type'], $field['attributes']);

				if (isset($field['constraints'])) {
					foreach ($field['constraints'] as $constraint) {
						$form->addConstraint($constraint, $name);
					}
				}
			}

			return $form;
		}

		public function __construct($name, $context) {
			if ($name == "") throw new \Exception("a name is required for the form", 1);

			$this->name = $name;
			$this->context = $context;
			
			$this->attrs = [];
			$this->fields = [];
			$this->constraints = [];
			$this->errors = [];
		}

		public function getName() {
			return $this->name;
		}

		public function setName($name = '') {
			if ($name == '') throw new \Exception("name cannot be blank", 1);
			
			$name = str_replace(" ", "_", $name);
			$this->name = $name;

			return $this;
		}

		public function getMethod() {
			return $this->method;
		}

		public function setMethod($method) {
			if (!in_array(strtoupper($method), ["POST", "GET"])) {
				throw new \Exception("method must be either GET or POST", 1);
			}

			$this->method = $method;
			return $this;
		}

		public function getAction() {
			return $this->action;
		}

		public function setAction($action) {
			$this->action = $action;
			return $this;
		}

		public function getClass() {
			return $this->class;
		}

		public function setClass($class) {
			$this->class = $class;
			return $this;
		}

		public function addConstraint($constraint, $formPart) {
			if (in_array($formPart, array_keys($this->fields))) {
				$constraintName = "Sebastian\\Core\\Form\\Constraint\\{$constraint}Constraint";
				$formPart = $this->get($formPart);

				$constraint = new $constraintName($formPart);
				$this->constraints[$formPart->getName()][$constraintName] = $constraint;
			}
		}

		public function getErrors() {
			return $this->errors;
		}

		public function errors() {
			foreach ($this->errors as $formPartErrors) {
				foreach ($formPartErrors as $error) {
					echo "<span>{$error->getMessage()}</span>";
				}
			}
		}

		public function submit() {
			$this->errors = [];

			foreach ($this->constraints as $elementConstraints) {
				foreach ($elementConstraints as $constraint) {
					try {
						$constraint->validate();
					} catch (FormException $e) {
						$this->addErrorFromException($e);
					}
				}
			}
		}

		public function addErrorFromException($e) {
			$element = $e->getFormPart();
			$element->addErrorFromException($e);
			$this->errors[$e->getFormPart()->getName()][] = $e;
		}

		public function isValid() {
			$this->submit();
			return count($this->errors) === 0;
		}

		public function bind(Entity $entity = null) {
			if (!$entity) return;
			
			$em = $this->getContext()->getEntityManager();
			$repo = $em->getRepository($entity); // to use getGetter,getSetter,etc.

			foreach ($this->getFields() as $name => $field) {
				$method = $repo->getGetterMethod($name, false);
				if ($method) $field->setValue($entity->{$method}());
			}
		}

		public function handleRequest(Request $reqest) {
			if (!isset($_POST[$this->getName()])) return; // need to change - GET forms			
			$form = $_POST[$this->getName()];
			if (is_null($form) || $form == '') $form = [];

			foreach ($form as $key => $value) {
				$formPart = $this->get($key);

				if (!is_null($formPart)) {
					$formPart->setValue($value);
				}
			}

			$this->submit();
		}

		public function put($name, $type, $attrs) {
			if (isset($this->fields[$name])) throw new \Exception("a name by that field already exists", 1);

			if ($type == "select") $formPart = new FormPart\SelectFormPart($this, $name, $type, $attrs);
			else if ($type == "textarea") $formPart = new FormPart\TextAreaFormPart($this, $name, $type, $attrs);
			else if ($type == "input") $formPart = new FormPart\InputFormPart($this, $name, $type, $attrs);
			else if ($type == "checkbox") $formPart = new FormPart\CheckboxFormPart($this, $name, $type, $attrs);

			$this->fields[$name] = $formPart;
			return $this;
		}

		public function getContext() {
			return $this->context;
		}

		public function getFields() {
			return $this->fields;
		}

		public function get($name) {
			return $this->fields[$name];
		}

		// todo do me better
		public function start() {
			echo "<form method='{$this->method}' class='$this->class' action='{$this->action}'>";
		}

		public function end() {
			echo "</form>";
		}
	}