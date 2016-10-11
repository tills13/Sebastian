<?php
    namespace Sebastian\Core\Cache\Driver;

    class NullDriver extends AbstractDriver {
        public function cache(string $key, $thing, $override = false, $ttl = null) {
            return true;
        }

        public function clear($cache) {
            return true;
        }

        public function invalidate($key) {
            return true;
        }

        public function isCached($key) {
            return false;
        }

        public function load($key, bool $die = true) {
            return null;
        }

        public function getInfo() {
            return [];
        }

        public function getMemInfo() {
            return [];
        }
    }