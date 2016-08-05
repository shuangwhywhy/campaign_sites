<?php

namespace modules\sns\wechat\model\message_handler;

use \modules\sns\wechat\model\WechatEvent;
use \modules\sns\wechat\model\message_handler\event_handler\WechatSubscribeEventHandler;
use \modules\sns\wechat\model\message_handler\event_handler\WechatUnsubscribeEventHandler;
use \modules\sns\wechat\model\message_handler\event_handler\WechatScanEventHandler;
use \modules\sns\wechat\model\message_handler\event_handler\WechatLocationEventHandler;
use \modules\sns\wechat\model\message_handler\event_handler\WechatUserGetCardEventHandler;
use \modules\sns\wechat\model\message_handler\event_handler\WechatUserDeleteCardEventHandler;
use \modules\sns\wechat\model\message_handler\event_handler\WechatUserConsumeCardEventHandler;
use \modules\sns\wechat\model\message_handler\event_handler\WechatUserEnterSessionFromCardEventHandler;

class WechatEventMessageHandler extends \modules\sns\wechat\model\WechatMessageHandler {

	protected function getWechatSubscribeEventHandler () {
		return new WechatSubscribeEventHandler();
	}

	protected function getWechatUnsubscribeEventHandler () {
		return new WechatUnsubscribeEventHandler();
	}

	protected function getWechatScanEventHandler () {
		return new WechatScanEventHandler();
	}

	protected function getWechatLocationEventHandler () {
		return new WechatLocationEventHandler();
	}

	protected function getWechatUserGetCardEventHandler () {
		return new WechatUserGetCardEventHandler();
	}

	protected function getWechatUserDeleteCardEventHandler () {
		return new WechatUserDeleteCardEventHandler();
	}

	protected function getWechatUserConsumeCardEventHandler () {
		return new WechatUserConsumeCardEventHandler();
	}

	protected function getWechatUserEnterSessionFromCardEventHandler () {
		return new WechatUserEnterSessionFromCardEventHandler();
	}

	public function processMessage ($message, $handler=null) {
		$event = null;
		switch ($message['Event']) {

			case WechatEvent::SUBSCRIBE:
				if (!$handler || !($handler instanceof WechatSubscribeEventHandler)) {
					$handler = $this->getWechatSubscribeEventHandler();
				}
				$event = new WechatEvent($message, $handler);
				break;

			case WechatEvent::UNSUBSCRIBE:
				if (!$handler || !($handler instanceof WechatUnsubscribeEventHandler)) {
					$handler = $this->getWechatUnsubscribeEventHandler();
				}
				$event = new WechatEvent($message, $handler);
				break;

			case WechatEvent::SCAN:
				if (!$handler || !($handler instanceof WechatScanEventHandler)) {
					$handler = $this->getWechatScanEventHandler();
				}
				$event = new WechatEvent($message, $handler);
				break;

			case WechatEvent::LOCATION:
				if (!$handler || !($handler instanceof WechatLocationEventHandler)) {
					$handler = $this->getWechatLocationEventHandler();
				}
				$event = new WechatEvent($message, $handler);
				break;

			case WechatEvent::USER_GET_CARD:
				if (!$handler || !($handler instanceof WechatUserGetCardEventHandler)) {
					$handler = $this->getWechatUserGetCardEventHandler();
				}
				$event = new WechatEvent($message, $handler);
				break;

			case WechatEvent::USER_DEL_CARD:
				if (!$handler || !($handler instanceof WechatUserDeleteCardEventHandler)) {
					$handler = $this->getWechatUserDeleteCardEventHandler();
				}
				$event = new WechatEvent($message, $handler);
				break;

			case WechatEvent::USER_CONSUME_CARD:
				if (!$handler || !($handler instanceof WechatUserConsumeCardEventHandler)) {
					$handler = $this->getWechatUserConsumeCardEventHandler();
				}
				$event = new WechatEvent($message, $handler);
				break;

			case WechatEvent::USESR_ENTER_SESSION_FROM_CARD:
				if (!$handler || !($handler instanceof WechatUserEnterSessionFromCardEventHandler)) {
					$handler = $this->getWechatUserEnterSessionFromCardEventHandler();
				}
				$event = new WechatEvent($message, $handler);
				break;

		}
		if ($event && $event instanceof WechatEvent) {
			return $event->dispatch();
		}
		return false;
	}

}
