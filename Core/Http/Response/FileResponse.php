<?php 
    namespace Sebastian\Core\Http\Response;

    /**
     * FileResponse
     * 
     * @author Tyler <tyler@sbstn.ca>
     * @since  Oct. 2015
     */
    class FileResponse extends Response {
        public function send() {
            $this->sendHttpResponseCode();
            $this->sendHeaders();

            //$session = $this->
            require $this->content;
        }
    }