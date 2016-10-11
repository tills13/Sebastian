<?php
    namespace Sebastian\Core\Database\Query\Part;

    use Sebastian\Core\Database\Connection;

    class StoredProcedure extends AbstractPart {
        protected $name;
        protected $arguments;

        public function __construct($name, $arguments) {
            $this->name = $name;
            $this->arguments = $arguments;
        }

        public function __toString() {
            return "{$this->name}(" . implode(', ', $this->arguments) . ")";
        }
    }