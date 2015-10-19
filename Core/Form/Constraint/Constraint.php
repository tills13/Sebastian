<?php
	namespace Sebastian\Core\Form\Constraint;

	use Sebastian\Core\Form\FormPart\FormPart;

	/**
	 * Constraint
	 *
	 * @author Tyler <tyler@sbstn.ca>
	 * @since Oct. 2015
	 */
	class Constraint {
		private $formPart;

		public function __construct(FormPart $formPart) {
			$this->formPart = $formPart;
		}

		public function getFormPart() {
			return $this->formPart;
		}

		public function setFormPart($formPart) {
			$this->formPart = $formPart;
			return $this;
		}
	}