<?php
    namespace Sebastian\Core\Database\Transformer;

    use Sebastian\Core\Database\Connection;

    interface TransformerInterface {
        public function __construct(Connection $connection);
        public function getDatabaseType($phpType);
        public function getPhpType($databaseType);
        public function transformToPhpValue($value, $dbType);
        public function transformToDBValue($value, $phpType);
    }