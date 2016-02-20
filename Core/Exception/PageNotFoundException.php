<?php 
	namespace Sebastian\Core\Exception;

	/**
	 * PageNotFoundException
	 * 
	 * @author Tyler <tyler@sbstn.ca>
	 * @since  Oct. 2015
	 */
	class PageNotFoundException extends SebastianException {
		public function __construct() {
			parent::__construct("That page does not exist...);
		}
	} 