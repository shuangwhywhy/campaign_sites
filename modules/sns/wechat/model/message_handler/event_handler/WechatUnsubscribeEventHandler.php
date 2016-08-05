<?php

namespace modules\sns\wechat\model\message_handler\event_handler;

use \modules\sns\wechat\model\WechatEvent;
use \modules\core\common\helper\DB;

class WechatUnsubscribeEventHandler extends \modules\sns\wechat\model\message_handler\WechatEventHandler {

	protected function processUnsubscribeEvent ($open_id, $wid) {

	}

	public function processEvent (WechatEvent $event) {
		$message = $event->getMessage();
		$open_id = $event->getOpenID();
		$wid = $event->getWid();

		DB::exec("UPDATE `wechat_users` SET `subscribe` = 0 WHERE `open_id` = ".DB::quote($open_id)." AND `wid` = ".DB::quote($wid), CORE_DB_CONN);

		$this->processUnsubscribeEvent($open_id, $wid);
		return true;
	}

}
