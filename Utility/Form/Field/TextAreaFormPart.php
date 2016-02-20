<?php 
	namespace Sebastian\Utility\Form\FormPart;

	/**
	 * TextAreaFormPart
	 * 
	 * @author Tyler <tyler@sbstn.ca>
	 * @since  Oct. 2015
	 */
	class TextAreaFormPart extends FormPart {
		protected $type = Field::TYPE_TEXT_AREA;
		protected $tag = 'textarea';

		public function render() {
			parent::render();

			$attrs = $this->getAttributes();

			echo "<{$this->getTag()} $attrs>" . $this->getValue() . "</{$this->getTag()}>";
		}
	}