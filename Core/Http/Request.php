<?php
	namespace Sebastian\Core\Http;

	use Sebastian\Component\Collection\Collection;
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
			$this->get = new Collection($get);
			$this->post = new Collection($post);
			$this->cookies = new Collection($cookies);
			$this->server = new Collection($server);
			$this->files = new Collection($files);
			$this->headers = $this->server->filter(function($value, $key) { 
				return (strpos($key, "HTTP_") === 0);
			});

			if (strstr($_SERVER['REQUEST_URI'], '?')) {
				$this->route = substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], "?"));
			} else $this->route = $_SERVER['REQUEST_URI'];

			$this->route = urldecode($this->route);
			$this->type = Request::REQUEST_TYPE_DEFAULT;
			$this->referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
			
			if ($this->server->has('HTTP_X_REQUESTED_WITH') &&
				strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
				$this->type = Request::REQUEST_TYPE_JSON;
			}

			if ($this->get('view_only', false)) {
				$this->type = Request::REQUEST_TYPE_VIEW;
			}
		}

		public function get($keyword, $default = null) {
			return $this->get->get($keyword, $this->post->get($keyword, $default));
		}

		/**
		 * [remove description]
		 * @param  [type] $keyword [description]
		 * @return [type]          [description]
		 *
		 * @todo  implement
		 */
		public function remove($keyword) {
			//if (in_array($keyword, array_keys($this->post))) unset($this->post[$keyword]);
			//elseif (in_array($keyword, array_keys($this->get))) unset($this->get[$keyword]);
		}

		public function route() {
			return $this->route;
		}

		public function method($is = null) {
			if (!is_null($is)) return (strtolower($this->method()) == strtolower($is));

			return $this->server['REQUEST_METHOD'];
		}

		public function has($keyword) {
			return ($this->get->has($keyword) || $this->post->has($keyword)); 
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