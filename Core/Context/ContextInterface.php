<?php
	namespace Sebastian\Core\Context;

	interface ContextInterface {
		public function __call($method, $arguments);
		//public function __get();
		//public function __set();
	}