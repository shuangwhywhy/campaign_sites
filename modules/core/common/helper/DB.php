<?php

namespace modules\core\common\helper;

class DB {

	protected static $conn = array();

	protected static $logConn = CORE_DB_CONN;

	public static function connect ($environment='dev', $rights_level='normal') {
		$configs = include APP_DIR.DS.'config'.DS.$environment.DS.'db.conf.php';

		if (!empty($configs)) {
			foreach ($configs as $name => $dbConfig) {
				if (empty($dbConfig['min_rights_level'])) {
					$dbConfig['min_rights_level'] = 'normal';
				}
				if (!isset($dbConfig['table_prefix'])) {
					$dbConfig['table_prefix'] = '';
				}
				if (!isset($dbConfig['table_suffix'])) {
					$dbConfig['table_suffix'] = '';
				}
				if ($rights_level != 'admin' && $dbConfig['min_rights_level'] == 'admin') {
					continue;
				}
				try {
					$dsn = 'mysql:'.(empty($dbConfig['dbname']) ? '' : ('dbname='.$dbConfig['dbname'].';')).'host='.$dbConfig['host'].';charset=utf8';
					$pdo = new \PDO(
						$dsn,
						$dbConfig['username'],
						$dbConfig['password'],
						array(
							\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
						)
					);
					self::execLogless('SET NAMES utf8', $pdo);
					self::$conn[$name] = array('pdo' => $pdo, 'dsn' => $dsn, 'table_prefix' => $dbConfig['table_prefix'], 'table_suffix' => $dbConfig['table_suffix']);
				} catch (\PDOException $exp) {
					Util::pr('DB connection failed');
					if (DEBUG_MODE) {
						Util::pr($exp->getMessage());
					}
					die();
				}
			}
		}
	}

	public static function getConnection ($conn=DEFAULT_DB_CONN) {
		if (is_array($conn)) {
			return $conn['pdo'];
		} else if ($conn instanceof \PDO) {
			return $conn;
		} else if ((is_string($conn) || is_int($conn)) && isset(self::$conn[$conn])) {
			return self::$conn[$conn]['pdo'];
		} else {
			die('Unknown db connection '.var_export($conn, true));
		}
	}

	public static function getDSN ($conn=DEFAULT_DB_CONN) {
		if (is_array($conn)) {
			return $conn['dsn'];
		} else if (is_string($conn)) {
			if (preg_match('/^mysql:/', $conn)) {
				return $conn;
			} else if (isset(self::$conn[$conn])) {
				return self::$conn[$conn]['dsn'];
			} else {
				return 'unknown';
			}
		} else if (is_int($conn) && isset(self::$conn[$conn])) {
			return self::$conn[$conn]['dsn'];
		} else {
			return 'unknown';
		}
	}

	public static function getTablePrefix ($conn=DEFAULT_DB_CONN) {
		if (is_array($conn)) {
			return $conn['table_prefix'];
		} else if ((is_string($conn) || is_int($conn)) && isset(self::$conn[$conn])) {
			return self::$conn[$conn]['table_prefix'];
		} else {
			return '';
		}
	}

	public static function getTableSuffix ($conn=DEFAULT_DB_CONN) {
		if (is_array($conn)) {
			return $conn['table_suffix'];
		} else if ((is_string($conn) || is_int($conn)) && isset(self::$conn[$conn])) {
			return self::$conn[$conn]['table_suffix'];
		} else {
			return '';
		}
	}

	public static function execLogless ($sql, $conn=DEFAULT_DB_CONN) {
		$pdo = self::getConnection($conn);
		return $pdo->exec($sql);
	}

	private static function logDBOp ($dsn, $op, $sql, $result) {
		if (defined(LOG_DB_OP) && LOG_DB_OP) {
			self::execLogless(
				"INSERT INTO `db_log`
					(`ip`, `ua`, `dsn`, `ctrl`, `action`, `url`, `op`, `sql`, `result`, `time`)
				VALUES
					(
						".self::quote($_SERVER['REMOTE_ADDR']).",
						".self::quote($_SERVER['HTTP_USER_AGENT']).",
						".self::quote($dsn).",
						".self::quote(Util::getCtrlName()).",
						".self::quote(Util::getActionName()).",
						".self::quote(Util::getCurrentURL()).",
						".self::quote($op).",
						".self::quote($sql).",
						".self::quote(print_r($result, true)).",
						".time()."
					)"
			, self::$logConn);
		}
	}

	public static function exec ($sql, $conn=DEFAULT_DB_CONN) {
		$pdo = self::getConnection($conn);
		$result = $pdo->exec($sql);
		self::logDBOp(self::getDSN($conn), 'exec', $sql, $result);
		return $result;
	}

	public static function fetch ($sql, $conn=DEFAULT_DB_CONN) {
		$pdo = self::getConnection($conn);
		try {
			$statement = $pdo->query($sql);
			if (!$statement) {
				self::logDBOp(self::getDSN($conn), 'fetch', $sql, false);
				if (DEBUG_MODE) {
					Util::prt($sql);
				} else {
					die('Invalid db Query');
				}
			}
			$result = $statement->fetch(\PDO::FETCH_ASSOC);

			self::logDBOp(self::getDSN($conn), 'fetch', $sql, $result);
			$statement->closeCursor();
		} catch (\PDOException $e) {
			self::logDBOp(self::getDSN($conn), 'fetch', $sql, false);
			if (DEBUG_MODE) {
				Util::prt($e->getMessage());
			} else {
				die('db error');
			}
		}
		return $result;
	}

	public static function fetchAll ($sql, $conn=DEFAULT_DB_CONN) {
		$pdo = self::getConnection($conn);
		try {
			$statement = $pdo->query($sql);
			if (!$statement) {
				self::logDBOp(self::getDSN($conn), 'fetchAll', $sql, false);
				if (DEBUG_MODE) {
					Util::prt($sql);
				} else {
					die('Invalid db Query');
				}
			}
			$result = $statement->fetchAll(\PDO::FETCH_ASSOC);

			self::logDBOp(self::getDSN($conn), 'fetchAll', $sql, $result);
			$statement->closeCursor();
		} catch (\PDOException $e) {
			self::logDBOp(self::getDSN($conn), 'fetch', $sql, false);
			if (DEBUG_MODE) {
				Util::prt($e->getMessage());
			} else {
				die('db error');
			}
		}
		return $result;
	}

	public static function lastID ($conn=DEFAULT_DB_CONN) {
		$pdo = self::getConnection($conn);
		return $pdo->lastInsertId();
	}

	public static function quote ($str) {
		if (is_null($str)) {
			return 'NULL';
		}
		$pdo = self::getConnection(DEFAULT_DB_CONN);
		return $pdo->quote($str);
	}

	public static function lastError ($conn=DEFAULT_DB_CONN) {
		$pdo = self::getConnection($conn);
		return $pdo->errorInfo();
	}

	public static function escapeName ($name) {
		return preg_replace('/[^\w\.`]+/', '', $name);
	}

	public static function getColumns ($tableName, array $exceptCols=array(), $conn=DEFAULT_DB_CONN) {
		$tableName = self::escapeName($tableName);
		$pdo = self::getConnection($conn);
		$query = $pdo->prepare("DESCRIBE `$tableName`");
		$query->execute();
		$fields = $query->fetchAll(\PDO::FETCH_COLUMN);
		$cols = array();
		foreach ($fields as $key => $col) {
			if (!in_array($col, $exceptCols)) {
				$cols[] = $col;
			}
		}
		return $cols;
	}

	public static function getRealTableName ($tableName, $conn=DEFAULT_DB_CONN) {
		return self::escapeName(self::getTablePrefix($conn).$tableName.self::getTableSuffix($conn));
	}

}
