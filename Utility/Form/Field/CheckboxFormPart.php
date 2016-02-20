<?php
	namespace Sebastian\Utility\Form\Field;
	
	/**
	 * CheckboxFormPart
	 * 
	 * @author Tyler <tyler@sbstn.ca>
	 * @since  Oct. 2015
	 */
	class CheckboxFormPart extends Field {
		public function render() {
			$attrs = $this->getAttributes();
			echo "<{$this->getTag()} $attrs value='{$this->getValue()}'>";
		}
	}