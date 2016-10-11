<?php
    namespace Sebastian\Core\Database\Query\Part;

    class Join extends AbstractPart {
        const TYPE_INNER = 0;
        const TYPE_OUTER = 1;
        const TYPE_NATURAL = 2;
        const TYPE_FULL = 3;
        const TYPE_DIRECTIONAL = 4;
        const TYPE_LEFT = 5;
        const TYPE_RIGHT = 6;

        protected $type;
        protected $table;
        protected $alias;
        protected $condition;

        public function __construct($table, $condition = null) {
            $this->type = Join::TYPE_NATURAL;

            if (is_array($table)) {
                $key = array_keys($table)[0];
                $table = $table[$key];

                if (is_string($key)) {
                    //print ($key);
                    $this->alias = $key;
                }
            }

            $this->table = $table;
            $this->condition = $condition;
        }

        public function getTable() {
            return $this->table;
        }

        public function hasTableAlias() {
            return !($this->alias == null);
        }

        public function getTableAlias() {
            return $this->alias;
        }

        public function getCondition() {
            return $this->condition;
        }

        public function __toString() {
            return "JOIN {$this->getTable()}" 
                . ($this->hasTableAlias() ? (" AS " . $this->getTableAlias()) : "") . " ON " . $this->getCondition();
        }
    }