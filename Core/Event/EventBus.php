<?php
    namespace Sebastian\Core\Event;

    use Sebastian\Core\DependencyInjection\Injector;

    class EventBus {
        protected static $events = [];

        public static function register($eventName, Callable $callable, $priority = 0) {
            if (!isset(self::$events[$eventName])) {
                self::$events[$eventName] = [];
            }

            self::$events[$eventName][] = $callable;
        }

        public static function trigger(string $name, Event $event = null, ... $params) {
            if (!$event) $event = new Event();

            foreach (self::$events[$name] ?? [] as $handler) {
                if ($event->shouldStopPropagation()) break;

                $arguments = Injector::resolveCallable($handler, array_merge(['@event' => $event], $params));
                call_user_func_array($handler, $arguments);
            }
        }
    }