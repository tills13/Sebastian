<?php
    namespace Sebastian\Core\Cache\Driver\Volatile;

    use Sebastian\Core\Cache\CacheManager;
    use Sebastian\Core\Cache\Driver\Driver;

    class ArrayDriver extends Driver {
        protected $cache;

        public function __construct(CacheManager $manager) {
            parent::__construct($manager);

            $this->cache = [];
        }

        public function clear($cache) {
            $this->cache = [];
            return true;
        }

        public function cache($key, $thing, $override = false, $ttl = null) {
            $ttl = $ttl ?: Driver::DEFAULT_TTL;

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

        public function invalidate($key) {
            unset($this->cache[$key]);
            return true;
        }

        public function isCached($key) {
            return in_array($key, array_keys($this->cache));
            //return isset($this->cache[$key]);
        }

        public function load($key) {
            return $this->isCached($key) ? $this->cache[$key]['item'] : null;
        }

        public function getCache() {
            return $this->cache;
        }
    }