<?php
	namespace Sebastian\Core\Form\Constraint;

	use Sebastian\Core\Form\Exception\EmailException;

	/**
	 * EmailConstraint
	 *
	 * @author Tyler <tyler@sbstn.ca>
	 * @since Oct. 2015
	 */
	class EmailConstraint extends Constraint {
		public function validate() {
			if (0 === preg_match("/([\w\.\+]+?@[\w\.]+\.[\w\.]+)/", $this->getFormPart()->getValue())) {
				throw new EmailException($this->getFormPart());
			}
		}
	}