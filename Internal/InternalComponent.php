<?php
    namespace Sebastian\Internal;

    use Sebastian\Core\Component\Component;
    use Sebastian\Core\Context\ContextInterface;
    use Sebastian\Utility\Configuration\Configuration;

    class InternalComponent extends Component {
        public function __construct(ContextInterface $context, $name, Configuration $config = null) {
            parent::__construct($context, $name, $config);

            $this->setWeight(0);
        }

        public function setup(Configuration $config = null) {
            $context = $this->getContext();

            if ($templating = $this->getContext()->get('templating')) {
                $context->templating->addMacro('sebastian', function() use ($templating) {
                    return $templating->render('javascript');
                });
            }
        }

        public function checkRequirements(ContextInterface $context) {
            return true;
        }
    }