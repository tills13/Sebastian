<?php
    namespace Sebastian\Core\Database\Transformer;

    use Sebastian\Core\Database\Driver\PostgresDriver;

    class PostgresTransformer extends AbstractTransformer {
        protected $postgresTypesToPhpTypes = [
            'bit' => ['bit'],
            'bool' => ['boolean'],
            'box' => ['box'],
            'bpchar' => ['character', 'char'],
            'bytea' => ['bytea'],
            'cidr' => ['cidr'],
            'circle' => ['circle'],
            'date' => ['date'],
            'daterange' => ['daterange'],
            'float4' => ['real', 'double'],
            'float8' => ['double precision', 'double'],
            'inet' => ['inet'],
            'int2' => ['smallint', 'smallserial', 'integer'],
            'int4' => ['integer', 'serial'],
            'int4range' => ['int4range'],
            'int8' => ['bigint', 'bigserial'],
            'int8range' => ['int8range'],
            'interval' => ['interval'],
            'json' => ['json'],
            'lseg' => ['lseg'],
            'macaddr' => ['macaddr'],
            'money' => ['money'],
            'numeric' => ['decimal', 'numeric'],
            'numrange' => ['numrange'],
            'path' => ['path'],
            'point' => ['point'],
            'polygon' => ['polygon'],
            'text' => ['text'],
            'time' => ['time', 'time without time zone'],
            'timestamp' => ['timestamp', 'timestamp without time zone'],
            'timestamptz' => ['timestamp with time zone'],
            'timetz' => ['time with time zone'],
            'tsquery' => ['tsquery'],
            'tsrange' => ['tsrange'],
            'tsvector' => ['tsvector'],
            'uuid' => ['uuid'],
            'varbit' => ['bit varying'],
            'varchar' => ['character varying', 'varchar'],
            'xml' => ['xml'],
        ];

        protected $phpTypesToPostgresTypes = [
            'bit' => 'bit',
            'boolean' => 'bool',
            'box' => 'box',
            'character' => 'bpchar',
            'char' => 'bpchar',
            'bytea' => 'bytea',
            'cidr' => 'cidr',
            'circle' => 'circle',
            'date' => 'date',
            'datetime' => 'timestamp',
            'daterange' => 'daterange',
            'real' => 'float4',
            'double precision' => 'float8',
            'inet' => 'inet',
            'smallint' => 'int2',
            'smallserial' => 'int2',
            'integer' => 'int4',
            'serial' => 'int4',
            'int4range' => 'int4range',
            'bigint' => 'int8',
            'bigserial' => 'int8',
            'int8range' => 'int8range',
            'interval' => 'interval',
            'json' => 'json',
            'lseg' => 'lseg',
            'macaddr' => 'macaddr',
            'money' => 'money',
            'decimal' => 'numeric',
            'numeric' => 'numeric',
            'numrange' => 'numrange',
            'path' => 'path',
            'point' => 'point',
            'polygon' => 'polygon',
            'text' => 'text',
            'time' => 'time',
            'time without time zone' => 'time',
            'timestamp' => 'timestamp',
            'timestamp without time zone' => 'timestamp',
            'timestamp with time zone' => 'timestamptz',
            'time with time zone' => 'timetz',
            'tsquery' => 'tsquery',
            'tsrange' => 'tsrange',
            'tstzrange' => 'tstzrange',
            'tsvector' => 'tsvector',
            'uuid' => 'uuid',
            'bit varying' => 'varbit',
            'character varying' => 'varchar',
            'varchar' => 'varchar',
            'xml' => 'xml'
        ];

        public function getDatabaseType($phpType) {
            if (!isset($this->phpTypesToPostgresTypes[$phpType])) return null;

            return $this->phpTypesToPostgresTypes[$phpType];
        }

        public function getPhpType($databaseType) {
            return $this->postgresTypesToPhpTypes[$databaseType];
        }

        public function transformToPhpValue($value, $dbType = null) {
            $phpTypes = $this->getPhpType($dbType);

            if (in_array('boolean', $phpTypes)) {
                $value = $value === "t" ? true : false;
            } else if (in_array('integer', $phpTypes)) {
                $value = intval($value, 10); // explicit base ten
            } else if (in_array('double', $phpTypes)) {
                $value = floatval($value);
            } else if (in_array('timestamp', $phpTypes)) {
                $value = new \DateTime($value);
            }

            return $value;
        }

        public function transformToDBValue($value, $phpType = null) {
            if (!$phpType) $phpType = strtolower(is_object($value) ? get_class($value) : gettype($value));

            $dbType = $this->getDatabaseType($phpType);

            if ($dbType == 'timestamp') {
                if (!$value instanceof \DateTime) $value = new \DateTime($value);
                $value = $value->format('Y-m-d g:i:s');
            } else if ($dbType == 'boolean' || $dbType == 'bool') {
                $value = $value ? "TRUE" : "FALSE";
            }

            return $value;
        }
    }