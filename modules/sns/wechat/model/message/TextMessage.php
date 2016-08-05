<?php

namespace modules\sns\wechat\model\message;

use \modules\sns\wechat\model\Message;
use \modules\sns\wechat\helper\Wechat;

class TextMessage extends Message {

	public $content = '';

	public function __construct ($to='', $content='') {
		$this->from = Wechat::getOpenID();
		$this->to = $to;
		$this->content = $content;
		$this->type = 'text';
	}

	public function setContent ($content) {
		$this->content = $content;
	}

	public function getCustomerServiceFormatArray () {
		if (!empty($this->content)) {
			$data = array(
				'touser' => $this->to,
				'msgtype' => $this->type,
				'text' => array(
					'content' => $this->content
				)
			);
			return $data;
		} else {
			return false;
		}
	}

	public function getAutoReplyFormatArray () {
		if (!empty($this->content)) {
			$data = array(
				'ToUserName' => array('leaf'=>true, 'type'=>'cdata', 'value'=>$this->to),
				'FromUserName' => array('leaf'=>true, 'type'=>'cdata', 'value'=>$this->from),
				'CreateTime' => time(),
				'MsgType' => array('leaf'=>true, 'type'=>'cdata', 'value'=>$this->type),
				'Content' => array('leaf'=>true, 'type'=>'cdata', 'value'=>$this->content)
			);
			return $data;
		} else {
			return false;
		}
	}

	public function getBroadcastMessageFormatArray () {

	}

	public function getUploadMaterialFormatArray () {
		// Not supported
		return false;
	}

	public function getData () {
		return $this->content;
	}

}
