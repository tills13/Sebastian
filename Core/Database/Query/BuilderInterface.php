<?php
    namespace Sebastian\Core\Database\Query;

    use \PDO;

    interface BuilderInterface {
        public function __construct(PDO $driver);
        public function select(array $columns) : BuilderInterface;
        public function update() : BuilderInterface;
        public function insert() : BuilderInterface;
        public function delete() : BuilderInterface;

        public function from($from, $alias) : BuilderInterface;

        public function join() : BuilderInterface;
        public function innerJoin($table, $condition) : BuilderInterface;
        public function directedJoin($direction, $table, $condition) : BuilderInterface;

        public function set() : BuilderInterface;

        public function where($where) : BuilderInterface;
        public function andWhere() : BuilderInterface;
        public function orWhere() : BuilderInterface;

        public function groupBy() : BuilderInterface;
        public function addGroupBy() : BuilderInterface;

        public function having() : BuilderInterface;
        public function andHaving() : BuilderInterface;
        public function orHaving() : BuilderInterface;

        public function orderBy($column, $direction) : BuilderInterface;
        public function limit($limit) : BuilderInterface;
        public function offset($offset) : BuilderInterface;

        public function getQuery() : QueryInterface;
        
        public function expr();
        public static function sp($name, ... $arguments) : Part\StoredProcedure;
    }