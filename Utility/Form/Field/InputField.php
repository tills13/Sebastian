<?php
	namespace Sebastian\Utility\Form\Field;
	
	/**
	 * InputFormPart
	 * 
	 * @author Tyler <tyler@sbstn.ca>
	 * @since  Oct. 2015
	 */
	class InputField extends Field {
		protected $type = Field::TYPE_INPUT;
		protected $tag = 'input';

		public function render() {
			$attrs = $this->getAttributesString();
			return "<{$this->getTag()} type=\"text\" name=\"{$this->getFullName()}\" {$attrs} value=\"{$this->getValue()}\">";
		}
	}