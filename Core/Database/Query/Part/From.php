<?php
    namespace Sebastian\Core\Database\Query\Part;

    class From implements Part {
        protected $from;

        public function __construct($from, $alias = null) {
            $this->from = $from;
            $this->alias = $alias;
        }

        public function getFrom() {
            return $this->from;
        }

        public function hasAlias() {
            return !($this->alias == null);
        }

        public function getAlias() {
            return $this->alias;
        }

        public function __toString() {
            $from = $this->getFrom();

            if ($from instanceof Part) $from = "({$from})";

            return $from . ($this->hasAlias() ? " AS " . $this->getAlias() : "");
        }
    }