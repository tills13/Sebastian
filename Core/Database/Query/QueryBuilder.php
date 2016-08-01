<?php
    namespace Sebastian\Core\Database\Query;

    class QueryBuilder {
        protected $em;

        protected $parts = [
            'distinct' => false,
            'select' => [],
            'from' => [],
            'join' => [],
            'set' => [],
            'where' => [],
            'groupBy' => [],
            'having' => [],
            'orderBy' => []
        ];

        protected $params;
        protected $paramTypes;

        public function __construct(EntityManager $em) {
            $this->em = $em;
        }

        public function expr() {
            return new ExpressionBuilder();
        }

        public function select() {

        }

        public function update() {

        }

        public function insert() {

        }

        public function delete() {

        }

        public function join() {
            
        }

        public function innerJoin() {

        }

        /**
         * left/right
         */
        public function directedJoin() {

        }

        public function set() {

        }

        public function where() {

        }

        public function andWhere() {

        }

        public function orWhere() {

        }

        public function groupBy() {

        }

        public function addGroupBy() {

        }

        public function having() {

        }

        public function andHaving() {

        }

        public function orHaving() {

        }

        public function orderBy() {

        }
    }