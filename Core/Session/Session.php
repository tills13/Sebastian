<?php
    namespace Sebastian\Core\Session;

    use Sebastian\Core\Model\UserInterface;
    
    /**
     * Session
     *
     * wrapper for $_SESSION
     *  
     * @author Tyler <tyler@sbstn.ca>
     * @since Oct. 2015
     */
    class Session {
        protected $cookies;

        public static function fromGlobals() {
            $session = new Session();
            return $session;
        }

        protected function __construct() {
            $this->start();
            
            $token = isset($_COOKIE['token']) ? $_COOKIE['token'] : null;
            
            $this->set('session_token', $token);
            $this->set('session_user', $_SESSION['user'] ?? null); // needed?

            if (!$token && !is_null($this->get('session_user', null))) {
                $user = $this->get('session_user');
                $expire = time() + 60 * 60 * 24 * 5; // five days
                //setcookie('token', $user->getToken(), $expire);
            }

            //$this->set('session_user', 'tullsy');
        }

        public function start() {
            session_start();
        }

        public function reload() {
            //$token = isset($_COOKIE['token']) ? $_COOKIE['token'] : null;
            $user = $this->get('session_user', null);
            
            //if ($user && (!$token || $token != $user->getToken())) {
                //$token = $user->getToken();
                //$this->setCookie('token', $token);
            //}

            //$this->set('session_token', $token);
        }

        public function destroy() {
            session_destroy();
        }

        public function clear($field = null) {
            if (!$field) $this->destroy();
            else unset($_SESSION[$field]);
        }

        public function set($field, $value, $override = false) {
            if (!$override && isset($_SESSION[$field])) return;

            $_SESSION[$field] = $value;

            return $this;
        }

        public function get($field, $default = null) {
            return isset($_SESSION[$field]) ? $_SESSION[$field] : $default;
        }

        public function has($attr) {
            return in_array($attr, $_SESSION);
        } 

        public function is($attr, $default = false) {
            return isset($_SESSION[$attr]) ? $_SESSION[$attr] === true : $default;
        }

        public function check() {
            //var_dump($_SESSION['session_user']);
            return ($_SESSION['session_user'] != null);
        }

        public function setCookie($field, $value, $expires = null) {
            if ($expires == null) $expires = time() + 60 * 60 * 24;
            setcookie($field, $value, $expires);
        }

        public function setUser(UserInterface $user) {
            $this->set('session_user', $user);
            return $this;
        }

        // GETTERS ====
        
        public function getCookie($field, $default = null) {
            return isset($_COOKIE[$field]) ? $_COOKIE[$field] : $default;
        }

        public function getToken() {
            return $this->get('session_token');
        }

        public function getUser() {
            return $this->get('session_user');
        }

        public function getNotices($destroy = true) {
            $notices = $this->get('session_notices', []);
            if ($destroy) $this->clear('session_notices');

            return $notices;
        }
    }