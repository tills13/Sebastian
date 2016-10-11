<?php
    namespace Sebastian\Core\Cache\Driver;

    use Sebastian\Utility\Configuration\Configuration;

    interface DriverInterface {
        public function __construct(Configuration $config = null);
        public function init();
        public function clear($cache);
        public function cache(string $key, $thing, $override, $ttl);
        public function invalidate($key);
        public function isCached($key);
        public function load($key, bool $die = true);
        public function getInfo();
        public function getMemInfo();
        public function getName();
        public function generateKey($thing);
    }