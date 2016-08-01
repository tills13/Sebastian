<?php
    namespace Sebastian\Core\Database\Query\Part;

    class Join implements Part {
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

        public function __construct($table, $alias = null, $condition = null) {
            $this->type = Join::TYPE_NATURAL;
            $this->table = $table;
            $this->alias = $alias;
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
    }