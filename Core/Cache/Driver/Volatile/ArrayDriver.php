<?php
    namespace Sebastian\Core\Cache\Driver\Volatile;

    use Sebastian\Core\Cache\Driver\AbstractDriver;

    class ArrayDriver extends AbstractDriver {
        protected $cache;

        public function init() {
            $this->cache = [];
        }

        public function cache(string $key, $thing, $override = false, $ttl = null) {
            $ttl = $ttl ?: self::DEFAULT_TTL;

            if ($override || (!$override && !$this->isCached($key))) {
                $this->cache[$key] = [
                    'inserted_at' => new \DateTime(),
                    'ttl' => $ttl,
                    'item' => $thing
                ];

                return true;
            }

            return false;
        }

        public function clear($cache) {
            $this->cache = [];
            return true;
        }

        public function invalidate($key) {
            unset($this->cache[$key]);
            return true;
        }

        public function isCached($key) {
            return in_array($key, array_keys($this->cache));
            //return isset($this->cache[$key]);
        }

        public function load($key, bool $die = true) {
            return $this->isCached($key) ? $this->cache[$key]['item'] : null;
        }

        public function getCache() {
            return $this->cache;
        }

        public function getInfo() {
            return [];
        }

        public function getMemInfo() {
            return [];
        }
    }