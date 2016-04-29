<?php
	namespace Sebastian\Core\Controller;

	use Sebastian\Application;
	use Sebastian\Core\Component\Component;
	use Sebastian\Core\Exception\SebastianException;
	use Sebastian\Core\Http\Request;
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
	class Controller {
		protected $headers;

		protected $context;
		protected $component;

		protected $jsFiles;
		protected $jsScripts;
		protected $cssFiles;

		protected $title;
		protected $subtitle;

		protected $tabs;

		public function __construct(Application $context, Component $component) {
			if (!$context || !$component) throw new Exception("Application and Component must be provided to the controller", 1);
			
			$this->context = $context;
			$this->component = $component;

			$this->cssFiles = [];
			$this->jsFiles = [];

			$this->renderer = $this->context->get('templating');
		}

		public function render($template, $data = []) {
			$response = new Response();
			$response->setContent($this->renderer->render($template, $data));
			$response->sendHttpResponseCode(Response::HTTP_OK);
			return $response;
		}

		public function redirect($url, $https = false, $code = Response::HTTP_FOUND) {
			return new RedirectResponse($url, $code);
		}

		public function addJavascriptFiles($files = []) {
			foreach ($files as $file) {
				if (is_array($file)) {
					$this->addJavascriptFile($file['filename'], $file['isMin'], $file['version']);
				} else $this->addJavascriptFile($file, false, 1);
			}
		}

		public function addJavascriptFile($filename, $min = false, $version = 1) {
			$this->jsFiles[$filename] = [
				'filename' => $filename,
				'isMin' => $min,
				'version' => $version
			];
		}

		public function addCSSFile($filename, $min = false, $version = 1) {
			$this->cssFiles[$filename] = [
				'filename' => $filename,
				'isMin' => $min,
				'version' => $version
			];
		}

		public function generateUrl($route = null, $args = []) {
			return $this->getContext()->getRouter()->generateUrl($route, $args);
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

		public function getEntityManager() {
			return $this->getContext()->getEntityManager();
		}

		public function getFormFactory() {
			return FormFactory::getFactory(
				$this->getContext(),
				$this->getContext()->getConfig()->sub('form.factory', [])
			);
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