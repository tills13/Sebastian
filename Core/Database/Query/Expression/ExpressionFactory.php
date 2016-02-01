<?php
	namespace Sebastian\Core\Database\Query\Expression;

	use Sebastian\Component\Configuration\Configuration;

	class ExpressionFactory {
		protected $expression;

		public static function getFactory(Configuration $configuration = null) {
			return new ExpressionFactory($configuration);
		}

		public function __construct(Configuration $configuration = null) {
			$this->configuration = $configuration;
			$this->expression = new Expression();
		}

		public function with($expression) {
			if ($this->expression->getLeft() != null) throw new \Exception('with() can only be called on empty expressions');
			$this->expression->setLeft($expression);

			return $this;
		}

		public function equals($expression) {
			$this->expression->setSeparator(Expression::SEPARATOR_EQUALS);
			$this->expression->setRight($expression);

			return $this;
		}

		public function andExpr($expression) {
			$this->expression->setSeparator(Expression::SEPARATOR_AND);
			$this->expression->setRight($expression);

			return $this;
		}

		public function orExpr($expression) {
			$this->expression->setSeparator(Expression::SEPARATOR_OR);
			$this->expression->setRight($expression);

			return $this;
		}

		public function getExpression() {
			return $this->expression;
		}
	}