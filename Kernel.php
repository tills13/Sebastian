<?php   
    namespace Sebastian;

    define('SEBASTIAN_ROOT', __DIR__);

    use Sebastian\Core\Cache\CacheManager;
    use Sebastian\Utility\Configuration\Configuration;
    use Sebastian\Core\Controller\Controller;
    use Sebastian\Core\Database\Connection;
    use Sebastian\Core\Database\EntityManager;
    use Sebastian\Core\Exception\SebastianException;
    use Sebastian\Core\Http\Response\Response;
    use Sebastian\Core\Http\Request;
    use Sebastian\Core\Http\Router;
    use Sebastian\Core\Session\Session;

    /**
     * Kernel
     * @author Tyler <tyler@sbstn.ca>
     * @since  Oct. 2015
     */
    class Kernel {
        protected $config;

        protected $application;
        protected $request;

        public function __construct($env) {
            $this->env = $env;
            $this->request = Request::fromGlobals();

            $this->boot();
        }

        public function boot() {
            $this->config = Configuration::fromFilename("config_{$this->env}.yaml");

            if ($this->config->has('application.app_class')) {
                $namespace = $this->config->get('application.namespace');
                $appClass = $this->config->get('application.app_class');
                $applicationPath = "\\{$namespace}\\{$appClass}";

                $this->application = new $applicationPath($this, $this->config);
            }
        }

        public function run($params = null) {
            return $this->application->handle($this->request);
        }

        public function shutdown(Response $response) {
            $this->application->shutdown($this->request, $response);
        }

        public function getRequest() {
            return $this->request;
        }

        public function getEnvironment() {
            return $this->env;
        }
    }