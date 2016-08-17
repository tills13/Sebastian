<?php
    namespace Sebastian\Core\Controller;

    use Sebastian\Application;
    use Sebastian\Core\Component\Component;
    use Sebastian\Core\Context\Context;
    use Sebastian\Core\Exception\SebastianException;
    use Sebastian\Core\Http\Request;
    use Sebastian\Core\Http\Response\RedirectResponse;
    use Sebastian\Core\Http\Response\Response;
    use Sebastian\Core\Http\Response\JsonResponse;
    
    use Sebastian\Utility\Form\FormFactory;
    use Sebastian\Utility\Utility\Utils;
    
    /**
     * Controller
     * 
     * @author Tyler <tyler@sbstn.ca>
     * @since  Oct. 2015
     */
    class Controller extends Context {
        protected $extensions;
        protected $context;
        protected $component;

        public function __construct(Application $context, Component $component) {
            if (!$context || !$component) throw new Exception("Application and Component must be provided to the controller", 1);
            parent::__construct();

            $this->context = $context;
            $this->component = $component;

            $this->renderer = $this->context->get('templating');
        }

        public function render($template, array $data = []) {
            return $this->renderer->render($template, $data);
            //$response = new Response();
            //$response->setContent($this->renderer->render($template, $data));
            //$response->sendHttpResponseCode(Response::HTTP_OK);
            //return $response;
        }

        public function redirect($url, $https = false, $code = Response::HTTP_FOUND) {
            return new RedirectResponse($url, $code);
        }

        public function generateUrl($route = null, $args = []) {
            return $this->getRouter()->generateUrl($route, $args);
        }

        public function __call($method, $args) {
            $extension = parent::__call($method, $args);

            if (is_null($extension)) {
                return $this->context->$method($args);
            }
        }

        public function getCacheManager() {
            return $this->getContext()->getCacheManager();
        }

        public function getComponent() {
            return $this->component;
        }

        public function getConnection() {
            return $this->getContext()->getConnection();
        }

        public function getContext() {
            return $this->context;
        }

        public function getRequest() {
            return $this->getContext()->getRequest();
        }

        public function getSession() {
            return $this->getContext()->getSession();
        }

        public function getService($serviceName) {
            return $this->getContext()->getService($serviceName);
        }       

        public function __toString() {
            return get_class($this);
        }
    }