<?php
	namespace Sebastian\Core\Entity;

	/**
	 * Entity
	 * 
	 * @author Tyler <tyler@sbstn.ca>
	 * @since  Oct. 2015
	 */
	class Entity implements \JsonSerializable {
		protected $touched;
		protected $createdAt;
		protected $modifiedAt;

		public function __construct() {
			$this->touched = [];
		}

		// GETTERS

		public function getCreatedAt() {
			return $this->createdAt;
		}

		public function getModifiedAt() {
			return $this->modifiedAt;
		}

		// SETTERS

		public function setCreatedAt($createdAt) {
			$this->touch();
			$this->createdAt = $createdAt;
			
			return $this;
		}

		public function setModifiedAt($modifiedAt) {
			$this->modifiedAt = $modifiedAt;
			
			return $this;
		}

		public function touch() {
			$backtrace = debug_backtrace();
			$backtrace = array_reverse($backtrace);
			array_pop($backtrace);

			$last = array_pop($backtrace);

			$method = $last['function'];
			$var = substr($method, 3);
			$var[0] = strtolower($var[0]);

			if (!in_array($var, $this->touched)) $this->touched[] = $var;

			$this->setModifiedAt(time());
		}

		public function isTouched($field = null) {
			if (!$field) return !empty($this->touched);
			else return in_array($field, $this->touched);
		}

		public function reset() {
			$this->touched = [];
		}

		public function jsonSerialize() {
	        return [
	            'createdAt' => $this->getCreatedAt(),
	            'modifiedAt' => $this->getModifiedAt(),
	        ];
	    }
	
		public function __toString() {
			return json_encode($this);
		}
	}