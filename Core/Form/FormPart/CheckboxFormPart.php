<?php
	namespace Sebastian\Core\Form\FormPart;
	
	/**
	 * CheckboxFormPart
	 * 
	 * @author Tyler <tyler@sbstn.ca>
	 * @since  Oct. 2015
	 */
	class CheckboxFormPart extends FormPart {
		public function render() {
			$attrs = $this->getAttributes();
			echo "<{$this->getTag()} $attrs value='{$this->getValue()}'>";
		}
	}