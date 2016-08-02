<?php
    namespace Sebastian\Core\Database\PDO;

    use \PDOException;

    use Sebastian\Core\Database\Connection;
    use Sebastian\Core\Database\Statement\Statement;
    use Sebastian\Utility\Configuration\Configuration;
    use Sebastian\Core\Database\Exception\DatabaseException;
    use Sebastian\Core\Database\Exception\UniqueConstraintException;
    use Sebastian\Core\Database\Transformer\PostgresTransformer;

    class PostgresPDO extends SebastianPDO {
        public function __construct(Connection $connection, $username, $password, Configuration $config) {
            $this->setDriverName('pgsql');
            $this->setTransformer(new PostgresTransformer($connection));

            parent::__construct($connection, $username, $password, $config);
        }

        public function prepare($statement, $options = null) {
            if ($this->config->get('connection.tagging', false)) {
                $statement = $this->tagQuery($statement);
            }
        
            return parent::prepare($statement, $options);
        }

        public function execute($statement) {
            if ($this->config->get('connection.tagging', false)) {
                $statement = $this->tagQuery($statement);
            }

            return parent::execute($statement);   
        }

        public function convertException(PDOException $e) {
            $code = $e->getCode();

            if ($code == 23505) {
                return new UniqueConstraintException("Unique constraint violation", $code, $e);
            } else if ($code == 42703) {
                $message = $e->getMessage();
                return new DatabaseException($message, $code, $e);
            }

            return $e;
        }

        public function tagQuery(string $query, $depth = 1) {
            $trace = debug_backtrace();
            $caller = $trace[min($depth, count($trace) - 1)];
            
            while(isset($caller['class']) &&
                ($caller['class'] == __CLASS__ ||
                strstr($caller['class'], 'Sebastian'))) {
                // stay at the last index
                if (++$depth >= count($trace)) {
                    break;
                }
                $caller = $trace[$depth];
            }
            
            $bt = $caller['function'] . '()';
            if (isset($caller['class'])){
                $bt = $caller['class'] . '::' . $bt;
            }
            $bt  = '-- ' . $bt . "\n";
            if (strpos($caller['function'], '{closure}') === false) {
                $line = empty($caller['line']) ? '(unknown)' : $caller['line'];
                $file = empty($caller['file']) ? '(unknown file)' : $caller['file'];
                $bt .= '-- ' . $file . ' line ' . $line . "\n";
            }
            
            return "\n" . $bt . $query . "\n";
        }
    }