<?php
    namespace Sebastian\Core\Event;

    class Event {
        const SHUTDOWN = 'shutdown';
        const VIEW = 'view';

        protected $shouldStopPropagation = false;

        public function stopPropagation() {
            $this->shouldStopPropagation = true;
        }

        public function shouldStopPropagation() {
            return $this->shouldStopPropagation;
        }
    }