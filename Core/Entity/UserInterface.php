<?php
	namespace Sebastian\Core\Entity;

	interface UserInterface extends EntityInterface {
		public function setUsername($username);
		public function getUsername();
		public function isAdmin();
	}