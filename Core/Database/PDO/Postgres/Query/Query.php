<?php
    namespace Sebastian\Core\Database\PDO\Postgres\Query;

    use Sebastian\Core\Database\Query\AbstractQuery;

    class Query extends AbstractQuery {
        public function __toString() {
            $query  = "SELECT \n";
            $query .= $this->columnsToString() . "\n";
            $query .= "FROM " . $this->fromsToString() . "\n";

            foreach ($this->joins as $m => $join) {
                $query .= $join . "\n";
            }

            if ($this->where !== null) {
                $query .= "WHERE " . $this->where . "\n";
            }

            $orderBy = $this->getOrderBy();
            if ($orderBy && count($orderBy) != 0) {
                $query = $query . "ORDER BY ";

                $index = 0;
                foreach ($orderBy as $column => $direction) {
                    $direction = strtoupper($direction);
                    $query = $query . "{$column} {$direction}";
                    if (++$index != count($orderBy)) $query = $query . ",";
                    else $query = $query . "\n";
                }
            }

            if ($this->limit) $query .= "LIMIT {$this->limit}\n";
            if ($this->offset) $query .= "OFFSET {$this->offset}\n";
            
            return $query;
        }
    }