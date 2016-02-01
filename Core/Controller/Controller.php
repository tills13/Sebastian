<?php
	namespace Sebastian\Core\Controller;

	use Sebastian\Core\Http\Request;
	use Sebastian\Core\Http\Response\Response;
	use Sebastian\Core\Http\Response\JsonResponse;

	use Sebastian\Core\Form\Form;

	use Sebastian\Core\Utility\Utils;
	use Sebastian\Core\Exception\SebastianException;
	
	/**
	 * Controller
	 * 
	 * @author Tyler <tyler@sbstn.ca>
	 * @since  Oct. 2015
	 */
	class Controller {
		protected $headers;

		protected $context;

		protected $jsFiles;
		protected $jsScripts;
		protected $cssFiles;

		protected $title;
		protected $subtitle;

		protected $tabs;

		public function __construct($context) {
			$this->context = $context;

			$this->masterLayout = "master";
			$this->headers = [];
			$this->cssFiles = [];
			$this->jsFiles = [];
		}

		public function render($path = false, $data = []) {
			ob_start();
			
			if ($this->getRequest()->getType() == Request::REQUEST_TYPE_DEFAULT) { // everything
				$response = new Response();
				$data['body'] = $this->getViewContents($path, $data);
				$this->renderView($this->masterLayout, $data);
			} else if ($this->getRequest()->getType() == Request::REQUEST_TYPE_VIEW) { // just the view
				$response = new Response();
				$this->renderView($path, $data);
			} else if ($this->getRequest()->getType() == Request::REQUEST_TYPE_JSON) {
				$response = new JsonResponse();
				$this->masterLayout = "json.{$this->masterLayout}";
				$data['success'] = (isset($data['success']) ? $data['success'] : false);
				$this->renderView($this->masterLayout, $data);
			}

			$responseBody = ob_get_clean();
			$response->setContent($responseBody);

			return $response;
		}

		public function renderView($path = false, $data = []) {
			foreach ($data as $key => $value) $$key = $value;

			// setting up easier-to-access vars
			$context = $this->getContext();
			$session = $this->getSession();
			$request = $this->getRequest();
			$utils = new Utils();
			
			include ($this->getFullPath($path));
		}

		public function getViewContents($path, $data) {
			ob_start();
			$this->renderView($path, $data);
			$content = ob_get_clean();

			return $content;
		}

		public function renderError($error) {
			$this->render('error', [
				'success' => false,
				'errorCode' => $error->getCode(),
				'errorTrace' => substr(implode("<br />#", explode("#", $error->getTraceAsString())), 6),
				'errorMessage' => $error->getMessage()
			]);
		}

		public function getFullPath($path, $component = null) {
			$components = $this->getContext()->getComponents(true);
			$namespace = $this->getContext()->getNamespace();

			foreach ($components as $component) {
				$mPath = \APP_ROOT . "/{$namespace}/{$component->getPath()}/Resources/views/";
				$mPath = str_replace('//', '/', $mPath); // just in case

				if (Utils::endsWith($path, ".php")) $mPath = $mPath . $path;
				else $mPath= $mPath . $path . ".php";

				if (file_exists($mPath)) break;
			}

			if (!file_exists($mPath)) {
				throw new SebastianException("The route exists but the template doesn't.\n$path");
			}

			return $mPath;
		}

		public function generateUrl($routeName, $args = []) {
			// todo replace with getRoute()
			$route = $this->getContext()->getRouter()->getRoutes()[$routeName]['match'];
			foreach ($args as $key => $arg) {
				$match = preg_match("/{($key(?:\:[^\}]*)?)}/", $route);

				if ($match != 0) {
					$route = preg_replace("/{($key(?:\:[^\}]*)?)}/", $arg, $route);
					unset($args[$key]);
				}
			}

			if (count($args) > 0) $route .= "?" . http_build_query($args);

			return $route;
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

		public function setTitle($title) {
			$this->title = $title;
		}

		public function getTitle() {
			return $this->title;
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

		public function getCacheManager() {
			return $this->getContext()->getCacheManager();
		}

		public function getEntityManager() {
			return $this->getContext()->getEntityManager();
		}

		public function getConnection() {
			return $this->getContext()->getConnection();
		}

		public function getForm($name) {
			$components = $this->getContext()->getComponents();
			$namespace = $this->getContext()->getNamespace();

			foreach ($components as $component) {
				$mPath = \APP_ROOT . "/{$namespace}/{$component['path']}/Resources/form/";
				$mPath = str_replace('//', '/', $mPath); // just in case

				if (Utils::endsWith($name, ".yaml")) $mPath = $mPath . $name;
				else $mPath= $mPath . $name . ".yaml";

				if (file_exists($mPath)) {
					return Form::fromConfig(yaml_parse_file($mPath), $this->getContext());
				}
			}

			return new Form($name, $this->getContext());
		}
	}