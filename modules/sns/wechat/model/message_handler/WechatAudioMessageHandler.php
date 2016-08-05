<?php

namespace modules\sns\wechat\model\message_handler;

use \modules\sns\wechat\model\WechatEvent;
use \modules\sns\wechat\model\message_handler\event_handler\WechatAudioEventHandler;

class WechatAudioMessageHandler extends \modules\sns\wechat\model\WechatMessageHandler {

	protected function getWechatAudioEventHandler () {
		return new WechatAudioEventHandler();
	}

	public function processMessage ($message, $handler=null) {
		if (!$handler || !($handler instanceof WechatAudioEventHandler)) {
			$handler = $this->getWechatAudioEventHandler();
		}
		$event = new WechatEvent($message, $handler);
		return $event->dispatch();
	}

}
