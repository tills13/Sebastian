<?php
	namespace Sebastian\Core\Context;

	/**
	 * Context
	 * @author Tyler <tyler@sbstn.ca>
	 * @since  Oct. 2015
	 */
	class Context {
		protected $context;

		public function __construct($context) {
			$this->context = $context;
		}

		public function getContext() {
			return $this->context;
		}

		public function setContext($context) {
			$this->context = $context;
		}
	}