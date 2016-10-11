<?php
    namespace Sebastian\Core\Cache;

    use Sebastian\Core\Context\ContextInterface;
    use Sebastian\Core\DependencyInjection\Injector;
    use Sebastian\Core\Entity\EntityInterface;
    use Sebastian\Utility\ClassMapper\ClassMapper;
    use Sebastian\Utility\Configuration\Configuration;

    /**
     * CacheManager
     * @author Tyler <tyler@sbstn.ca>
     * @since  Oct. 2015
     */
    class CacheManager {
        const NULL_DRIVER = 'Sebastian\Core:NullDriver';
        const APCU_DRIVER = 'Sebastian\Core:APCUDriver';
        const ARRAY_DRIVER = 'Sebastian\Core:Volatile\ArrayDriver';
        const DEFAULT_DRIVER = self::NULL_DRIVER;

        public static $tag = "CacheManager";

        protected $config;
        protected $context;
        protected $drivers;
        protected $logger;

        public function __construct(ContextInterface $context, array $drivers = []) {
            $this->context = $context;

            $this->initialize($drivers);
        }

        public function initialize(array $drivers = null) {
            foreach ($drivers ?? [] as $name => $config) {
                $this->initializeDriver($name, $config);
            }
        }

        public function initializeDriver(string $name, array $config = []) {
            $config = new Configuration($config);

            if (!$config->has('driver')) {
                throw new Exception('Cache driver configuration requires a "driver" parameter');
            }

            $driver = Injector::instance($config->get('driver'), "Cache\\Driver", [
                '$config' => $config
            ]);

            $driver->init();

            $this->drivers[$name] = $driver;
            return $driver;
        }

        public function getDriver(string $name, array $config = null) {
            return $this->drivers[$name] ?? $this->initializeDriver($name, $config);
        }

        public function getDrivers() {
            return $this->drivers;
        }
    }