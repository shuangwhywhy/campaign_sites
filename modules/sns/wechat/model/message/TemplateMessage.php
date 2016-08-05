<?php

namespace modules\sns\wechat\model\message;

use \modules\sns\wechat\model\Message;
use \modules\core\common\model\JsonModel;
use \modules\sns\wechat\helper\Wechat;
use \modules\core\common\helper\Util;

class TemplateMessage extends Message {

	public $content = array();

	public $template_id;

	public $url;

	public $template = '';

	public function __construct ($to='', $template_id='', $url='', $content=array()) {
		$this->from = Wechat::getOpenID();
		$this->to = $to;
		$this->template_id = $template_id;
		$this->url = $url;
		$this->content = $content;
		$this->type = 'template';
	}

	public function setTemplate ($template) {
		$this->template = $template;
	}

	public function setContent ($content) {
		$this->content = $content;
	}

	public function addEntry ($name, $value, $color='#000000') {
		$this->content[$name] = array('value' => $value, 'color' => $color);
	}

	public function getCustomerServiceFormatArray () {
		// Not supported
		return false;
	}

	public function getAutoReplyFormatArray () {
		// Not supported
		return false;
	}

	public function getBroadcastMessageFormatArray () {
		// Not supported
		return false;
	}

	public function getUploadMaterialFormatArray () {
		// Not supported
		return false;
	}

	public function getTemplateFormatArray () {
		return array(
			'touser' => $this->to,
			'template_id' => $this->template_id,
			'url' => $this->url,
			'data' => $this->content
		);
	}

	public function getTemplateFormat () {
		$data = $this->getTemplateFormatArray();
		if (!$data) {
			return false;
		}
		$json = new JsonModel($data);
		return $json->toString(false);
	}

	public function getData () {
		return $this->getContent();
	}

	public function getContent () {
		return $this->content;
	}

	public function getContentString () {
		$str = $this->template;
		foreach ($this->content as $key => $val) {
			$str = str_replace('{{'.$key.'.DATA}}', $val['value'], $str);
		}
		return $str;
	}

	public function toString () {
		return $this->getContentString();
	}

}
