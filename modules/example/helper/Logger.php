<?php

namespace modules\example\helper;

use \modules\core\db\helper\DB;

class Logger extends \modules\sns\wechat\helper\Logger {

	public static function logMemberApi ($ip, $apiName, $url, $result, $wid = NULL, $request = NULL, $memberData = NULL) {
		if (!is_null($request)) {
			$request = print_r($request, true);
		}
		if (!is_null($memberData)) {
			$memberData = print_r($memberData, true);
		}
		DB::exec(
			"INSERT INTO `member_api_log`
			SET
				`ip` = ".DB::quote($ip).",
				`wid` = ".DB::quote($wid).",
				`api_name` = ".DB::quote($apiName).",
				`url` = ".DB::quote($url).",
				`post_data` = ".DB::quote($request).",
				`response` = ".DB::quote($result).",
				`member_data` = ".DB::quote($memberData).",
				`create_time` = ".time(),
			CORE_DB_CONN);
	}

}
