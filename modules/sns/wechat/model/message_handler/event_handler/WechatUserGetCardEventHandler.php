<?php

namespace modules\sns\wechat\model\message_handler\event_handler;

use \modules\sns\wechat\model\message_handler\WechatEventHandler;
use \modules\sns\wechat\model\WechatEvent;
use \modules\core\common\helper\DB;

class WechatUserGetCardEventHandler extends WechatEventHandler {

	public function processEvent (WechatEvent $event) {
		$message = $event->getMessage();
		$open_id = $event->getOpenID();
		$wid = $event->getWid();

		$time = $message['CreateTime'];
		$friend = null;
		if (!empty($message['IsGiveByFriend'])) {
			$friend = $message['FriendUserName'];
		}
		DB::exec(
			"INSERT INTO `wechat_event_card_obtain`
				(`open_id`, `wid`, `card_id`, `code`, `last_code`, `outer_id`, `friend`, `time`)
			VALUES
				(
					".DB::quote($open_id).",
					".DB::quote($wid).",
					".DB::quote($message['CardId']).",
					".DB::quote(empty($message['UserCardCode']) ? NULL : $message['UserCardCode']).",
					".DB::quote(empty($message['OldUserCardCode']) ? NULL : $message['OldUserCardCode']).",
					".DB::quote(empty($message['OuterId']) ? NULL : $message['OuterId']).",
					".DB::quote($friend).",
					".intval($time)."
				)"
		);
		return true;
	}

}
