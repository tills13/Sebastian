<?php
	namespace Sebastian\Core\Http;

	use Sebastian\Core\Utility\Utils;
	
	/**
	 * Request
	 * 
	 * @author Tyler <tyler@sbstn.ca>
	 * @since  Oct. 2015
	 */
	class Request {
		protected $get;
		protected $post;
		protected $route;
		protected $method;
		protected $type;

		protected $attrs;

		const REQUEST_TYPE_DEFAULT = 0;
		const REQUEST_TYPE_JSON = 1;
		const REQUEST_TYPE_VIEW = 2;

		public static function fromGlobals() {
			$request = new Request($_GET, $_POST, $_COOKIE, $_SERVER, $_FILES);
			return $request;
		}

		protected function __construct($get = [], $post = [], $cookies = [], $server = [], $files = []) {
			$this->get = $get;
			$this->post = $post;
			$this->cookies = $cookies;
			$this->server = $server;
			$this->files = $files;
			$this->headers = array_filter($this->server, function($index) { 
				return (strpos($index, "HTTP_") === 0);
			})

			if (strstr($_SERVER['REQUEST_URI'], '?')) {
				$this->route = substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], "?"));
			} else $this->route = $_SERVER['REQUEST_URI'];

			$this->route = urldecode($this->route);
			$this->type = Request::REQUEST_TYPE_DEFAULT;
			$this->referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
			
			if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
				strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
				$this->type = Request::REQUEST_TYPE_JSON;
			}

			if ($this->query('view_only', false)) {
				$this->type = Request::REQUEST_TYPE_VIEW;
			}

			$this->setAttr('request_ip', $_SERVER['REMOTE_ADDR']);
			$this->setAttr('ua', $_SERVER['HTTP_USER_AGENT']);
		}

		public function setAttr($attr, $value, $override = false) {
			$this->attrs[$attr]  = $value;
		}

		public function getAttr($attr, $default) {
			return ($this->attrs[$attr] ?: $default);
		}

		// get and post
		// 1. get THEN 2. post
		public function get($keyword, $default = null) {
			if (in_array($keyword, array_keys($this->get))) return $this->get[$keyword];
			if (in_array($keyword, array_keys($this->post))) return $this->post[$keyword];
			return $default;
		}

		public function remove($keyword) {
			if (in_array($keyword, array_keys($this->post))) unset($this->post[$keyword]);
			elseif (in_array($keyword, array_keys($this->get))) unset($this->get[$keyword]);
		}

		public function route() {
			return $this->route;
		}

		public function method($is = null) {
			if (!is_null($is)) return (strtolower($this->method()) == strtolower($method));

			return $this->server['REQUEST_METHOD'];
		}

		public function has($keyword) {
			return in_array($keyword, array_keys($this->get)) || in_array($keyword, array_keys($this->post));
		}

		public function isXmlHttpRequest() {
			return ($_SERVER['HTTP_X_REQUESTED_WITH'] === 'xmlhttprequest');
		}

		public function isMobile() {
			return Utils::requestIsMobile();
		}

		public function params() {
			return array_merge($this->post, $this->get);
		}



		// GETTERS
		public function getType() {
			return $this->type;
		}

		public function setType($type) {
			$this->type = $type;
			return $this;
		}

		public function getReferrer() {
			return $this->referrer;
		}


		public function __toString() {
			return "{$this->route}";
		}
	}