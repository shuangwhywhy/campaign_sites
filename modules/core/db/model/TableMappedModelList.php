<?php

namespace modules\core\db\model;

use \modules\core\common\model\DataModel;

abstract class TableMappedModelList extends DataModel {

	public abstract function createModel ($initData=array());

	public function append ($model) {
		$this->data[] = $model;
	}

}
