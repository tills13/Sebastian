<?php
    namespace Sebastian\Core\Database\Query;

    use Sebastian\Utility\Collection\Collection;
    use Sebastian\Core\Database\Query\Part\Part;

    class UpdateQuery extends Query {
        protected $columns;
        protected $values;

        protected $updates;
        protected $returning;

        public function __construct() {
            parent::__construct();
            $this->updates = [];
            $this->returning = [];
        }

        public function addUpdate($column, $value) {
            $this->updates[$column] = $value;
        }

        public function getUpdates() {
            return $this->updates;
        }

        public function getNumColumns() {
            return $this->columns ? count($this->columns) : 0;
        }

        public function __toString() {
            $query = "UPDATE {$this->getInto()} SET ";

            $index = 0;
            foreach ($this->getUpdates() as $column => $value) {
                $query = $query . "{$column} = {$value}";
                if (++$index < count($this->getUpdates())) {
                    $query = $query . ',';
                }
            }

            $query = $query . " WHERE {$this->getWhere()}";

            return $query;
        }
    }