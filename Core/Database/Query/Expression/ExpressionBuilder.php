<?php
    namespace Sebastian\Core\Database\Query\Expression;

    use Sebastian\Utility\Configuration\Configuration;

    class ExpressionBuilder {
        public function __construct() {}

        public function andExpr($left = null, $right = null) {
            return new Expression(Expression::TYPE_AND, $left, $right);
        }

        public function orExpr($left = null, $right = null) {
            return new Expression(Expression::TYPE_OR, $left, $right);
        }

        public function compare($left, $op, $right) {
            return new Expression($op, $left, $right);
        }

        public function eq($left, $right) {
            return $this->compare($left, Expression::EQUALS, $right);
        }

        public function neq($left, $right) {
            return $this->compare($left, Expression::NOT_EQUALS, $right);
        }

        public function lt($left, $right) {
            return $this->compare($left, Expression::LT, $right);
        }

        public function lte($left, $right) {
            return $this->compare($left, Expression::LTE, $right);
        }

        public function gt($left, $right) {
            return $this->compare($left, Expression::GT, $right);
        }

        public function gte($left, $right) {
            return $this->compare($left, Expression::GT, $right);
        }

        public function is($left, $right) {
            return $this->compare($left, Expression::IS, $right);
        }
    }