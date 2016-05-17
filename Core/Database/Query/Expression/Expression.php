<?php
    namespace Sebastian\Core\Database\Query\Expression;

    class Expression {
        const TYPE_NONE = 0; // commas
        const TYPE_AND = 1;
        const TYPE_OR = 2;
        const TYPE_EQUALS = 3;
        const TYPE_IS = 4;
        const TYPE_NOT_EQUALS = 5;
        const TYPE_GT = 6;
        const TYPE_LT = 7;

        //const SEPARATOR_AND = " && ";
        //const SEPARATOR_OR = " || ";
        const SEPARATOR_COMMA = ", ";
        const SQL_SEPARATOR_AND = " AND ";
        const SQL_SEPARATOR_OR = " OR ";
        const SQL_SEPARATOR_EQUALS = " = ";
        const SQL_SEPARATOR_IS = " IS ";
        const SQL_SEPARATOR_NOT_EQUALS = " != ";
        
        const SEPARATOR_AND = Expression::SQL_SEPARATOR_AND;
        const SEPARATOR_OR = Expression::SQL_SEPARATOR_OR;
        const SEPARATOR_EQUALS = Expression::SQL_SEPARATOR_EQUALS;
        const SEPARATOR_IS = Expression::SQL_SEPARATOR_IS;
        const SEPARATOR_NOT_EQUALS = Expression::SQL_SEPARATOR_NOT_EQUALS;
        const SEPARATOR_GT = ">";
        const SEPARATOR_LT = "<";

        protected $prefix = "(";
        protected $postfix = ")";
        protected $separator = Expression::SEPARATOR_COMMA;

        protected $_typeMap = [
            Expression::TYPE_NONE => Expression::SEPARATOR_COMMA,
            Expression::TYPE_AND => Expression::SEPARATOR_AND,
            Expression::TYPE_OR => Expression::SEPARATOR_OR,
            Expression::TYPE_EQUALS => Expression::SEPARATOR_EQUALS,
            Expression::TYPE_IS => Expression::SEPARATOR_IS,
            Expression::TYPE_NOT_EQUALS => Expression::SEPARATOR_NOT_EQUALS
        ];

        protected $elements;

        public function __construct($type = Expression::TYPE_NONE) {
            $this->type = $type;
        }

        public function put($element) {
            $this->elements[] = $element;
            return $this;
        }

        public function putAll(array $elements) {
            $this->elements = $this->elements + $elements;
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

        public function setSeparator($separator) {
            $this->separator = $separator;
            return $this;
        }

        public function getSeparator($override = null) {
            return $override ?: $this->_typeMap[$this->getType()];
        }

        public function setType($type) {
            $this->type = $type;
            return $this;
        }

        public function getType() {
            return $this->type;
        }

        public function is($type) {
            return $this->getType() == $type;
        }

        public function __toString() {
            $expressionString = "";
            if (count($this->elements) > 1) $expressionString .= $this->getPrefix();
            $expressionString .= implode($this->getSeparator(), $this->elements);
            if (count($this->elements) > 1) $expressionString .= $this->getPostfix();
            return $expressionString;
        }
    }