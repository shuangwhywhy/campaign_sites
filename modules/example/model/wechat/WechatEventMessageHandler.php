<?php

namespace modules\example\model\wechat;

class WechatEventMessageHandler extends \modules\sns\wechat\model\message_handler\WechatEventMessageHandler {

	protected function getWechatSubscribeEventHandler () {
		return new WechatSubscribeEventHandler();
	}

	protected function getWechatUnsubscribeEventHandler () {
		return new WechatUnsubscribeEventHandler();
	}

}
