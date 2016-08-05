<?php

namespace modules\core\db\model;

use \modules\core\common\model\DataModel;

abstract class TableMappedModel extends DataModel {

	public function __construct ($data=array()) {
		$this->setData($data);
	}

	public function setData ($data=array()) {
		foreach ($data as $name => $val) {
			$this->{$name} = $val;
		}
	}

	public static function getClass () {
		return __CLASS__;
	}

	public abstract function getTableName ();

	public abstract function getCandidateKeys ();

	public function getCandidateKeyValues () {
		$keys = $this->getCandidateKeys();
		$values = array();
		if (!empty($keys)) {
			foreach ($keys as $name => $key) {
				$value = array();
				if (is_array($key)) {
					foreach ($key as $k) {
						if ($this->offsetExists($k)) {
							$value[$k] = $this->{$k};
						}
					}
				} else {
					if ($this->offsetExists($key)) {
						$value[$key] = $this->{$key};
					}
				}
				if (!empty($value)) {
					$values[$name] = $value;
				}
			}
		}
		return $values;
	}

}
