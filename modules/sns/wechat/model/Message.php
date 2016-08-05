<?php

namespace modules\sns\wechat\model;

use \modules\core\common\model\JsonModel;
use \modules\core\common\model\XmlModel;

abstract class Message {

	public $id;

	public $from;

	public $to;

	public $type;

	public function setFrom ($from) {
		$this->from = $from;
	}

	public function setTo ($to) {
		$this->to = $to;
	}

	public function setType ($type) {
		$this->type = $type;
	}

	public function getCustomerServiceFormat () {
		$data = $this->getCustomerServiceFormatArray();
		if (!$data) {
			return false;
		}
		$json = new JsonModel($data);
		return $json->toString(false);
	}

	public abstract function getCustomerServiceFormatArray ();

	public function getAutoReplyFormat () {
		$data = $this->getAutoReplyFormatArray();
		if (!$data) {
			return false;
		}
		$json = new XmlModel($data);
		return $xml->toString();
	}

	public abstract function getAutoReplyFormatArray ();

	public function getBroadcastMessageFormat () {
		$data = $this->getBroadcastMessageFormatArray();
		if (!$data) {
			return false;
		}
		$json = new JsonModel($data);
		return $json->toString(false);
	}

	public abstract function getBroadcastMessageFormatArray ();

	public function getUploadMaterialFormat () {
		$data = $this->getUploadMaterialFormatArray();
		if (!$data) {
			return false;
		}
		$json = new JsonModel($data);
		return $json->toString(false);
	}

	public abstract function getUploadMaterialFormatArray ();

	public abstract function getData ();

}
