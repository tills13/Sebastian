<?php
    namespace Sebastian\Core\Database\Query\Expression;

    use Sebastian\Utility\Configuration\Configuration;

    class ExpressionFactory {
        protected $expression;

        public static function getFactory(Configuration $configuration = null) {
            return new ExpressionFactory($configuration);
        }

        public function __construct(Configuration $configuration = null) {
            $this->configuration = $configuration;
        }

        public function expr($elements = null) {
            if ($this->expression != null) {
                $expression = new Expression(Expression::TYPE_NONE);
                $this->expr($this->getExpression(true))->orExpr($expression);
            } else {
                $this->expression = new Expression(Expression::TYPE_NONE);
            }

            if ($elements != null) {
                if (is_array($elements)) $this->expression->putAll($elements);
                else $this->expression->put($elements);
            }

            return $this;
        }

        public function orExpr($elements) {
            if ($this->expression != null) {
                $this->orExpr($this->getExpression(true));//->orExpr($expression);

                $expression = new Expression();

                if ($elements != null) {
                    if (is_array($elements)) $expression->putAll($elements);
                    else $expression->put($elements);
                }

                $this->expression->put($expression);
            } else {
                $this->expr($elements);
            }

            return $this;
        }

        public function equals($expression) {
            $this->expression->setType(Expression::TYPE_EQUALS);
            $this->expression->put($expression);

            return $this;
        }

        public function is($expression) {
            $this->expression->setType(Expression::TYPE_IS);
            $this->expression->put($expression);

            return $this;
        }

        public function notEquals($expression) {
            $this->expression->setType(Expression::TYPE_NOT_EQUALS);
            $this->expression->put($expression);

            return $this;
        }

        public function andExpr($expression) {
            if ($this->expression == null) {
                $this->expression = new Expression(Expression::TYPE_AND);
            }

            // todo
            if (!$this->expression->is(Expression::TYPE_AND)) {
                $this->expression->setType(Expression::TYPE_AND);
            }

            $this->expression->put($expression);

            return $this;
        }

        public function greaterThan($expression) {
            if ($this->expression->getLeft() == null) {
                return $this->with($expression);
            }

            $this->expression->setSeparator(Expression::SEPARATOR_GT);
            $this->expression->setRight($expression);

            return $this;
        }

        public function lessThan($expression) {
            if ($this->expression->getLeft() == null) {
                return $this->with($expression);
            }

            $this->expression->setSeparator(Expression::SEPARATOR_LT);
            $this->expression->setRight($expression);

            return $this;
        }

        public function reset() {
            $this->expression = null;
            return $this;
        }

        public function getExpression($reset = false) {
            $expression = $this->expression;

            if ($reset) $this->reset();

            return $expression;
        }
    }