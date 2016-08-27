<?php
    namespace Sebastian\Internal;

    use Sebastian\Core\Component\Component;
    use Sebastian\Core\Context\ContextInterface;
    use Sebastian\Utility\Configuration\Configuration;

    class InternalComponent extends Component {
        public function __construct(ContextInterface $context, $name, Configuration $config = null) {
            parent::__construct($context, $name, $config);

            $this->setWeight(-999);
        }

        public function setup(Configuration $config = null) {
            $context = $this->getContext();

            if (($templating = $this->getContext()->get('templating')) !== null) {
                $templating->addMacro('sebastian', function() use ($templating) {
                    return $templating->render('javascript');
                });

                $templating->addMacro('debugToolbar', function() use ($templating) {
                    return $templating->render('debug_toolbar');
                });
            }
        }
    }