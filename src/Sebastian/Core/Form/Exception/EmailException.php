<?php
	namespace Sebastian\Core\Form\Exception;

	use Sebastian\Core\Form\FormPart\FormPart;

	/**
	 * EmailException
	 *
	 * @author Tyler <tyler@sbstn.ca>
	 * @since Oct. 2015
	 */
	class EmailException extends FormException {
		public function __construct(FormPart $formPart) { 
			parent::__construct($formPart, "<b>{$formPart->getName()}</b> must be a valid email"); 
		}
	}