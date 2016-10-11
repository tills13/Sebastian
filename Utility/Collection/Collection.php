<?php
    namespace Sebastian\Utility\Collection;

    class Collection implements \ArrayAccess,\Iterator,\JsonSerializable {
        private $position;
        private $_collection;

        public function __construct(array $collection = null) {
            $this->position = 0;
            $this->_collection = $collection ?: [];
        }

        public function get($path = null, $default = null) {
            $collection = $this->_collection;

            if ($path !== null) {
                foreach (explode('.', $path) as $part) {
                    if (isset($collection[$part])) $collection = $collection[$part];
                    else return $default;
                }
            }

            return $collection;
        }

        public function offsetGet($offset) {
            return $this->get($offset, null);
        }

        public function set($path = null, $value = null) {
            $collection = &$this->_collection;

            if ($path == null) $path = $this->count();

            if ($path !== null) {
                $path = explode('.', $path);
                $target = array_pop($path);

                foreach ($path as $index => $part) {
                    if (!isset($collection[$part])) $collection[$part] = [];
                    $collection = &$collection[$part];
                }

                $collection[$target] = $value;
            }
        }

        public function remove($path) {
            $collection = &$this->_collection;

            if ($path !== null) {
                $path = explode('.', $path);
                $target = array_pop($path);

                foreach ($path as $index => $part) {
                    if (!isset($collection[$part])) return false;
                    $collection = &$collection[$part];
                }

                unset($collection[$target]);
                return true;
            }

            return false;
        }

        public function offsetSet($offset, $value) {
            return $this->set($offset, $value);
        }

        public function offsetUnset($offset) {
            return $this->remove($offset);
        }

        public function extend($collection) {
            $this->_collection = array_merge($collection, $this->_collection);

            return $this;
        }

        public function sub($path, $default = []) {
            $sub = $this->get($path, $default);

            return !is_array($sub) ? $sub : new $this($sub);
        }

        public function filter($callable) {
            $collection = $this->_collection;
            $collection = array_filter($collection, $callable, ARRAY_FILTER_USE_BOTH);
            return new $this($collection);
        }

        /**
         * [has description]
         * @param  [type]  $path          [description]
         * @param  boolean $caseSensitive [description]
         * @return boolean                [description]
         *
         * @todo fix case sensitivity
         */
        public function has($path, $caseSensitive = true) {
            if (!$path || $path == null) return false;
            if (!$caseSensitive) $path = strtolower($path);
            $collection = $this->_collection;

            if ($path) {
                foreach (explode('.', $path) as $part) {
                    if (!$caseSensitive) {
                        $keys = array_map(function($key) { return strtolower($key); }, array_keys($collection));    
                        if (in_array($path, $keys)) $collection = $collection[$part];
                        else return false;
                    } else {
                        if (isset($collection[$part])) $collection = $collection[$part];
                        //if (array_key_exists($part, $collection)) $collection = $collection[$part];
                        else return false;
                    }
                }
            }

            return true;
        }

        public function offsetExists($offset) {
            return $this->has($offset);
        }

        public function rewind() {
            $this->position = 0;
        }

        public function current() {
            return $this->sub($this->key());
        }

        public function key() {
            return array_keys($this->_collection)[$this->position];
        }

        public function next() {
            $this->position++;
        }

        public function valid() {
            return $this->position < count(array_keys($this->_collection));
            //return isset($this->_collection[$this->key()]);
        }

        public function count() {
            return count(array_keys($this->_collection));
        }

        public function toArray() {
            return $this->_collection;
        }

        public function jsonSerialize() {
            return $this->toArray();
        }

        public function __debugInfo() {
            return $this->_collection;
        }

        public function __toString() {
            $inner = var_export($this->_collection);
            return "[{$inner}]";
        }
    }