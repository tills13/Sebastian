<?php
	namespace Sebastian\Core\Service;

	/**
	 * Service
	 *
	 * base service class
	 * 
	 * @author Tyler <tyler@sbstn.ca>
	 * @since Oct. 2015
	 */
	abstract class Service {
		public function __construct() {}
		abstract public function boot();
	}