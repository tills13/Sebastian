<?php
    namespace Sebastian\Core\Database\Query\Expression;

    class Expression {
        //const AND = " && ";
        //const OR = " || ";
        const COMMA = ",";
        const SQL_AND = "AND";
        const SQL_OR = "OR";
        const SQL_EQUALS = "=";
        const SQL_IS = "IS";
        const SQL_NOT_EQUALS = "!=";
        
        const AND = Expression::SQL_AND;
        const OR = Expression::SQL_OR;
        const EQUALS = Expression::SQL_EQUALS;
        const IS = Expression::SQL_IS;
        const NOT_EQUALS = Expression::SQL_NOT_EQUALS;
        
        const GT = ">";
        const GTE = ">=";
        const LT = "<";
        const LTE = "<=";

        const TYPE_NONE = Expression::COMMA; // commas
        const TYPE_AND = Expression::AND;
        const TYPE_OR = Expression::OR;
        const TYPE_EQUALS = Expression::EQUALS;
        const TYPE_IS = Expression::IS;
        const TYPE_NOT_EQUALS = Expression::NOT_EQUALS;
        const TYPE_GT = Expression::GT;
        const TYPE_GTE = Expression::GTE;        
        const TYPE_LT = Expression::LT;
        const TYPE_LTE = Expression::LTE;

        protected $prefix = "(";
        protected $postfix = ")";
        protected $separator = Expression::COMMA;

        protected $_typeMap = [
            Expression::TYPE_NONE => Expression::COMMA,
            Expression::TYPE_AND => Expression::AND,
            Expression::TYPE_OR => Expression::OR,
            Expression::TYPE_EQUALS => Expression::EQUALS,
            Expression::TYPE_IS => Expression::IS,
            Expression::TYPE_NOT_EQUALS => Expression::NOT_EQUALS
        ];

        protected $elements;

        public function __construct($type = Expression::TYPE_NONE, ... $elements) {
            $this->type = $type;
            $this->addElements($elements);
        }

        public function addElements(array $elements) {
            foreach ($elements as $element) {
                if ($element instanceof Expression && $element->is($this->getType())) {
                    $this->addElements($element->getElements());
                } else {
                    $this->addElement($element);
                }
            }
        }

        public function addElement($element) {
            $this->elements[] = $element;
        }

        public function getElements() {
            return $this->elements;
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
            return $this->getType() === $type;
        }

        public function __toString() {
            if (count($this->elements) === 1) {
                return (string) $this->elements[0];
            }

            return '(' . implode(" {$this->type} ", $this->elements) . ')';
        }
    }