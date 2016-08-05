<?php

namespace modules\sns\wechat\model;

use \modules\sns\wechat\model\message_handler\WechatEventHandler;

class WechatEvent {

	const SUBSCRIBE							= 'subscribe';
	const UNSUBSCRIBE						= 'unsubscribe';
	const SCAN								= 'SCAN';
	const LOCATION							= 'LOCATION';
	const USER_GET_CARD						= 'user_get_card';
	const USER_DEL_CARD						= 'user_del_card';
	const USER_CONSUME_CARD					= 'user_consume_card';
	const USER_ENTER_SESSION_FROM_CARD		= 'user_enter_session_from_card';

	protected $handler;

	protected $message;

	protected $open_id;

	protected $wid;

	protected $msg_type;

	public function __construct ($message, $handler=null) {
		$this->message = $message;
		$this->open_id = $message['ToUserName'];
		$this->wid = $message['FromUserName'];
		$this->msg_type = $message['MsgType'];
		$this->setHandler($handler);
	}

	public function getOpenID () {
		return $this->open_id;
	}

	public function getWid () {
		return $this->wid;
	}

	public function getMessage () {
		return $this->message;
	}

	public function getMessageType () {
		return $this->msg_type;
	}

	public function setHandler ($handler) {
		$this->handler = $handler;
	}

	public function getHandler () {
		return $this->handler;
	}

	protected function beforeDispatch ($handler) {
		return true;
	}

	protected function postDispatch ($handleResult) {
		return $handleResult;
	}

	public function dispatch ($handler=null) {
		if (!$handler || !($handler instanceof WechatEventHandler)) {
			$handler = $this->handler;
		}
		if (!$handler || !($handler instanceof WechatEventHandler)) {
			return false;
		}
		if ($this->beforeDispatch($handler)) {
			$result = $handler->handleEvent($this);
			return $this->postDispatch($result);
		}
		return false;
	}

}
