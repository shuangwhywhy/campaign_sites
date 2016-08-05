<?php

namespace modules\sns\wechat\model\message_handler\event_handler;

use \modules\sns\wechat\model\WechatEvent;
use \modules\sns\wechat\helper\Wechat;
use \modules\sns\wechat\helper\Logger;
use \modules\core\common\helper\DB;
use \modules\core\common\helper\Util;

class WechatLocationEventHandler extends \modules\sns\wechat\model\message_handler\WechatEventHandler {

	protected function processAutoLocationEvent ($open_id, $wid, $lat, $lng, $precision) {

	}

	protected function processManualLocationEvent ($open_id, $wid, $lat, $lng, $scale) {

	}

	public function processEvent (WechatEvent $event) {
		$message = $event->getMessage();
		$open_id = $event->getOpenID();
		$wid = $event->getWid();
		$type = $event->getMessageType();

		$lat = 0;
		$lng = 0;
		$precision = 0;

		if ($type == 'event') {
			$lat = $message['Latitude'];
			$lng = $message['Longitude'];
			$precision = $message['Precision'];
			Logger::logWxLocationEvent($open_id, $wid, $lat, $lng, $precision, 'auto');
			$this->processAutoLocationEvent($open_id, $wid, $lat, $lng, $precision);

		} else if ($type == 'location') {
			$lat = $message['Location_X'];
			$lng = $message['Location_Y'];
			$scale = $message['Scale'];
			Logger::logWxLocationEvent($open_id, $wid, $lat, $lng, $scale, 'manual');
			$this->processManualLocationEvent($open_id, $wid, $lat, $lng, $scale);
		}
		return true;
	}

}
