<?php
	namespace Sebastian\Core\Service;

	/**
	 * SecurityService
	 *
	 * service methods relating to security
	 *  
	 * @author Tyler <tyler@sbstn.ca>
	 * @since Oct. 2015
	 */
	class SecurityService extends Service {
		public function __construct() {
			parent::__construct();
		}

		public function generateToken() {
			return bin2hex(openssl_random_pseudo_bytes(16));
		}
	}