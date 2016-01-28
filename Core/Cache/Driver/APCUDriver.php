<?php
    namespace Sebastian\Core\Cache\Driver;

    class APCUDriver extends Driver {
        public function clear($cache) {
            return apc_clear_cache($cache);
        }

        public function cache($key, $thing, $override = false, $ttl = null) {
            $ttl = $ttl ?: Driver::DEFAULT_TTL;

            if ($override || (!$override && !$this->isCached($key))) {
                return apcu_store($key, $thing, $ttl);
            }

            return false;
        }

        public function invalidate($key) {
            return apcu_delete($key);
        }

        public function isCached($key) {
            return apc_exists($key);
        }

        public function load($key) {
            $object = apcu_fetch($key, $success);

            if (!$success) throw new CacheException("failed to load {$key} from cache");
            return $object;
        }
    }