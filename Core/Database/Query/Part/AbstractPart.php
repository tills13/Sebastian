<?php
    namespace Sebastian\Core\Database\Query\Part;

    class AbstractPart implements PartInterface {
        protected $binds = [];
        protected $bindTypes = [];

        public function bind(array $binds, array $bindTypes = []) {
            $this->binds = array_merge($binds, $this->binds);
            $this->bindTypes = array_merge($bindTypes, $this->bindTypes);
        }
        
        public function bindValue($name, $value) {
            $this->binds[$name] = $value;
        }

        public function getBinds() {
            return $this->binds;
        }

        public function getBindTypes() {
            return $this->bindTypes;
        }
    }