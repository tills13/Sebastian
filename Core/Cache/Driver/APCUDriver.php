<?php
    namespace Sebastian\Core\Cache\Driver;

    class APCUDriver extends AbstractDriver {
        public function cache(string $key, $thing, $override = false, $ttl = null) {
            $ttl = $ttl ?: self::DEFAULT_TTL;

            if ($override || (!$override && !$this->isCached($key))) {
                return apcu_store($key, $thing, $ttl);
            }

            return false;
        }

        public function clear($cache) {
            return apc_clear_cache($cache);
        }

        public function invalidate($key) {
            return apcu_delete($key);
        }

        public function isCached($key) {
            return apcu_exists($key);
        }

        public function load($key, bool $die = true) {
            $object = apcu_fetch($key, $success);

            if (!$success && $die) throw new CacheException("failed to load {$key} from cache");
            return $object;
        }

        public function getInfo() {
            return apcu_cache_info();
        }

        public function getMemInfo() {
            return apcu_sma_info();
        }
    }