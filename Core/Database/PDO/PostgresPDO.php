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
    }