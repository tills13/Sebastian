<?php 
	namespace Sebastian\Core\Exception;

	/**
	 * EntityNotFoundException
	 * 
	 * @author Tyler <tyler@sbstn.ca>
	 * @since  Oct. 2015
	 */
	class EntityNotFoundException extends SebastianException {
		public function __construct() {
			parent::__construct("Entity not found...");
		}
	}