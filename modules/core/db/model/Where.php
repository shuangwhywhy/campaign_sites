<?php

namespace modules\core\db\model;

use \modules\core\db\helper\DB;

class Where {

	const _AND = 'AND';
	const _OR = 'OR';
	const _NOT = 'NOT';
	const _GT = '>';
	const _GTEQ = '>=';
	const _LT = '<';
	const _LTEQ = '<=';
	const _EQ = '=';
	const _IN = 'IN';
	const _LIKE = 'LIKE';

	public $logicOp;

	public $negative = false;

	public $data = array();

	public function __construct ($where=array(), $logicOp=self::_EQ) {
		if (is_array($where) && !empty($where)) {
			if ($logicOp == self::_NOT) {
				$this->logicOp = self::_EQ;
			} else {
				$this->logicOp = $logicOp;
			}
			foreach ($where as $name => $val) {
				$this->data[$name] = $val;
			}
		} else if ($where instanceof Where) {
			$this->logicOp = $where->logicOp;
			$this->data = $where->data;
			$this->negative = $where->negative;
		}
		if ($logicOp == self::_NOT) {
			$this->negative = true;
		}
	}

	public function toSql () {
		if (!empty($this->data)) {
			switch ($this->logicOp) {
				case self::_EQ:
					$col = key($this->data);
					$val = current($this->data);
					$op = '=';
					if (is_array($val)) {
						$this->logicOp = self::_IN;
						return $this->toSql();
					} else if (is_null($val)) {
						if ($this->negative) {
							$op = 'IS NOT';
						} else {
							$op = 'IS';
						}
					} else if ($this->negative) {
						$op = '!=';
					}
					return ' (`'.DB::escapeName($col).'` '.$op.' '.DB::quote($val).') ';

				case self::_GT:
					$op = $this->logicOp;
					if ($this->negative) {
						$op = self::_LTEQ;
					}
					$col = key($this->data);
					$val = current($this->data);
					return ' (`'.DB::escapeName($col).'` '.$op.' '.DB::quote($val).') ';

				case self::_GTEQ:
					$op = $this->logicOp;
					if ($this->negative) {
						$op = self::_LT;
					}
					$col = key($this->data);
					$val = current($this->data);
					return ' (`'.DB::escapeName($col).'` '.$op.' '.DB::quote($val).') ';

				case self::_LT:
					$op = $this->logicOp;
					if ($this->negative) {
						$op = self::_GTEQ;
					}
					$col = key($this->data);
					$val = current($this->data);
					return ' (`'.DB::escapeName($col).'` '.$op.' '.DB::quote($val).') ';

				case self::_LTEQ:
					$op = $this->logicOp;
					if ($this->negative) {
						$op = self::_GT;
					}
					$col = key($this->data);
					$val = current($this->data);
					return ' (`'.DB::escapeName($col).'` '.$op.' '.DB::quote($val).') ';

				case self::_LIKE:
					$if_not = '';
					if ($this->negative) {
						$if_not = 'NOT ';
					}
					$col = key($this->data);
					$val = current($this->data);
					return ' (`'.DB::escapeName($col).'` '.$if_not.$this->logicOp.' '.DB::quote($val).') ';

				case self::_IN:
					$if_not = '';
					if ($this->negative) {
						$if_not = 'NOT ';
					}
					$col = key($this->data);
					$val = current($this->data);
					if (is_array($val)) {
						foreach ($val as $key => $v) {
							$val[$key] = DB::quote($v);
						}
					}
					return ' ('.DB::escapeName($col).'` '.$if_not.$this->logicOp.' ('.implode(',', $val).')) ';

				case self::_AND:
				case self::_OR:
					$subWhere = array();
					foreach ($this->data as $key => $val) {
						if ($val instanceof \modules\core\db\model\Where) {
							$subWhere[] = $val->toSql();
						} else {
							$where = new Where(array($key=>$val));
							$subWhere[] = $where->toSql();
						}
					}
					return ' ('.implode($this->logicOp, $subWhere).') ';

				default:
					return '';
			}
		} else {
			return '';
		}
	}

}
