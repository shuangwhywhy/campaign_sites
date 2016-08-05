<?php

namespace modules\sns\wechat\model\message;

use \modules\sns\wechat\model\Message;
use \modules\sns\wechat\helper\Wechat;

class CardMessage extends Message {

	public $card_id;

	public $code;

	public $bind_user;

	public $outer_id;

	public function __construct ($to='', $card_id='', $bind_user=false, $outer_id='', $code='') {
		$this->from = Wechat::getOpenID();
		$this->to = $to;
		$this->card_id = $card_id;
		$this->outer_id = $outer_id;
		$this->code = $code;
		$this->bind_user = $bind_user;
		$this->type = 'wxcard';
	}

	public function getCustomerServiceFormatArray () {
		if (!empty($this->content)) {
			$fields = array();
			if (!empty($this->code)) {
				$fields['code'] = $this->code;
			}
			if (!empty($this->bind_user)) {
				$fields['openid'] = $this->to;
			}
			$fields = Wechat::getCardApiSignature($fields);
			if (!empty($this->outer_id)) {
				$fields['outer_id'] = $this->outer_id;
			}
			$data = array(
				'touser' => $this->to,
				'msgtype' => $this->type,
				'wxcard' => array(
					'card_id' => $this->card_id,
					'card_ext' => $fields
				)
			);
			return $data;
		} else {
			return false;
		}
	}

	public function getAutoReplyFormatArray () {
		// Not supported
		return false;
	}

	public function getBroadcastMessageFormatArray () {
		return array(
			'touser' => $this->to,
			'wxcard' => array(
				'card_id' => $this->card_id
			),
			'msgtype' => $this->type
		);
	}

	public function getUploadMaterialFormatArray () {

	}

	public function getData () {

	}

}
