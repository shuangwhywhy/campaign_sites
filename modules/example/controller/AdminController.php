<?php

namespace modules\example\controller;

use \modules\core\common\helper\DB;
use \modules\core\common\helper\Util;
use \modules\core\common\model\ViewModel;
use \modules\sns\wechat\model\message\CardMessage;
use \modules\sns\wechat\model\message\TemplateMessage;
use \modules\sns\wechat\helper\Wechat;
use \modules\example\helper\MemberHelper;

class AdminController extends \modules\sns\wechat\controller\WechatController {

	protected $rights_level = 'admin';

	protected $noDigestAuth = array();

	protected $adminUser = null;

	public function beforeDispatch ($action) {
		$this->adminUser = Util::digestAuth();
		if (!in_array($action, $this->noDigestAuth) && !$this->adminUser) {
			die('Unauthorized');
		}
		return parent::beforeDispatch($action);
	}

	public function getDefaultPlatform () {
		return 'admin';
	}

}
