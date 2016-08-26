<?php
    namespace Sebastian\Core\Cache\Driver;

    use Sebastian\Core\Cache\CacheManager;

    abstract class Driver {
        const DEFAULT_TTL = 120;
        protected $manager;

        public function __construct(CacheManager $manager) {
            $this->manager = $manager;
        }

        public function init() {}

        abstract public function clear($cache);
        abstract public function cache($key, $thing, $override, $ttl);
        abstract public function invalidate($key);
        abstract public function isCached($key);
        abstract public function load($key);
        abstract public function getInfo();
        abstract public function getMemInfo();

        public function getName() {
            return get_class($this);
        }
    }