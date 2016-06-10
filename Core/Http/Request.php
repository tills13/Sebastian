<?php
    namespace Sebastian\Core\Http;

    use Sebastian\Utility\Collection\Collection;
    use Sebastian\Core\Session\Session;
    
    /**
     * Request
     * 
     * @author Tyler <tyler@sbstn.ca>
     * @since  Oct. 2015
     */
    class Request {
        const REQUEST_TYPE_DEFAULT = 0;
        const REQUEST_TYPE_JSON = 1;
        const REQUEST_TYPE_VIEW = 2;

        const METHOD_GET = "GET";
        const METHOD_POST = "POST";

        public $body;
        public $get;
        public $post;

        protected $route;
        protected $method;
        protected $type;

        protected $attrs;
        protected $session;

        public static function fromGlobals() {
            $request = new Request($_GET, $_POST, $_COOKIE, $_SERVER, $_FILES);
            return $request;
        }

        protected function __construct($get = [], $post = [], $cookies = [], $server = [], $files = []) {
            $this->body = file_get_contents('php://input');
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
            $this->session = Session::fromGlobals();
            
            if ($this->server->has('HTTP_X_REQUESTED_WITH') &&
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                //$this->type = Request::REQUEST_TYPE_JSON;
            }

            if ($this->get('view_only', false)) {
                $this->type = Request::REQUEST_TYPE_VIEW;
            }

            $this->processBody();
        }

        public function get($keyword, $default = null) {
            return $this->get->get($keyword, $this->post->get($keyword, $default));
        }

        protected function processBody() {
            try {
                if (strstr($this->headers->get('HTTP_CONTENT_TYPE'), 'application/json')) {
                    $this->body = new Collection(json_decode($this->body, true));
                }
            } catch (\Exception $e) {
                $this->body = [];
            }
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
            return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'xmlhttprequest');
        }

        public function isMobile() {
            $useragent = $this->server->get('USER_AGENT');

            return (
                preg_match(
                    '/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm(os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i', 
                    $useragent
                ) || preg_match(
                    '/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp(i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac(|\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt(|\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg(g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',
                    substr($useragent, 0, 4)
                )
            );
        }

        public function params() {
            return array_merge($this->post, $this->get);
        }

        public function getSession() {
            return $this->session;
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