<?php
	namespace Sebastian\Core\Controller;

	use Sebastian\Core\Context\Context;

	use Sebastian\Core\Http\Request;
	use Sebastian\Core\Http\Response\Response;

	use Sebastian\Core\Form\Form;

	use Sebastian\Core\Utility\Utils;
	use Sebastian\Core\Exception\SebastianException;
	
	/**
	 * Controller
	 * 
	 * @author Tyler <tyler@sbstn.ca>
	 * @since  Oct. 2015
	 */
	class Controller extends Context {
		protected $headers;

		protected $jsFiles;
		protected $jsScripts;
		protected $cssFiles;

		protected $title;
		protected $subtitle;
		protected $showBannerBar;

		// paging
		protected $resultsPerPage;
		protected $totalResults;
		protected $page;
		protected $pagerUrl;
		protected $pagerAttributes;

		protected $tabs;

		public function __construct($context) {
			parent::__construct($context);

			$this->masterLayout = "master";
			$this->headers = [];
			$this->cssFiles = [];
			$this->jsFiles = [];

			$this->title = null;
			$this->subtitle = null;
			$this->showBannerBar = false;

			$this->resultsPerPage = 10;
			$this->totalResults = 0;
			$this->page = 1;
			$this->pagerUrl = null;
			$this->pagerAttributes = [];

			$this->tabs = [];
		}

		private function _startRender() {
			ob_start();
		}

		private function _stopRender() {
			$content = ob_get_clean();
			return $content;
		}

		public function render($path = false, $data = []) {
			ob_start();
			foreach ($this->headers as $header) {
				header("{$header['field']}: {$header['value']}", $header['replace']);
			}
			
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
			//$this->_startRender();
			foreach ($data as $key => $value) $$key = $value;

			// setting up easier-to-access vars
			$context = $this->getContext();
			$session = $this->getSession();
			$request = $this->getRequest();
			$subtitle = $this->subtitle;
			$utils = new Utils();

			$url = '$this->generateUrl';
			
			include ($this->getFullPath($path));
			//return $this->_stopRender();
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
			$namespace = $this->getContext()->getAppNamespace();
			foreach ($components as $component) {
				$mPath = \APP_ROOT . "/{$namespace}/{$component['path']}/Resources/views/";
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
			$paged = Utils::endsWith($routeName, '.paged');
			if ($paged) {
				$routeName = substr($routeName, 0, strpos($routeName, '.paged'));
			}

			// todo replace with getRoute()
			$route = $this->getContext()->getRouter()->getRoutes()[$routeName]['match'];
			foreach ($args as $key => $arg) {
				$match = preg_match("/{($key(?:\:[^\}]*)?)}/", $route);

				if ($match != 0) {
					$route = preg_replace("/{($key(?:\:[^\}]*)?)}/", $arg, $route);
					unset($args[$key]);
				}
			}

			if ($paged) {
				$route .= "/p{$args['page']}";
				unset($args['page']);
			}

			if (count($args) > 0) {
				$route .= "?" . http_build_query($args);
				/*$args = implode('&', array_map(function($key,$val) { 
					return "{$key}={$val}"; 
				}, array_keys($args), array_values($args)));

				$route .= "?{$args}";*/
			}

			return $route;
		}

		// override
		public function generateTabs() {}

		// not implemented
		public function forward($route, $params) {
			return null;
		}

		public function redirect($url, $https = false, $code = 302) {
			header("Location: " . $url);
			exit();
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

		public function getTitle() {
			return $this->title;
		}

		public function getSubtitle() {
			return $this->subtitle;
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
			$namespace = $this->getContext()->getAppNamespace();
			foreach ($components as $component) {
				$mPath = \APP_ROOT . "/{$namespace}/{$component['path']}/Resources/form/";
				$mPath = str_replace('//', '/', $mPath); // just in case

				if (Utils::endsWith($name, ".yaml")) $mPath = $mPath . $name;
				else $mPath= $mPath . $name . ".yaml";

				if (file_exists($mPath)) return Form::fromConfig(yaml_parse_file($mPath), $this->getContext());
			}

			return new Form($name, $this->getContext());
		}

		public function getResultsPerPage() {
			return $this->resultsPerPage;
		}

		public function getTotalResults() {
			return $this->totalResults;
		}

		public function getPagerUrl() {
			return $this->pagerUrl;
		}

		public function getPagerAttributes() {
			return $this->pagerAttributes;
		}

		public function showBanner() {
			return $this->showBannerBar;
		}

		public function getPage() {
			return $this->page;
		}

		public function getTotalPages() {
			return ceil($this->totalResults / $this->resultsPerPage);
		}

		public function getOffset() {
			return ($this->page - 1) * $this->resultsPerPage;
		}

		public function getTabs() {
			return $this->tabs ?: [];
		}

		// SETTERS =====

		public function setHeader($field, $value, $applyNow = false) {
			if (!$applyNow) {
				$this->headers[$field] = [
					'field' => $field,
					'value' => $value,
					'replace' => false
				];
			} else {
				header("{$field}: {$value}");
			}
		}
		
		public function setTitle($title) {
			$this->title = $title;
		}

		public function setSubtitle($subtitle) {
			$this->subtitle = $subtitle;
		}

		public function showBannerBar($use) {
			$this->showBannerBar = $use;
		}

		public function setPage($page) {
			$this->page = $page;
		}

		public function setResultsPerPage($results) {
			$this->resultsPerPage = $results;
		}

		public function setTotalResults($totalResults) {
			$this->totalResults = $totalResults;
		}

		public function setPagerUrl($pagerUrl) {
			$this->pagerUrl = $pagerUrl;
		}

		public function setPagerAttributes($pagerAttributes) {
			$this->pagerAttributes = $pagerAttributes;
		}

		public function setActiveTab($activeTab) {
			$this->tabs[$activeTab]['active'] = true;
		}

		public function setTabs($tabs) {
			$this->tabs = $tabs;
		}

		public function hideTabIf($tabId, $condition) {
			if ($condition) $this->tabs[$tabId]['show'] = false;
		}

		public function generatePageTabs($active) {
			$pagesOnEitherSide = 2;
			
			$pages = [
				[
					'text' => '',
					'classes' => 'fa fa-angle-double-left page',
					'url' => $this->generateUrl($this->getPagerUrl(), array_merge(['page' => 1], $this->getPagerAttributes()))
				],[
					'text' => '',
					'classes' => 'fa fa-angle-left page',
					'url' => $this->generateUrl($this->getPagerUrl(), array_merge(['page' => max($this->getPage() - 1, 1)], $this->getPagerAttributes()))
				]
			];

			if (max($this->getPage() - $pagesOnEitherSide, 1) != 1) {
				$pages[] = [
					'text' => '...',
					'classes' => 'fa page',
					'url' => 'javascript:;'
				];
			}

			for ($i = max($this->getPage() - $pagesOnEitherSide, 1); $i <= min($this->getTotalPages(), $this->getPage() + $pagesOnEitherSide); $i++) {
				$pages["page-$i"] = [
					'text' => $i,
					'classes' => 'fa page',
					'url' => $this->generateUrl($this->getPagerUrl(), array_merge(['page' => $i], $this->getPagerAttributes()))
				];
			}

			if (min($this->getTotalPages(), $this->getPage() + $pagesOnEitherSide) != $this->getTotalPages()) {
				$pages[] = [
					'text' => '...',
					'classes' => 'fa page',
					'url' => 'javascript:;'
				];
			}

			$pages[] = [
					'text' => '',
					'classes' => 'fa fa-angle-right page',
					'url' => $this->generateUrl($this->getPagerUrl(), array_merge(['page' => min($this->getPage() + 1, $this->getTotalPages())], $this->getPagerAttributes()))
			];

			$pages[] = [
					'text' => '',
					'classes' => 'fa fa-angle-double-right page',
					'url' => $this->generateUrl($this->getPagerUrl(), array_merge(['page' => $this->getTotalPages()], $this->getPagerAttributes()))	
			];

			$pages["page-$active"]['active'] = true;
			
			return $pages;
		}
	}