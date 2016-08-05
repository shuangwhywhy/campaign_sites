<?php

namespace modules\core\common\model;

class RawModel {

	private $data;

	public function __construct ($data='') {
		$this->data = $data;
	}

	public function getData () {
		return $this->data;
	}

}
