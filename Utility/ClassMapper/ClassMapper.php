<?php
    namespace Sebastian\Utility\ClassMapper;

    use \Exception;
    use Sebastian\Core\Context\ContextInterface;

    class ClassMapper {
        protected static $instance;

        protected $context;
        protected $components;

        private function __construct(ContextInterface $context) {
            $this->context = $context;
            $this->components = $context->getComponents() ?? [];
        }

        public static function getInstance(ContextInterface $context = null) {
            if (!self::$instance && $context !== null) {
                self::$instance = new ClassMapper($context);
            } else if (!self::$instance) {
                throw new Exception("ClassMapper has not been initialized properly");
            }

            return self::$instance;
        }

        /*
         * Map Component:Extra:Class -> full Namespace path
         */
        public static function parse(string $classString, $extra = null) : string {
            $instance = ClassMapper::getInstance();

            if (strstr($classString, ':')) {
                if (substr_count($classString, ':') == 1) list($component, $class) = explode(':', $classString);
                else if (substr_count($classString, ':') == 2) list($component, $extra, $class) = explode(':', $classString);
                else {
                    throw new Exception("Invalid short class: {$class}."); 
                }

                if (($component = $instance->getComponent($component)) !== null) {
                    if ($extra) $class = implode('\\', [$extra, $class]);
                    $class = $component->getClass($class);

                    return $class;
                } else {
                    throw new Exception("Class {$classString} does not exist.");
                }
            } else {
                foreach ($instance->getComponents() as $component) {
                    /** @todo **/
                }
            }

            die($classString);

            return null;
        }

        public function getComponent(string $component) {
            return $this->getComponents()[$component] ?? null;
        }

        public function getComponents() {
            return $this->getContext()->getComponents();
        }

        public function getContext() {
            return $this->context;
        }
    }