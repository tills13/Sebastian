<?php
	namespace Sebastian\Core\Database\Query\Expression;

	class Expression {
		//const SEPARATOR_AND = " && ";
		//const SEPARATOR_OR = " || ";
		const SEPARATOR_COMMA = ", ";
		const SQL_SEPARATOR_AND = " AND ";
		const SQL_SEPARATOR_OR = " OR ";
		const SQL_SEPARATOR_EQUALS = " = ";
		const SQL_SEPARATOR_NOT_EQUALS = " != ";
		
		const SEPARATOR_AND = Expression::SQL_SEPARATOR_AND;
		const SEPARATOR_OR = Expression::SQL_SEPARATOR_OR;
		const SEPARATOR_EQUALS = Expression::SQL_SEPARATOR_EQUALS;
		const SEPARATOR_NOT_EQUALS = Expression::SQL_SEPARATOR_NOT_EQUALS;

		protected $prefix = "(";
		protected $postfix = ")";
		protected $separator = Expression::SEPARATOR_COMMA;

		protected $left;
		protected $right;

		public function __construct($separator = null, $left = null, $right = null) {
			$this->separator = $separator ?: Expression::SEPARATOR_COMMA;
			$this->left = $left;
			$this->right = $right;
		}

		public function setLeft($left) {
			$this->left = $left;
		}

		public function getLeft() {
			return $this->left;
		}

		public function setPrefix($prefix) {
			$this->prefix = $prefix;
		}

		public function getPrefix() {
			return $this->prefix;
		}

		public function setPostfix($postfix) {
			$this->postfix = $postfix;
		}

		public function getPostfix() {
			return $this->postfix;
		}

		public function setRight($right) {
			$this->right = $right;
		}

		public function getRight() {
			return $this->right;
		}

		public function setSeparator($separator) {
			$this->separator = $separator;
		}

		public function getSeparator() {
			return $this->separator;
		}

		public function __toString() {
			if ($this->getRight() == null && $this->getLeft() == null) return "";
			if ($this->getRight() == "" && $this->getLeft() == "") return "";

			return $this->getPrefix() . $this->getLeft()
				. (!($this->getLeft() == null || $this->getRight() == null) ? "{$this->getSeparator()}" : "")
				. ($this->getRight() ?: "") . $this->getPostfix();
		}
	}