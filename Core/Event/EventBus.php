<?php
    namespace Sebastian\Core\Event;

    class EventBus {
        protected static $events = [];

        public static function register($eventName, Callable $callable, $priority = 0) {
            if (!isset(self::$events[$eventName])) {
                self::$events[$eventName] = [];
            }

            self::$events[$eventName][] = $callable;
        }

        public static function trigger($eventName, ... $params) {
            foreach (self::$events[$eventName] ?? [] as $handler) {
                call_user_func_array($handler, $params);
            }
        }
    }