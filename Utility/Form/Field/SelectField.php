<?php
	namespace Sebastian\Utility\Form\Field; 

	use Sebastian\Utility\Form\Form;
	
	/**
	 * SelectFormPart
	 * 
	 * @author Tyler <tyler@sbstn.ca>
	 * @since  Oct. 2015
	 */
	class SelectField extends Field {
		protected $type = Field::TYPE_SELECT;
		protected $tag = 'select';

		protected $selected;
		protected $options;

		public function __construct(Form $form, $name, $attributes = []) {
			parent::__construct($form, $name, $attributes);

			$this->options = $this->getAttributes()->sub('options', []);
			$this->attributes->remove('options');
			$this->selected = null;
		}

		public function setValue($value) {
			if (!$this->options->has($value)) return;
			$this->selected = $value;

			return $this;
		}

		public function setOptions($options = []) {
			$this->options = $options;

			return $this;
		}

		public function getValue() {
			return $this->selected;
		}

		public function getOptions() {
			return $this->options;
		}

		public function render() {
			$attrs = $this->getAttributesString();

			$selectString  = "<{$this->getTag()} name=\"{$this->getFullName()}\" {$attrs}>";
			$selectString .= "<option>{$this->attributes->get('empty_value', 'select an option')}</option>";
			foreach ($this->getOptions() as $value => $option) {
				$selectString .= "<option value=\"{$value}\"" . (($this->selected == $value) ? "selected" : "") . ">{$option}</option>";
			}

			$selectString .= "</{$this->getTag()}>";
			return $selectString;
		}
	}