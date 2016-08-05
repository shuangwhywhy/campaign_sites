<?php

namespace modules\core\common\model;

class ResultObj {

	protected $data = array('success'=>false, 'code'=>null, 'data'=>null, 'message'=>null);

	public function __construct ($success=false, $code=null, $data=null, $message=null) {
		$this->setSuccess($success);
		$this->setCode($code);
		$this->setData($data);
		$this->setMessage($message);
	}

	public function setSuccess ($success) {
		$this->data['success'] = $success;
	}

	public function isSuccess () {
		return $this->data['success'];
	}

	public function setCode ($code) {
		$this->data['code'] = $code;
	}

	public function getCode () {
		return $this->data['code'];
	}

	public function setData ($data) {
		$this->data['data'] = $data;
	}

	public function getData () {
		return $this->data['data'];
	}

	public function setMessage ($message) {
		$this->data['message'] = $message;
	}

	public function getMessage () {
		return $this->data['message'];
	}

	public function toArray () {
		return $this->data;
	}

}
