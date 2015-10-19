<?php
	namespace Sebastian\Core\Form\Constraint;

	use Sebastian\Core\Form\Exception\NotBlankException;

	/**
	 * NotBlankConstraint
	 *
	 * @author Tyler <tyler@sbstn.ca>
	 * @since Oct. 2015
	 */
	class NotBlankConstraint extends Constraint {
		public function validate() {
			if ($this->getFormPart()->getValue() == '' || is_null($this->getFormPart()->getValue())) {
				throw new NotBlankException($this->getFormPart());
			}
		}
	}