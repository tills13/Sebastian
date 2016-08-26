<?php
    namespace Sebastian\Core\Event;

    class Event {
        const PRE_REQUEST = 'pre_request';
        const VIEW = 'view';
        const SHUTDOWN = 'shutdown';

        protected $shouldStopPropagation = false;

        public function stopPropagation() {
            $this->shouldStopPropagation = true;
        }

        public function shouldStopPropagation() {
            return $this->shouldStopPropagation;
        }
    }