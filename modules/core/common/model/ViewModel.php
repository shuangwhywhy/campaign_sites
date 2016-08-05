<?php

namespace modules\core\common\model;

class ViewModel {

	protected $data = null;
	protected $template = '';
	public $platform = '';

	public function __construct ($template, $data=null) {
		$this->setTemplate($template);
		$this->setData($data);
	}

	public function setTemplate ($template) {
		$this->template = $template;
	}

	public function setData ($data) {
		$this->data = $data;
	}

	public function getData () {
		return $this->data;
	}

	public function render ($platform='mobile') {
		$data = $this->data;
		include APP_DIR.DS.'view'.DS.$platform.DS.'public'.DS.'header.phtml';
		include APP_DIR.DS.'view'.DS.$platform.DS.$this->template.'.php';
		include APP_DIR.DS.'view'.DS.$platform.DS.'public'.DS.'footer.phtml';
	}

	public function renderPartial ($platform='mobile') {
		$data = $this->data;
		include APP_DIR.DS.'view'.DS.$platform.DS.$this->template.'.php';
	}

	public function getDefaultPlatform () {
		return $this->platform;
	}

}
