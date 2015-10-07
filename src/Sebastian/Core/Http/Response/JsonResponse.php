<?php 
	namespace Sebastian\Core\Http\Response;

	/**
	 * JsonResponse
	 * 
	 * @author Tyler <tyler@sbstn.ca>
	 * @since  Oct. 2015
	 */
	class JsonResponse extends Response {
		public function send() {
			$this->sendHttpResponseCode();
			$this->sendHeaders();

			echo json_encode($this->content);
		}
	}