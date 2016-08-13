<?php

namespace clients\example\sites\common\app\controller;

use \modules\core\common\helper\DB;
use \modules\core\common\helper\Util;
use \modules\core\common\model\ViewModel;
use \modules\sns\wechat\model\message\CardMessage;
use \modules\sns\wechat\model\message\TemplateMessage;
use \modules\sns\wechat\helper\Wechat;
use \modules\example\helper\MemberHelper;

class AdminController extends \modules\example\controller\AdminController {

	protected $noDigestAuth = array('dashboard');

	public function loginAction () {
		Util::setIclpCookie('wx_auth_openid', WX_FAKE_LOGIN);
		Util::prt('Done');
	}

	public function setupAction () {
		// Write initialization code here
		exit;
	}

	public function dashboardAction () {
	}

	public function indexAction () {
	}

	public function getMemberDetailsAction () {
	}

}
