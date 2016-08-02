<?php
    namespace Sebastian\Core\Database\PDO;

    use \PDO;
    use \PDOException;
    use Sebastian\Core\Database\Connection;
    use Sebastian\Core\Database\Statement\Statement;
    use Sebastian\Core\Database\Transformer\TransformerInterface;
    use Sebastian\Utility\Configuration\Configuration;
    use Sebastian\Utility\Logger\Logger;

    abstract class SebastianPDO extends PDO {
        protected $connection;
        protected $config;
        protected $logger;
        protected $transformer;

        public function __construct(Connection $connection, $username, $password, Configuration $config) {
            $this->connection = $connection;
            $this->config = $config;
            $this->logger = $connection->getLogger();

            $dns = "{$this->getDriverName()}:
                        host={$config->get('hostname')};
                        port={$config->get('port', 5432)};
                        dbname={$config->get('dbname', 'postgres')};";

            parent::__construct($dns, $username, $password, []);
            
            if ($config->get('connection.persistent', false)) {
                $this->setAttribute(PDO::ATTR_PERSISTENT, true);
            } else {
                if ($config->has('connection.statement_class')) {
                    $statementClass = ClassMapper::parse($config->get('connection.statement_class'));
                } else $statementClass = Statement::class;
                
                $this->setAttribute(PDO::ATTR_STATEMENT_CLASS, [$statementClass, [$this, $config->sub('connection')]]);
            }
        
            $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // todo configurable
            $this->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        }

        public function setDriverName($driverName) {
            $this->driverName = $driverName; 
        }

        public function getDriverName() {
            return $this->driverName;
        }

        public abstract function convertException(PDOException $e); 

        public function setLogger(Logger $logger) {
            $this->logger = $logger;
        }

        public function getLogger() {
            return $this->logger;
        }

        public function setTransformer(TransformerInterface $transformer) {
            $this->transformer = $transformer;
        }

        public function getTransformer() {
            return $this->transformer;
        }
    }