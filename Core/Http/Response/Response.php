<?php
    namespace Sebastian\Core\Http\Response;

    use Sebastian\Utility\Collection\Collection;

    /**
     * Response
     * 
     * @author Tyler <tyler@sbstn.ca>
     * @since  Oct. 2015
     */
    class Response {
        const HTTP_OK = 200;
        const HTTP_MOVED_PERMANENTLY = 301;
        const HTTP_FOUND = 302;
        const HTTP_BAD_REQUEST = 400;
        const HTTP_FORBIDDEN = 403;
        const HTTP_NOT_FOUND = 404;
        const HTTP_TEAPOT = 418;
        const HTTP_INTERNAL_SERVER_ERROR = 500;

        protected $headers;
        protected $content;
        protected $responseCode;

        public function __construct($content = null, $responseCode = null, $headers = []) {
            $this->headers = new Collection($headers);
            
            $this->content = $content;
            $this->responseCode = $responseCode;
        }

        public function send() {
            $this->sendHttpResponseCode();
            $this->sendHeaders();

            echo $this->content;
        }

        public function sendHttpResponseCode() {
            http_response_code($this->responseCode);
        }

        public function sendHeaders() {
            foreach ($this->headers as $field => $value) {
                header("{$field}: {$value}");
            }
        }

        public function setResponseCode($responseCode) {
            $this->responseCode = $responseCode;
            return $this;
        }

        public function setContent($content) {
            $this->content = $content;
            return $this;
        }

        public function setHeader($field, $value, $applyNow = false) {
            if (!$applyNow) {
                $this->headers->set($field, $value);
            } else {
                header("{$field}: {$value}");
            }

            return $this;
        }
    }