<?php
	namespace Sebastian\Core\Database\Query;

	use Sebastian\Utility\Collection\Collection;
	use Sebastian\Core\Database\Query\Part\Part;

	class DeleteQuery extends Query {
		public function __toString() {
			$query = "DELETE FROM {$this->fromsToString()} WHERE {$this->getWhere()}";

			return $query;
		}
	}