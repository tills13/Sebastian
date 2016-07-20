<?php 
    namespace Sebastian\Core\Http\Response;

    /**
     * RedirectResponse
     * 
     * @author Tyler <tyler@sbstn.ca>
     * @since  Oct. 2015
     */
    class RedirectResponse extends Response {
        public function __construct($content = null, $statusCode = Response::HTTP_FOUND) {
            parent::__construct($content, $statusCode);

            $this->setHeader('Location', $content);
        }

        public function send() {
            $this->sendHttpResponseCode();
            $this->sendHeaders();
        }
    }