<?php 
	namespace Sebastian\Core\Form\Exception;

	use Sebastian\Core\Form\FormPart\FormPart;

	/**
	 * FormException
	 *
	 * @author Tyler <tyler@sbstn.ca>
	 * @since Oct. 2015
	 */
	class FormException extends \Exception {
		private $formPart;

		public function __construct(FormPart $formPart, $message) {
			parent::__construct($message);
			$this->formPart = $formPart;
		}

		public function setFormPart($formPart) {
			$this->formPart = $formPart;
			return $this;
		}

		public function getFormPart() {
			return $this->formPart;
		}
	} 