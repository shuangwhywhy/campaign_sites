<?php

namespace modules\sns\wechat\model\message_handler;

use \modules\sns\wechat\model\WechatEvent;
use \modules\sns\wechat\model\message_handler\event_handler\WechatLocationEventHandler;

class WechatLocationMessageHandler extends \modules\sns\wechat\model\WechatMessageHandler {

	protected function getWechatLocationEventHandler () {
		return new WechatLocationEventHandler();
	}

	public function processMessage ($message, $handler=null) {
		if (!$handler || !($handler instanceof WechatLocationEventHandler)) {
			$handler = $this->getWechatLocationEventHandler();
		}
		$event = new WechatEvent($message, $handler);
		return $event->dispatch();
	}

}
