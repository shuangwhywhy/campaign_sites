<?php

namespace modules\core\db\helper;

use \modules\core\db\model\TableMappedModel;
use \modules\core\db\model\TableMappedModelList;
use \modules\core\db\model\Where;

class DB extends \modules\core\common\helper\DB {

	public static function findAllModels (TableMappedModelList &$list, Where $conditions=null, $orderBy=array(), $limit=array(), $conn=DEFAULT_DB_CONN) {
		$obj = $list->createModel();
		$sql = "SELECT * FROM `".self::getRealTableName($obj->getTableName(), $conn)."`";
		if (!is_null($conditions)) {
			$sql .= ' WHERE'.$conditions->toSql();
		}
		$result = self::fetchAll($sql, $conn);
		if (!empty($result)) {
			foreach ($result as $row) {
				$model = $list->createModel($row);
				$list->append($model);
			}
		}
		return $list;
	}

	public static function findModel (TableMappedModel &$model, Where $conditions=null, $conn=DEFAULT_DB_CONN) {
		$sql = "SELECT * FROM `".self::getRealTableName($model->getTableName(), $conn)."`";
		if (!is_null($conditions)) {
			$sql .= ' WHERE'.$conditions->toSql();
		} else {
			$key_vals = $model->getCandidateKeyValues();
			if (!empty($key_vals)) {
				$conds = array();
				foreach ($key_vals as $val) {
					if (is_array($val)) {
						$conds[] = new Where($val, Where::_AND);
					}
				}
				$cond = new Where($conds, Where::_OR);
				$sql .= ' WHERE'.$cond->toSql();
			} else {
				return false;
			}
		}
		$result = self::fetch($sql, $conn);
		if (!empty($result)) {
			$model->setData($result);
			return true;
		}
		return false;
	}

	public static function save (TableMappedModel &$model, $conn=DEFAULT_DB_CONN) {
		$result = self::findModel($model, null, $conn);
		if ($result) {
			self::update($model, $conn);
		} else {
			self::insert($model, $conn);
		}
	}

	public static function insert (TableMappedModel &$model, $conn=DEFAULT_DB_CONN) {
		$setValues = array();
		foreach ($model as $key => $val) {
			$setValues[] = '`'.self::escapeName($key).'` = '.self::quote($val);
		}
		$setValuesSql = implode(', ', $setValues);
		$result = DB::exec("INSERT IGNORE INTO `".self::getRealTableName($model->getTableName())."` SET ".$setValuesSql, $conn);
	}

	public static function insertAll (TableMappedModelList &$list, $conn) {

	}

	public static function update (TableMappedModel &$model, $conn=DEFAULT_DB_CONN) {
		$setValues = array();
		foreach ($model as $key => $val) {
			$setValues[] = '`'.self::escapeName($key).'` = '.self::quote($val);
		}
		$setValuesSql = implode(', ', $setValues);
		$sql = "UPDATE `".self::getRealTableName($model->getTableName())."` SET ".$setValuesSql;
		$key_vals = $model->getCandidateKeyValues();
		if (!empty($key_vals)) {
			$conds = array();
			foreach ($key_vals as $val) {
				if (is_array($val)) {
					$conds[] = new Where($val, Where::_AND);
				}
			}
			$cond = new Where($conds, Where::_OR);
			$sql .= ' WHERE'.$cond->toSql();
		}
		$result = DB::exec($sql, $conn);
	}

}
