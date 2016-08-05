<?php

namespace modules\sns\wechat\model\message_handler\event_handler;

use \modules\sns\wechat\model\WechatEvent;
use \modules\sns\wechat\helper\Wechat;
use \modules\sns\wechat\helper\Logger;
use \modules\core\common\helper\DB;
use \modules\core\common\helper\Util;

class WechatScanEventHandler extends \modules\sns\wechat\model\message_handler\WechatEventHandler {

	protected function processScanEvent ($open_id, $wid, $scene) {

	}

	public function processEvent (WechatEvent $event) {
		$message = $event->getMessage();
		$open_id = $event->getOpenID();
		$wid = $event->getWid();
		$scene = $message['EventKey'];

		Logger::logWxScanEvent($open_id, $wid, $scene, 'normal');
		$this->processScanEvent($open_id, $wid, $scene);
		return true;
	}

}
