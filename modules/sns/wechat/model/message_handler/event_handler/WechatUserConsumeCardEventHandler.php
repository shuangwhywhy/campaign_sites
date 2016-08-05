<?php

namespace modules\sns\wechat\model\message_handler\event_handler;

use \modules\sns\wechat\model\message_handler\WechatEventHandler;
use \modules\sns\wechat\model\WechatEvent;
use \modules\core\common\helper\DB;

class WechatUserConsumeCardEventHandler extends WechatEventHandler {

	public function processEvent (WechatEvent $event) {
		$message = $event->getMessage();
		$open_id = $event->getOpenID();
		$wid = $event->getWid();

		$time = $message['CreateTime'];
		DB::exec(
			"INSERT INTO `wechat_event_card_consume`
				(`open_id`, `wid`, `staff_wid`, `source`, `location_id`, `card_id`, `trans_id`, `out_trade_no`, `time`)
			VALUES
				(
					".DB::quote($open_id).",
					".DB::quote($wid).",
					".DB::quote(empty($message['StaffOpenId']) ? NULL : $message['StaffOpenId']).",
					".DB::quote($message['ConsumeSource']).",
					".DB::quote(empty($message['LocationId']) ? NULL : $message['LocationId']).",
					".DB::quote($message['CardId']).",
					".DB::quote(empty($message['TransId']) ? NULL : $message['TransId']).",
					".DB::quote(empty($message['OutTradeNo']) ? NULL : $message['OutTradeNo']).",
					".intval($time)."
				)"
		);
		return true;
	}

}
