<?php

namespace modules\example\model\wechat;

use \modules\sns\wechat\model\WechatEvent;
use \modules\sns\wechat\helper\Wechat;
use \modules\sns\wechat\model\message\TextMessage;
use \modules\core\common\helper\DB;
use \modules\core\common\helper\Util;
use \modules\example\helper\Logger;

class WechatSubscribeEventHandler extends \modules\sns\wechat\model\message_handler\event_handler\WechatSubscribeEventHandler {

	public function beforeProcessEvent (WechatEvent $event) {
		parent::beforeProcessEvent($event);

		$wid = $event->getWid();
		// update local database:
		DB::exec("UPDATE `ms_member_member` SET `is_follow` = 1 WHERE `weixin_open_id` = ".DB::quote($wid), LEGACY_DB_CONN);

		// call defacto to update ECDB:
		$url = 'http://'.$_SERVER['SERVER_NAME'].'/home/vip/defollow_api.php?wid='.$wid.'&type=follow';
		$result = Util::request($url, false);
		Logger::logMemberApi($_SERVER['REMOTE_ADDR'], 'member_follow', $url, $result, $wid);

		// Get Example Client Member ID from ECDB:
		$user = Wechat::getMemberDetail($wid);
		if ($user) {
			$memberResult = Util::post(
				'http://'.$_SERVER['SERVER_NAME'].'/home/vip/get_member_info.php',
				array('auth_key' => 'P4J(&Y6ijO*Ij@p8o', 'openid' => $wid)
			);
			if ($memberResult && $memberResult['success']) {
				DB::exec("UPDATE `wechat_users` SET `member_id` = ".DB::quote($memberResult['data']['customerdata']['customerid'])." WHERE `wid` = ".DB::quote($wid), CORE_DB_CONN);
			}
		}

		// send original welcome message:
		$message = new TextMessage($wid, "感谢您的关注！");
		Wechat::sendCSMessage($message);
		return true;
	}

}
