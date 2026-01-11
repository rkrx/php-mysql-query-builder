<?php

namespace Kir\MySQL\Builder;

use Stringable;

interface RunnableTemporaryTable extends Stringable {
	/**
	 * @return string
	 */
	public function getName(): string;

	/**
	 * @return $this
	 */
	public function release();
}
