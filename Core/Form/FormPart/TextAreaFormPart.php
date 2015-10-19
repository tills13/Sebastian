<?php 
	namespace Sebastian\Core\Form\FormPart;

	/**
	 * TextAreaFormPart
	 * 
	 * @author Tyler <tyler@sbstn.ca>
	 * @since  Oct. 2015
	 */
	class TextAreaFormPart extends FormPart {
		public function render() {
			parent::render();

			$attrs = $this->getAttributes();

			echo "<{$this->getTag()} $attrs>" . $this->getValue() . "</{$this->getTag()}>";
		}
	}