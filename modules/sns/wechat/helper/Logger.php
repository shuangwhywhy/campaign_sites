<?php

namespace modules\sns\wechat\helper;

use \modules\core\db\helper\DB;

class Logger extends \modules\core\db\helper\Logger {

	public static function logOAuth ($openID, $wid, $subscribeTime) {
		DB::exec(
			"INSERT INTO `oauth_log`
				(`open_id`, `wid`, `oauth_time`, `subscribe_time`)
			VALUES
				(
					".DB::quote($openID).",
					".DB::quote($wid).",
					".time().",
					".intval($subscribeTime)."
				)"
		);
	}

	public static function logWxApi ($openID, $apiName, $url, $retData, $postData=null) {
		$result = DB::exec(
			"INSERT INTO `wx_api_log`
			(`open_id`, `api_name`, `url`, `response`, `post_data`, `create_time`)
			VALUES
			(
				".DB::quote($openID).",
				".DB::quote($apiName).",
				".DB::quote($url).",
				".DB::quote(print_r($retData, true)).",
				".DB::quote(print_r($postData, true)).",
				".time()."
			)", CORE_DB_CONN
		);
		if ($result) {
			return DB::lastID(CORE_DB_CONN);
		} else {
			return false;
		}
	}

	public static function logWxMessage ($openID, $wid, $data) {
		$result = DB::exec(
			"INSERT INTO `received_msg`
				(`open_id`, `wid`, `message`, `time`)
			VALUES
				(
					".DB::quote($openID).",
					".DB::quote($wid).",
					".DB::quote($data).",
					".time()."
				)", CORE_DB_CONN
		);
		if ($result) {
			return DB::lastID(CORE_DB_CONN);
		} else {
			return false;
		}
	}

	public static function logWxShare ($openID, $wid, $page, $url, $type) {
		$sql = "INSERT INTO `share_history`
					(`open_id`, `wid`, `share_page`, `full_url`, `share_type`, `time`)
				VALUES
					(
						".DB::quote($openID).",
						".DB::quote($wid).",
						".DB::quote($page).",
						".DB::quote($url).",
						".DB::quote($type).",
						".time()."
					)";
		$result = DB::exec($sql);
		return $result;
	}

	public static function logWxShareBack ($openID, $wid, $fromWids, $sharePage, $shareType) {
		DB::exec(
			"INSERT INTO `share_back_history`
				(`open_id`, `wid`, `from`, `attempt_page`, `page`, `url`, `type`, `time`)
			VALUES
				(
					".DB::quote($openID).",
					".DB::quote($wid).",
					".DB::quote(json_encode($fromWids)).",
					'',
					".DB::quote($sharePages).",
					'',
					".DB::quote($shareType).",
					".time()."
				)"
		);
	}

	/**
	 * $type: auto/manual
	 */
	public static function logWxLocationEvent ($openID, $wid, $lat, $lng, $precision, $type) {
		DB::exec(
			"INSERT INTO `user_location_history`
				(`open_id`, `wid`, `lat`, `lng`, `precision`, `type`, `time`)
			VALUES
				(
					".DB::quote($openID).",
					".DB::quote($wid).",
					".DB::quote($lat).",
					".DB::quote($lng).",
					".DB::quote($precision).",
					".DB::quote($type).",
					".time()."
				)", CORE_DB_CONN
		);
	}

	/**
	 * $type: normal/subscribe
	 */
	public static function logWxScanEvent ($openID, $wid, $scene, $type) {
		DB::exec(
			"INSERT INTO `qr_scan_log`
				(`open_id`, `wid`, `qr_scene`, `type`, `time`)
			VALUES
				(
					".DB::quote($openID).",
					".DB::quote($wid).",
					".DB::quote($scene).",
					".DB::quote($type).",
					".time()."
				)", CORE_DB_CONN
		);
	}

	public static function logWxSubscribeEvent ($openID, $wid, $scene) {
		DB::exec(
			"INSERT INTO `subscribe_log`
				(`open_id`, `wid`, `qr_scene`, `time`)
			VALUES
				(
					".DB::quote($openID).",
					".DB::quote($wid).",
					".DB::quote($scene).",
					".time()."
				)", CORE_DB_CONN
		);
	}

}
