<?php
	namespace Sebastian\Core\Entity;

	/**
	 * User
	 * 
	 * @author Tyler <tyler@sbstn.ca>
	 * @since  Oct. 2015
	 */
	class User extends Entity {
		protected $id;
		protected $admin;
		protected $roles;
		protected $permissions;
		protected $token;

		public function __construct() {
			parent::__construct();
		}

		public function getRoles() {
			return $this->roles;
		}

		public function getPermissions() {
			return $this->permissions;
		}

		public function getAdmin() {
			return $this->admin;
		}

		// convenience function
		public function isAdmin() {
			return $this->admin;
		}

		public function getToken() {
			return $this->token;
		}

		// setters

		public function setRoles($roles) {
			$this->roles = $roles;
		}

		public function addRole($role) {
			$this->roles[] = $role;
			return $this;
		}

		public function setPermissions($permissions) {
			$this->permissions = $permissions;

			return $this;
		}

		// todo use Permission object
		public function addPermission($permission) {
			$this->permissions[] = $permission;
			return $this;
		}

		public function setAdmin($admin) {
			$this->admin = $admin;

			return $this;
		}

		public function setToken($token) {
			$this->token = $token;
			return $this;
		}

		// === 
		
		public function hasRole($role) {
			return in_array($role, $this->roles);
		}

		public function hasPermission($permission) {
			if (!$this->permissions) return false;
			return in_array($permission, $this->permissions);
		}

		public function jsonSerialize() {
	        return [
	            'id' => $this->getId(),
	            'admin' => $this->getAdmin(),
	            'createdAt' => $this->getCreatedAt(),
	            'modifiedAt' => $this->getModifiedAt(),
	            'lastSeen' => $this->getLastSeen(),
	            'roles' => $this->getRoles(),
	            'permissions' => $this->getPermissions(),
	            'token' => $this->getToken()
	        ];
	    }
	}