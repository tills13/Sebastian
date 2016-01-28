<?php   
    namespace Sebastian;

    define('SEBASTIAN_ROOT', __DIR__);

    use Sebastian\Core\Cache\CacheManager;
    use Sebastian\Core\Database\Connection;
    use Sebastian\Core\Database\EntityManager;

    use Sebastian\Core\Http\Response\Response;
    use Sebastian\Core\Http\Request;
    use Sebastian\Core\Http\Router;
    use Sebastian\Core\Session\Session;

    use Sebastian\Core\Controller\Controller;

    use Sebastian\Core\Exception\SebastianException;

    use Sebastian\Core\Utility\Utils;

    /**
     * Kernel
     * @author Tyler <tyler@sbstn.ca>
     * @since  Oct. 2015
     */
    class Kernel {
        protected $config;

        protected $component;
        protected $components;
        protected $request;
        protected $router;
        protected $session;
        protected $services;

        public function __construct($env) {
            $this->env = $env;
            $this->config = $this->loadConfig("config_{$this->env}.yaml") ?: [];//yaml_parse_file(\APP_ROOT . "/../config/config_{$this->env}.yaml") ?: [];

            if ($this->getConfig('orm', false)) {
                $this->ormConfig = $this->loadConfig("orm_config.yaml") ?: [];    
            }

            $this->session = Session::fromGlobals($this);
            $this->registerComponents();

            $this->cacheManager = new CacheManager($this, $this->getConfig('cache.driver', null), $this->getConfig('cache', []));
            $this->entityManager = new EntityManager($this);
            $this->connection = new Connection($this, $this->getConfig('database', []));

            $this->router = Router::getRouter($this);
            $this->registerServices();
        }

        public function registerComponents() {
            $components = $this->getConfig('components') ?: [];

            $user = $this->getSession()->getUser();
            foreach ($components as $key => $component) {
                $requirements = isset($component['requirements']) ? $component['requirements'] : [];

                $valid = true;
                foreach ($requirements as $mKey => $requirement) {
                    if ($requirement == 'authenticated' && !$user) {
                        $valid = false;
                        break;
                    }

                    if ($requirement == 'admin' && (!$user || !$user->isAdmin())) {
                        $valid = false;
                        break;
                    }
                }

                if ($valid) $this->components[$key] = $component;
            }

            if (count($this->components) == 0) {
                throw new SebastianException("Error Processing Request", 1);
            }
        }

        public function registerServices() {
            $services = $this->getConfig('services');

            foreach ($services ?: [] as $name => $location) {
                $this->startService($name, $location);
            }
        }

        public function handleRequest(Request $request) {
            $this->request = $request;

            try {
                $resolvedRequest = $this->router->resolve($this->request);

                $controller = new $resolvedRequest[0]($this);
                $method = $resolvedRequest[1];
                $args = $resolvedRequest[2];

                $args['request'] = $request;

                if (!method_exists($controller, $method)) {
                    throw new PageNotFoundException("The requested method (<strong>{$method}</strong>) doesn't exist", 400);
                }
                
                $response = call_user_func_array([$controller, $method], $args);

                if ($response == null) throw new SebastianException("Controller must return a response.", 1);
                else return $response;
            } catch (SebastianException $e) {
                //var_dump("error: ".$e->getMessage());
                //$this->controller = new Controller($this);
                //return $this->controller->renderError($e);
                return new Response($e->getMessage());
            }
        }

        public function startService($name, $location) {
            $namespace = $this->getAppNamespace();
            if (strstr($location, ':')) {
                $location = explode(':', $location);
                $component = $location[0];
                $class = $location[1];
            } else return;

            $serviceLocation = "\\{$namespace}\\{$component}\\Service\\{$class}";

            $this->services[$name] = new $serviceLocation($this);
        }


        // GETTERS ====

        public function getSession() {
            return $this->session;
        }

        public function getRequest() {
            return $this->request;
        }

        public function getRouter() {
            return $this->router;
        }

        public function getController() {
            return $this->controller;
        }

        public function getConnection() {
            return $this->connection;
        }

        public function loadConfig($filename, $parse = true, $extensionOverride = null) {
            $filename = \APP_ROOT . "/../config/{$filename}";
            if (!file_exists($filename)) return null;

            if ($parse) {
                if ($extensionOverride === null) $fileType = Utils::getExtension($filename);
                else $fileType = $extensionOverride;

                if ($fileType == 'yaml' || $fileType == 'yml') return yaml_parse_file($filename);
                else if ($fileType == 'json') return json_decode(file_get_contents($filename));
                else return file_get_contents($filename);
            } else return file_get_contents($filename);
        }

        public function getConfig($path = null, $default = null) {
            $config = $this->config;

            if ($path) {
                foreach (explode('.', $path) as $subConfig) {
                    if (isset($config[$subConfig])) $config = $config[$subConfig];
                    else return $default;
                }
            }

            return $config;
        }

        public function getAppNamespace() {
            return $this->getConfig('application.namespace');
        }

        public function getLogFolder() {
            $appName = $this->getConfig('application.name');
            return "/var/log/{$appName}/";
        }

        public function getService($serviceName) {
            if (isset($this->services[$serviceName])) {
                return $this->services[$serviceName];
            } else return null;
        }

        /**
         * gets the registered components for the session
         * not garuanteed to be in order of precedence unless
         * called with the $sort=true param
         * 
         * @param  boolean $sort [description]
         * @return a list of components
         */
        public function getComponents($sort = false) {
            if ($sort) {
                uasort($this->components, function($a, $b) {
                    return $a['weight'] < $b['weight'];
                });
            }

            return $this->components;
        }

        public function getApplicableComponent() {
            return array_keys($this->getComponents(true))[0];
        }

        public function getEnvironment() {
            return $this->env;
        }

        public function getCacheManager() {
            return $this->cacheManager;
        }

        public function getEntityManager() {
            return $this->entityManager;
        }
    }