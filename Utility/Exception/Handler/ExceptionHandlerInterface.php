<?php
	namespace Sebastian\Utility\Exception\Handler;

	interface ExceptionHandlerInterface {
		public function onException(\Exception $e);
	}