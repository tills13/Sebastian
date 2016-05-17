<?php
    namespace Sebastian\Utility\Form;

    use Sebastian\Core\Http\Request;
    use Sebastian\Utility\Configuration\Configuration;
    use Sebastian\Utility\Collection\Collection;
    use Sebastian\Utility\Form\Exception\FormException;
    use Sebastian\Utility\Form\Field\Field;
    use Sebastian\Core\Model\EntityInterface;

    /**
     * Form
     * 
     * @author Tyler <tyler@sbstn.ca>
     * @since  Oct. 2015
     */
    class Form {
        const DEFAULT_ENCODING_ATTR = "application/x-www-form-urlencoded";
        const DEFAULT_CHARSET = "UTF-8";
        const DEFAULT_METHOD = "POST";
        const DEFAULT_ACTION = "";

        protected $name;
        protected $fields;
        protected $constraints;
        protected $attributes;
        protected $errors;

        protected $validated;

        public function __construct($name, Configuration $config = null) {
            $config = $config == null ? new Configuration() : $config;

            $this->name = $name;
            $this->validated = false;
            $this->fields = new Collection();
            $this->constraints = new Collection();
            $this->attributes = $config->extend([
                "id" => null,
                "class" => null,
                "method" => Form::DEFAULT_METHOD,
                "action" => Form::DEFAULT_ACTION,
                "novalidate" => false,
                "autocomplete" => false,
                "accept-charset" => Form::DEFAULT_CHARSET,
                "enctype" => Form::DEFAULT_ENCODING_ATTR,
                "target" => "_blank"
            ]);

            $this->errors = new Collection();
        }

        public function setAction($action) {
            $this->attributes->set('action', $action);
        }

        public function getAction() {
            return $this->attributes->get('action', Form::DEFAULT_ACTION);
        }

        public function setAttribute($attribute, $value) {
            $this->attributes->set($attribute, $value);
        }

        public function getAttribute($attribute) {
            return $this->attributes->get($attribute);
        }

        public function setClass($class) {
            $this->attributes->set('class', $class);
        }

        public function addClass($class) {
            $currentClass = $this->getClass();
            $this->setClass("{$class} {$currentClass}");
        }

        public function getClass() {
            return $this->attributes->get('class', '');
        }

        public function addConstraint(Constraint $constraint, Field $field = null) {
            if ($this->field != null) $field->addConstraint($constraint);
            else $this->constraints->put(null, $constraint);    
        }

        public function getConstraints() {
            return $this->constraints;
        }

        public function getErrors() {
            if (!$this->validated) return false;
            return $this->errors;
        }

        public function setField($fieldName, Field $field) {
            $this->fields->set($fieldName, $field);
        }

        public function addField(Field $field) {
            $this->fields->set($field->getName(), $field);
        }

        public function getField($field) {
            return $this->fields->get($field);
        }

        public function getFields() {
            return $this->fields;
        }

        public function setName($name) {
            if ($name == null || $name == '') throw new \Exception("name cannot be blank", 1);

            $name = str_replace(" ", "_", $name);
            $this->name = $name;
        }

        public function getName() {
            return $this->name;
        }

        public function setMethod($method) {
            if (!in_array(strtoupper($method), ["POST", "GET"])) {
                throw new \Exception("method must be either GET or POST", 1);
            }

            $this->method = $method;
        }

        public function getMethod() {
            return $this->attributes->get('method', Form::DEFAULT_METHOD);
        }

        public function handleRequest(Request $request) {
            foreach ($this->fields as $name => $field) {
                $value = $request->get("{$this->getName()}.{$field->getName()}");
                $field->setValue($value);
            }

            $this->submit();
        }

        public function isValid() {
            $this->submit();
            return count($this->errors) === 0;
        }

        public function start() {
            return "<form method=\"{$this->getMethod()}\" class=\"{$this->getClass()}\" action=\"{$this->getAction()}\">";
        }

        /**
         * @see Form#getField
         * alias to getField
         * @param  string $name field name
         * @return Field field
         */
        public function get($name) {
            return $this->getField($name);
        }

        public function end() {
            return "<form/>";
        }

        public function submit() {
            $this->errors = [];

            foreach ($this->fields as $field) {
                $field->validate();
            }

            $this->validated = true;
        }

        public function addErrorFromException($e) {
            $element = $e->getFormPart();
            $element->addErrorFromException($e);
            $this->errors[$e->getFormPart()->getName()][] = $e;
        }
    }