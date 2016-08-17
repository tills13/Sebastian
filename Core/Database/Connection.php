<?php   
    namespace Sebastian\Core\Database;

    use \PDO;
    use \PDOException;
    use Sebastian\Core\Exception\SebastianException;
    use Sebastian\Core\Database\Exception\DatabaseException;
    use Sebastian\Core\Database\Statement\PreparedStatement;
    use Sebastian\Utility\ClassMapper\ClassMapper;
    use Sebastian\Utility\Configuration\Configuration;
    use Sebastian\Utility\Collection\Collection;
    use Sebastian\Utility\Logger\Logger;

    /**
     * Connection
     * 
     * @author Tyler <tyler@sbstn.ca>
     * @since  Oct. 2015
     */
    class Connection {
        protected $driver;
        protected $context;
        protected $config;
        protected $logger;

        public function __construct($context, Configuration $config) {
            $this->context = $context;
            $this->config = $config->extend([
                'driver' => 'Sebastian\Core:PostgresDriver',
                'hostname' => null,
                'port' => null,
                'dbname' => null,
                'username' => null,
                'password' => null, 
                'connection' => [
                    'tagging' => true,
                    'lazy' => false,
                    'caching' => false,
                    'connection_timeout' => 5
                ]
            ]);

            $this->logger = $context->getLogger();
            $this->cm = $context->getCacheManager();

            if (!$this->config->get('connection.lazy', true)) {
                $this->initializeDriver($config->get('driver'));
            }
        }

        // todo needs to handle overrides properly (for custom drivers)
        public function initializeDriver($driverClass) {
            $class = ClassMapper::parseClass($driverClass, "Database\\PDO");

            try {
                $this->driver = new $class(
                    $this, 
                    $this->config->get('username'), 
                    $this->config->get('password'), 
                    $this->config
                );
            } catch (PDOException $e) {
                var_dump($e); die();
                $this->driver = null;
            }
        }

        public function __call($name, $arguments) {
            $args = str_repeat('?, ', count($arguments) - 1) . " ?";
            $ps = $this->prepare("SELECT * FROM {$name}({$args})");
            $ps->execute($arguments);
            
            return $ps;
        }

        public function beginTransaction() {
            $this->connect();
            return $this->driver->beginTransaction();
        }

        public function commit() {
            $this->connect();
            return $this->driver->commit();
        }

        public function connect() {
            if ($this->driver) return;
            else {
                $this->initializeDriver($this->config->get('driver'));

                if (!$this->driver) {
                    throw new DatabaseException("Driver not set up properly.");
                }
            }
        }

        public function close() {
            if (!$this->driver) return;
            $this->driver = null;
        }

        public function execute($query, $params = []) {
            $this->connect();

            if (is_object($query)) $query = (string) $query;
            if ($params instanceof Collection) $params = $params->toArray();

            $ps = $this->prepare($query);
            $ps->execute($params);
            return $ps;
        }

        public function prepare($query, array $options = []) {
            $this->connect();
            return $this->driver->prepare($query, $options);
        }

        public function quote($string, $params = PDO::PARAM_STR) {
            $this->connect();
            return $this->driver->quote($string, $params);
        }

        public function rollback() {
            $this->connect();
            return $this->driver->rollback();
        }

        public function inTransaction() {
            $this->connect();
            return $this->driver->inTransaction();
        }

        public function getAttribute($attribute) {
            $this->connect();
            return $this->driver->getAttribute($attribute);
        }

        public function getConfig() {
            return $this->config;
        }

        public function getDriver() {
            return $this->driver;
        }

        public function getLastError() {
            $this->connect();
            return $this->driver->errorInfo();
        }

        public function getLastErrorCode() {
            $this->connect();
            return $this->driver->errorCode();
        }

        public function getLastId($name = null) {
            $this->connect();
            return $this->driver->lastInsertId($name);
        }

        public function setLogger(Logger $logger) {
            $this->logger = $logger;
        }

        public function getLogger() {
            return $this->logger;
        }
    }