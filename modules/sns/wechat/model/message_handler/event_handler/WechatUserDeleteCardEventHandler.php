<?php

namespace modules\sns\wechat\model\message_handler\event_handler;

use \modules\sns\wechat\model\message_handler\WechatEventHandler;
use \modules\sns\wechat\model\WechatEvent;
use \modules\core\common\helper\DB;

class WechatUserDeleteCardEventHandler extends WechatEventHandler {

	public function processEvent (WechatEvent $event) {
		$message = $event->getMessage();
		$open_id = $event->getOpenID();
		$wid = $event->getWid();

		$time = $message['CreateTime'];
		DB::exec(
			"INSERT INTO `wechat_event_card_delete`
				(`open_id`, `wid`, `card_id`, `code`, `time`)
			VALUES
				(
					".DB::quote($open_id).",
					".DB::quote($wid).",
					".DB::quote($message['CardId']).",
					".DB::quote(empty($message['UserCardCode']) ? NULL : $message['UserCardCode']).",
					".intval($time)."
				)"
		);
		return true;
	}

}
