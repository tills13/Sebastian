<?php
    namespace Sebastian\Core\Event;

    use Sebastian\Core\Http\Request;

    class ViewEvent extends Event {
        protected $request;
        protected $response;

        public function __construct(Request $request, $response) {
            $this->request = $request;
            $this->response = $response;
        }

        public function getRequest() {
            return $this->request;
        }

        public function setResponse($response) {
            $this->response = $response;
        }

        public function getResponse() {
            return $this->response;
        }
    }