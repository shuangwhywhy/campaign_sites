<?php

namespace modules\example\controller;

use \modules\core\db\helper\DB;
use \modules\example\helper\Util;
use \modules\core\common\model\ViewModel;
use \modules\sns\wechat\helper\Wechat;
use \modules\sns\wechat\model\User;
use \modules\sns\wechat\model\message\TemplateMessage;

class PageController extends \modules\sns\wechat\controller\WechatController {

	protected $ongoing = array(
		'wechatAuthCallback',
		'campaignEnd',
		'campaignNotStarted',
	);

	public function beforeDispatch ($action) {
		if (!in_array($action, $this->ongoing) && !Util::isCampaignTime() && !Util::isTestUser(false, 'testers.conf.php')) {
			if (Util::isCampaignEnded()) {
				return 'campaignEnd';
			} else if (!Util::isCampaignStarted()) {
				return 'campaignNotStarted';
			}
		}
		return parent::beforeDispatch($action);
	}

	public function postDispatch ($action, $viewModel) {
		if (!Util::isCampaignTime() && !Util::isTestUser(false, 'testers.conf.php')) {
			return parent::postDispatch($action, $viewModel);
		}
		Util::setIclpCookie('prev_page', $action);
		if ($viewModel instanceof ViewModel) {
			$data = $viewModel->getData();
			if ((empty($data) || empty($data['nowechat'])) && isset($_GET['share_from'])) {
				$from = explode('_', $_GET['share_from']);
				$from_wids = array();
				if (!empty($from)) {
					foreach ($from as $uid) {
						$user = User::findByID($uid);
						if ($user) {
							$from_wids[] = $user->openid;
						}
					}
				}
				Logger::logWxShareBack(Wechat::getOpenID(), Wechat::getCurrentUser()->openid, $from_wids, $_GET['share_page'], $_GET['share_type']);
			}
		}
		return parent::postDispatch($action, $viewModel);
	}

	public function indexAction () {
		$wxConfig = Wechat::getConfig(Wechat::getOpenID());
		$user = Wechat::getCurrentUser();

		$isMember = $user->isMember();

		$newUser = Wechat::getMemberDetail($user->openid, true);
		$subscribed = $newUser->subscribe;

		return new ViewModel('index', array('page' => 'index', 'user' => $user, 'subscribed' => $subscribed, 'isMember' => $isMember, 'wx_config' => $wxConfig));
	}

	public function termsAction () {
		return new ViewModel('terms', array('page' => 'terms', 'nowechat' => 1));
	}

	public function gameAction () {
		$wxConfig = Wechat::getConfig(Wechat::getOpenID());
		$user = Wechat::getCurrentUser();
		return new ViewModel('game', array('page' => 'game', 'user' => $user, 'wx_config' => $wxConfig));
	}

	public function smsLandingAction () {
		return new ViewModel('sms_landing', array('nowechat' => 1));
	}

	public function bindingFlowAction () {
		return new ViewModel('binding_flow', array('nowechat' => 1));
	}

	public function campaignEndAction () {
		return new ViewModel('campaign_end', array('nowechat' => 1));
	}

	public function campaignNotStartedAction () {
		return new ViewModel('campaign_not_started', array('nowechat' => 1));
	}

}
