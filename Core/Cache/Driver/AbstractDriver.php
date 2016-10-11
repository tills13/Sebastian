<?php
    namespace Sebastian\Core\Cache\Driver;

    use Sebastian\Core\Cache\CacheManager;
    use Sebastian\Core\Entity\EntityInterface;
    use Sebastian\Utility\Configuration\Configuration;

    abstract class AbstractDriver implements DriverInterface {
        const DEFAULT_TTL = 120;

        protected $config;
        protected $em;

        public function __construct(Configuration $config = null) {
            if (!$config) $config = new Configuration();

            $this->config = $config->extend([
                'key_generation_strategy' => [
                    'object' => '',
                    'other' => ''
                ]
            ]);
        }

        public function init() {}
        abstract public function clear($cache);
        abstract public function cache(string $key, $thing, $override, $ttl);
        abstract public function invalidate($key);
        abstract public function isCached($key);
        abstract public function load($key, bool $die = true);
        abstract public function getInfo();
        abstract public function getMemInfo();

        public function getName() {
            return get_class($this);
        }

        public function generateKey($thing) {
            if (is_object($thing)) $base = $this->config->get('key_generation_strategy.object');
            else $base = $this->config->get('key_generation_strategy.other');

            $fields = ['class', 'id', 'hash'];

            foreach ($fields as $field) {
                $base = preg_replace_callback("/\{{$field}\}/", function($matches) use ($field, $thing) {
                    if ($field == 'class') return get_class($thing);
                    else if ($field == 'id' && $thing instanceof EntityInterface) {
                        return $thing->getId(); // todo: no
                    } else if ($field == 'hash') {
                        return is_object($thing) ? spl_object_hash($thing) : hash('sha256', $thing);
                    } else return "";
                }, $base);
            }

            return $base;
        }
    }