<?php

namespace modules\sns\wechat\model\message_handler\event_handler;

use \modules\sns\wechat\model\WechatEvent;
use \modules\sns\wechat\helper\Wechat;
use \modules\core\common\helper\DB;
use \modules\core\common\helper\Util;
use \modules\sns\wechat\helper\Logger;

class WechatSubscribeEventHandler extends \modules\sns\wechat\model\message_handler\WechatEventHandler {

	protected function processScanSubscribeEvent ($open_id, $wid, $scene) {

	}

	protected function processNormalSubscribeEvent ($open_id, $wid) {

	}

	public function processEvent (WechatEvent $event) {
		$message = $event->getMessage();
		$open_id = $event->getOpenID();
		$wid = $event->getWid();

		DB::exec("UPDATE `wechat_users` SET `subscribe` = 1 WHERE `open_id` = ".DB::quote($open_id)." AND `wid` = ".DB::quote($wid), CORE_DB_CONN);

		$matches = array();
		$scene = null;
		$key = $message['EventKey'];
		if (empty($key)) {
			$key = '';
		}
		if (preg_match('/^qrscene_(.+)$/', $key, $matches)) {
			$scene = $matches[1];
			Logger::logWxScanEvent($open_id, $wid, $scene, 'subscribe');
			$this->processScanSubscribeEvent($open_id, $wid, $scene);
		} else {
			$this->processNormalSubscribeEvent($open_id, $wid);
		}
		Logger::logWxSubscribeEvent($open_id, $wid, $scene);

		return true;
	}

}
