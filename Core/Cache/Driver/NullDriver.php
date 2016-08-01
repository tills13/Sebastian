<?php
    namespace Sebastian\Core\Cache\Driver;

    class NullDriver extends Driver {
        public function clear($cache) {
            return true;
        }

        public function cache($key, $thing, $override = false, $ttl = null) {
            return true;
        }

        public function invalidate($key) {
            return true;
        }

        public function isCached($key) {
            return false;
        }

        public function load($key) {
            return null;
        }

        public function getInfo() {
            return [];
        }

        public function getMemInfo() {
            return [];
        }
    }