<?php
	namespace Sebastian\Utility\Form\Exception;

	use Sebastian\Utility\Form\FormPart\FormPart;

	/**
	 * NotBlankException
	 *
	 * @author Tyler <tyler@sbstn.ca>
	 * @since Oct. 2015
	 */
	class NotBlankException extends FormException {
		public function __construct(FormPart $formPart) { 
			parent::__construct($formPart, "<b>{$formPart->getName()}</b> cannot be blank"); 
		}
	}