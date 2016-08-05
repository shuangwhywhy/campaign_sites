<?php

namespace modules\sns\wechat\model\message_handler;

use \modules\sns\wechat\model\WechatEvent;

abstract class WechatEventHandler {

	public function beforeProcessEvent (WechatEvent $event) {
		return true;
	}

	public abstract function processEvent (WechatEvent $event);

	public function postProcessEvent (WechatEvent $event) {
		return true;
	}

	public function handleEvent (WechatEvent $event) {
		return $this->beforeProcessEvent($event) && $this->processEvent($event) && $this->postProcessEvent($event);
	}

}
