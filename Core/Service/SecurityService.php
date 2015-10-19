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
	class SecurityService extends BaseService {
		public function __construct($app) {
			parent::__construct($app);
		}

		public function generateToken() {
			return bin2hex(openssl_random_pseudo_bytes(16));
		}
	}