<?php
    namespace Sebastian\Core\Cache;

    use Sebastian\Core\Entity\Entity;
    use Sebastian\Utility\Configuration\Configuration;

    /**
     * CacheManager
     * @author Tyler <tyler@sbstn.ca>
     * @since  Oct. 2015
     */
    class CacheManager {
        const DEFAULT_DRIVER = 'Sebastian\Core:NullDriver';
        const APCU_DRIVER = 'Sebastian\Core:NullDriver';
        const ARRAY_DRIVER = 'Sebastian\Core:Volatile\ArrayDriver';
        public static $tag = "CacheManager";
        public static $logger;

        protected $context;
        protected $options;
        protected $driver;

        public function __construct($context, Configuration $config) {
            $this->context = $context;
            $this->config = $config->extend([
                'driver' => CacheManager::DEFAULT_DRIVER,
                'enabled' => false,
                'key_generation_strategy' => [
                    'object' => '{class}_{component}_{id}',
                    'other' => '{hash}'
                ]
            ]);

            $this->initializeDriver($this->config->get('driver'));
        }

        // todo needs to handle overrides properly (for custom drivers)
        public function initializeDriver($driverClass) {
            $driverClass = explode(':', $driverClass);

            if (count($driverClass) != 2) {
                throw new SebastianException("Cache driver config must be of the form {Namespace}:{Class}");
            }

            $driverNamespace = $driverClass[0];
            $driverClassName = $driverClass[1];

            $classPath = "\\{$driverNamespace}\\Cache\\Driver\\{$driverClassName}";
            $this->driver = new $classPath($this);
            $this->driver->init();
        }

        public function clear($cache = "") {
            //CacheManager::$logger->info("clearing\t>\t{$which}");
            return $this->driver->clear($cache);
        }

        public function cache($key = null, $thing, $override = false, $ttl = null) {
            if ($key == null) $key = $this->generateKey($thing);
            return $this->driver->cache($key, $thing, $override, $ttl);
        }

        public function invalidate($key) {
            return $this->driver->invalidate($key);
        }

        public function isCached($key) {
            return $this->driver->isCached($key);
        }

        public function load($key) {
            return $this->driver->load($key);
        }

        public function getDriver() {
            return $this->driver;
        }

        public function setDriver(Driver $driver) {
            $this->driver = $driver;
        }

        public function generateKey($thing) {
            if (is_object($thing)) $base = $this->config->get('key_generation_strategy.object');
            else $base = $this->config->get('key_generation_strategy.other');

            $fields = ['component', 'class', 'id', 'hash'];

            foreach ($fields as $field) {
                $context = $this->context;
                $base = preg_replace_callback("/\{{$field}\}/", function($matches) use ($context, $field, $thing) {
                    if ($field == 'class') return get_class($thing);
                    else if ($field == 'component') return "Common";//$context->getApplicableComponent();
                    else if ($field == 'id') return $thing->getId(); // todo: no
                    else if ($field == 'hash') {
                        return is_object($thing) ? spl_object_hash($thing) : hash('sha256', $thing);
                    } else return "";
                }, $base);
            }

            return $base;
        }
    }