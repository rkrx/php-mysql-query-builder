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
		$qaueries = [$query];
		foreach($this->unions as $unionQuery) {
			if($unionQuery[0] === 'ALL') {
				$qaueries[] = 'UNION ALL';
			} else {
				$qaueries[] = 'UNION';
			}
			$qaueries[] = $unionQuery[1];
		}
		return join("\n", $qaueries);
	}
}
