<?php
    namespace Sebastian\Core\Database\PDO\Postgres\Query;

    use \PDO;

    use Sebastian\Core\Database\Query\Expression\ExpressionBuilder;
    use Sebastian\Core\Database\Query\Part\StoredProcedure;
    use Sebastian\Core\Database\Query\BuilderInterface;
    use Sebastian\Core\Database\Query\QueryInterface;

    use Sebastian\Core\Database\Query\Part\From;
    use Sebastian\Core\Database\Query\Part\DirectionalJoin;
    use Sebastian\Core\Database\Query\Part\Join;

    class Builder implements BuilderInterface {
        protected $driver;
        protected $query;

        public function __construct(PDO $driver) {
            $this->driver = $driver;
            $this->query = new Query();
        }

        /**
         *  select(string ...)
         *  select([alias -> col])
         *  select([ [alias -> col] ... ])
         */
        public function select(array $columns) : BuilderInterface {
            if (count($columns) != 0 && is_array($columns[0])) {
                foreach ($columns as $column) {
                    $this->query->select($column);
                }
            } else {
                $this->query->select($columns);
            }

            return $this;
        }

        public function update() : BuilderInterface {
            return $this;
        }

        public function insert() : BuilderInterface {
            return $this;
        }

        public function delete() : BuilderInterface {
            return $this;
        }

        public function from($from, $alias = null) : BuilderInterface {
            $this->query->addFrom(new From($from, $alias));
            return $this;
        }

        public function join() : BuilderInterface {
            $args = func_get_args();

            if (count($args) == 1 && $args[0] instanceof Join) {
                $join = $args[0];
            } else {
                $type = $args[0];

                //if ($type == )

                $table = $args[1];
                $condition = $args[2]; 
            }

            $this->query->join($join);

            return $this;
        }

        public function innerJoin($table, $condition) : BuilderInterface {
            $join = new InnerJoin($table, $condition);
            $this->query->join($join);

            return $this;
        }

        public function directedJoin($direction, $table, $condition) : BuilderInterface {
            $join = new DirectionalJoin($direction, $table, $condition);
            $this->query->join($join);

            return $this;
        }


        public function set() : BuilderInterface {
            return $this;
        }


        public function where($where) : BuilderInterface {
            $this->query->setWhere($where);
            return $this;
        }

        public function andWhere() : BuilderInterface {
            return $this;
        }

        public function orWhere() : BuilderInterface {
            return $this;
        }


        public function groupBy() : BuilderInterface {
            return $this;
        }

        public function addGroupBy() : BuilderInterface {
            return $this;
        }


        public function having() : BuilderInterface {
            return $this;
        }

        public function andHaving() : BuilderInterface {
            return $this;
        }

        public function orHaving() : BuilderInterface {
            return $this;
        }


        public function orderBy($column, $direction) : BuilderInterface {
            return $this;
        }

        public function limit($limit) : BuilderInterface {
            $this->query->setLimit($limit);
            return $this;
        }

        public function offset($offset) : BuilderInterface {
            $this->query->setOffset($offset);
            return $this;
        }

        public function getQuery() : QueryInterface {
            return $this->query;
        }

        public function expr() {
            return new ExpressionBuilder();
        }

        public static function sp($name, ... $arguments) : StoredProcedure {
            return new StoredProcedure($name, $arguments);
        }
    }