<?php
    namespace Sebastian\Core\Controller;

    use Sebastian\Application;
    use Sebastian\Core\Component\Component;
    use Sebastian\Core\Context\Context;
    use Sebastian\Core\Context\ContextInterface;
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
        protected $context;
        protected $component;
        protected $renderer;

        public function __construct(Application $context, Component $component) {
            parent::__construct();

            $this->context = $context;
            $this->component = $component;

            // convenience
            $this->renderer = $this->context->get('templating');
        }

        public function render($template, array $data = []) : string {
            return $this->renderer->render($template, $data);
        }

        public function redirect($url, $code = Response::HTTP_FOUND) : Response {
            return new RedirectResponse($url, $code);
        }

        public function generateUrl($route = null, $args = []) : string {
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

        public function getComponent() : Component {
            return $this->component;
        }

        public function getConnection() {
            return $this->getContext()->getConnection();
        }

        public function getContext() : ContextInterface {
            return $this->context;
        }

        public function getRequest() {
            return $this->getContext()->getRequest();
        }

        public function getSession() {
            return $this->getContext()->getSession();
        }

        public function getService($service) {
            return $this->getContext()->getService($service);
        }       

        public function __toString() {
            return get_class($this);
        }
    }