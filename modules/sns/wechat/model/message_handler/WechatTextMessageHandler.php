<?php

namespace modules\sns\wechat\model\message_handler;

use \modules\sns\wechat\model\WechatEvent;
use \modules\sns\wechat\model\message_handler\event_handler\WechatTextEventHandler;

class WechatTextMessageHandler extends \modules\sns\wechat\model\WechatMessageHandler {

	protected function getWechatTextEventHandler () {
		return new WechatTextEventHandler();
	}

	public function processMessage ($message, $handler=null) {
		if (!$handler || !($handler instanceof WechatTextEventHandler)) {
			$handler = $this->getWechatTextEventHandler();
		}
		$event = new WechatEvent($message, $handler);
		return $event->dispatch();
	}

}
