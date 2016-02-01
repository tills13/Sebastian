<?php
	namespace Sebastian\Component\Exception\Handler;

	interface ExceptionHandlerInterface {
		public function onException(\Exception $e);
	}