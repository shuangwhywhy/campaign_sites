<?php

namespace modules\core\common\helper;

class Logger {

	public static function logApi ($ip, $action, $url, $postData, $ret) {
		DB::exec(
			"INSERT INTO `api_log`
				(`ip`, `api_name`, `url`, `post_data`, `response`, `create_time`)
			VALUES
				(
					".DB::quote($ip).",
					".DB::quote($action).",
					".DB::quote($url).",
					".DB::quote(json_encode($postData)).",
					".DB::quote($ret).",
					".time()."
				)", CORE_DB_CONN
		);
	}

}
