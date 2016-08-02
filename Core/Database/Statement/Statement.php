<?php
    namespace Sebastian\Core\Database\Statement;

    use \PDO as PDO;
    use \PDOStatement as PDOStatement;
    use \PDOException as PDOException;
    use Sebastian\Core\Database\PDO\SebastianPDO;
    use Sebastian\Core\Database\Transformer\TransformerInterface;
    use Sebastian\Utility\Configuration\Configuration;

    class Statement extends PDOStatement {
        protected $pdo;
        protected $columns;
        protected $logger;

        protected function __construct(SebastianPDO $pdo, Configuration $config = null) {
            $this->pdo = $pdo;
            $this->logger = $pdo->getLogger();
            $this->config = $config;
        }

        public function execute($params = [], $types = []) {
            $startTime = microtime(true);

            if (($transformer = $this->pdo->getTransformer()) !== null) {
                foreach ($params as &$param) {
                    $param = $transformer->transformToDBValue($param);
                }
            }
        
            try { parent::execute($params); } catch (PDOException $e) {
                throw $this->pdo->convertException($e);
            }

            $diff = microtime(true) - $startTime;
            //$this->logger->info("query completed in {$diff} seconds", "db_log", "QUERY");
        }

        public function getColumnType($column) {
            if (!is_string($column)) {
                //$column = $this->
            } 

            return $this->getColumnMeta($column); 
        }

        public function fetchAll($fetchStyle = PDO::FETCH_ASSOC, $className = null, $ctorArgs = null) {
            return parent::fetchAll($fetchStyle);
        }
    }
