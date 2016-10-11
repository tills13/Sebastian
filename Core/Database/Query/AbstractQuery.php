<?php
    namespace Sebastian\Core\Database\Query;

    use Sebastian\Utility\Collection\Collection;
    use Sebastian\Core\Database\Query\Part\AbstractPart;

    abstract class AbstractQuery extends AbstractPart implements QueryInterface {
        const TYPE_SELECT = 0;
        const TYPE_DELETE = 1;
        const TYPE_UPDATE = 2;
        const TYPE_INSERT = 3;

        protected $columns;
        protected $columnAliases;

        protected $froms;
        protected $joins;
        protected $where;
        protected $limit;
        protected $offset;
        protected $orderBy;
        protected $into;

        public function __construct() {
            $this->columns = new Collection();
            $this->columnAliases = new Collection();
            $this->froms = new Collection();

            $this->joins = [];
            $this->where = null;
            $this->limit = null;
            $this->offset = 0; 
            $this->orderBy = [];
        }

        public function getBinds() {
            $joinBinds = [];

            foreach($this->joins as $join) {
                $joinBinds = array_merge($joinBinds, $join->getBinds());
            }

            return array_merge($this->binds, $joinBinds, $this->where->getBinds());
        }

        public function getBindTypes() {
            $joinBindTypes = [];

            foreach($this->joins as $join) {
                $joinBindTypes = array_merge($joinBindTypes, $join->getBindTypes());
            }

            return array_merge($this->bindTypes, $joinBindTypes, $this->where->getBindTypes());
        }

        public function select(array $columns) {
            foreach ($columns as $alias => $column) {
                $this->selectColumn($column, is_string($alias) ? $alias : null);
            }
        }

        public function selectColumn($column, $alias = null) {
            $this->columns->set(null, $column);
            $this->columnAliases->set($column, $alias);
        }

        public function addFrom($from) {
            $this->setFrom($from);
        }

        public function setFrom($from) {
            $this->froms->set(null, $from);
        }

        public function getFroms() {
            return $this->forms;
        }

        public function setInto($identifier) {
            $this->into = $identifier;
        }

        public function getInto() {
            return $this->into;
        }

        public function join($join) {
            $this->joins[] = $join;
        }

        public function setLimit($limit) {
            $this->limit = $limit;
            return $this;
        }

        public function getLimit() {
            return $this->limit;
        }

        public function setOffset($offset) {
            $this->offset = $offset;
            return $this;
        }

        public function getOffset() {
            return $this->offset;
        }

        public function addOrderBy($column, $direction) {
            $this->orderBy[$column] = $direction;
        }

        public function setOrderBy($column, $direction) {
            $this->orderBy = [$column => $direction];
        }

        public function getOrderBy() {
            return $this->orderBy;
        }

        public function setWhere($expression) {
            $this->where = $expression;
            return $this;
        }

        public function getWhere() {
            return $this->where;
        }

        public function getColumns() {
            return $this->columns;
        }

        public function getColumnAliases() {
            return $this->columnAliases;
        }

        abstract public function __toString();

        protected function columnsToString() {
            $aliases = $this->columnAliases;

            $cols = array_map(function($column) use ($aliases) {
                if ($aliases->has($column)) {
                    return "{$column} AS {$aliases->get($column)}";
                } else return $column;
            }, $this->columns->toArray());

            return implode(',', $cols);
        }

        protected function fromsToString() {
            /*$aliases = $this->tableAliases;
            $tables = array_map(function($table) use ($aliases) {
                if ($aliases->has($table)) {
                    return "{$table} AS {$aliases->get($table)}";
                } else return $table;
            }, $this->tables->toArray());*/

            return implode(',', $this->froms->toArray());
        }
    }