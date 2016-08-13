<?php

namespace modules\example\model\wechat;

use \modules\core\common\helper\DB;
use \modules\example\helper\Logger;
use \modules\core\common\helper\Util;
use \modules\sns\wechat\model\WechatEvent;

class WechatUnsubscribeEventHandler extends \modules\sns\wechat\model\message_handler\event_handler\WechatUnsubscribeEventHandler {

	public function beforeProcessEvent (WechatEvent $event) {
		parent::beforeProcessEvent($event);

		$wid = $event->getWid();
		// update local database:
		DB::exec("UPDATE `ms_member_member` SET `is_follow` = 0 WHERE `weixin_open_id` = ".DB::quote($wid), LEGACY_DB_CONN);

		// call defacto to update ECDB:
		$url = 'http://'.$_SERVER['SERVER_NAME'].'/home/vip/defollow_api.php?wid='.$wid.'&type=defollow';
		$result = Util::request($url, false);
		Logger::logMemberApi($_SERVER['REMOTE_ADDR'], 'member_defollow', $url, $result, $wid);

		return true;
	}

}
