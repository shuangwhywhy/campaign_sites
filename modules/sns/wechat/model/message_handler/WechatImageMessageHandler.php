<?php

namespace modules\sns\wechat\model\message_handler;

use \modules\sns\wechat\model\WechatEvent;
use \modules\sns\wechat\model\message_handler\event_handler\WechatImageEventHandler;

class WechatImageMessageHandler extends \modules\sns\wechat\model\WechatMessageHandler {

	protected function getWechatImageEventHandler () {
		return new WechatImageEventHandler();
	}

	public function processMessage ($message, $handler=null) {
		if (!$handler || !($handler instanceof WechatImageEventHandler)) {
			$handler = $this->getWechatImageEventHandler();
		}
		$event = new WechatEvent($message, $handler);
		return $event->dispatch();
	}

}
