<?php
namespace Kir\MySQL\Builder\Traits;

trait UnionBuilder {
	use AbstractDB;

	/** @var array */
	private $unions = [];

	/**
	 * @param string $query
	 * @return $this
	 */
	public function union($query) {
		$this->unions[] = array('', $query);
		return $this;
	}

	/**
	 * @param string $query
	 * @return $this
	 */
	public function unionAll($query) {
		$this->unions[] = array('ALL', $query);
		return $this;
	}

	/**
	 * @param string $query
	 * @return string
	 */
	protected function buildUnions($query) {
		$wrap = function ($query) {
			$query = trim($query);
			$query = join("\n\t", explode("\n", $query));
			return sprintf("(\n\t%s\n)", $query);
		};
		$queries = [$wrap($query)];
		foreach($this->unions as $unionQuery) {
			if($unionQuery[0] === 'ALL') {
				$queries[] = 'UNION ALL';
			} else {
				$queries[] = 'UNION';
			}
			$queries[] = $wrap($unionQuery[1]);
		}
		if(count($queries) > 1) {
			return join(" ", $queries);
		}
		return $query;
	}
}
