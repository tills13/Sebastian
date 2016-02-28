<?php
	namespace Sebastian\Core\Repository\Transformer;

	class PointTransformer extends BaseTransformer {
		protected $name; 

		public function __construct() {
			$this->setName('point');
		}

		public function transform($value) {
			if (preg_match('\((\d(?:.\d+)?), ?(\d(?:.\d+)?)\)?', $value, $matches) == 0) {
				//throw new TransformException();
			}

			return [$matches[0], $matches[1]];
		}

		public function reverseTransform($value) {
			//if (!is_array($value)) throw new TransformException();
			return "({$value[0]},{$value[1]})"
		}
	}