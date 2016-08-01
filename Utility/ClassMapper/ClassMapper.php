<?php
    namespace Sebastian\Utility\ClassMapper;

    use \Exception;

    class ClassMapper {
        protected static $components;

        public static function init(array $components) {
            ClassMapper::$components = $components;
        }

        public static function parse($class, $type = null) : string {
            if (strstr($class, ':')) {
                list($component, $class) = explode(':', $class);

                if (!isset(self::$components[$component])) {
                    throw new Exception("Class {$class} does not exist.");
                }

                $component = self::$components[$component];
                $class = $component->getClass($class);

                return $class;
            } else {
                foreach (ClassMapper::$components as $component) {
                    /** @todo **/   
                }
            }

            return null;
        }
    }