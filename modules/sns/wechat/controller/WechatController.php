<?php

namespace modules\sns\wechat\controller;

use \modules\core\common\helper\DB;
use \modules\core\common\helper\Util;
use \modules\core\common\model\JsonModel;
use \modules\core\common\model\XmlModel;
use \modules\sns\wechat\helper\Wechat;
use \modules\sns\wechat\helper\Logger;
use \modules\sns\wechat\model\User;
use \modules\core\common\model\ViewModel;
use \modules\core\common\model\ResultObj;
use \modules\sns\wechat\model\message\TemplateMessage;
use \modules\sns\wechat\model\message_handler\WechatEventMessageHandler;

class WechatController extends \modules\core\common\controller\IclpController {

	public function processRequestAction () {
		// check signature:
		if ($_GET['action'] == 'checkSignature' && !empty($_GET['echostr'])) {
			if (Wechat::checkSignature()) {
				echo $_GET['echostr'];
				exit;
			} else {
				die("Invalid Signature");
			}
		}

		$xml = XmlModel::parseSimpleXmlString($HTTP_RAW_POST_DATA);
		$arrData = $xml->getData();
		$open_id = $arrData['ToUserName'];
		$wid = $arrData['FromUserName'];
		Logger::logWxMessage($open_id, $wid, $HTTP_RAW_POST_DATA);
		switch ($arrData['MsgType']) {
			case 'event':
				$handler = new WechatEventMessageHandler();
				$handler->processMessage($arrData);
				break;
		}
	}

	public function wechatAuthCallbackAction () {
		if ($_GET['code'] && $_GET['state'] == Wechat::getAuthState()) {

			$result = Util::request('https://api.weixin.qq.com/sns/oauth2/access_token?appid='.Wechat::getAppID().'&secret='.Wechat::getAppSecret().'&code='.$_GET['code'].'&grant_type=authorization_code');
			if (is_array($result) && empty($result['errcode'])) {

				$accessToken = $result['access_token'];
				$result2 = Util::request('https://api.weixin.qq.com/sns/userinfo?access_token='.$accessToken.'&openid='.$result['openid'].'&lang=zh_CN');

				if (is_array($result2) && empty($result2['errcode'])) {
					$openid = mb_convert_encoding($result['openid'], 'UTF-8');

					$user = Wechat::getMemberDetail($openid);
					if (!$user || !(intval($user->subscribe))) {
						$user = new User($openid, false);
						$user->account_open_id = Wechat::getOpenID();
						$user->nickname = $result2['nickname'];
						$user->sex = $result2['sex'];
						$user->province = $result2['province'];
						$user->city = $result2['city'];
						$user->country = $result2['country'];
						$user->headimgurl = $result2['headimgurl'];
						$memberResult = Util::post(
							'http://'.$_SERVER['SERVER_NAME'].'/home/vip/get_member_info.php',
							array('auth_key' => 'P4J(&Y6ijO*Ij@p8o', 'openid' => $openid)
						);
						if ($memberResult && $memberResult['success']) {
							$user->member_id = $memberResult['data']['customerdata']['customerid'];
						}

						$saveResult = $user->save();
						$subscribe_time = 0;
					} else {
						$saveResult = true;
						$subscribe_time = $user->subscribe_time;
					}

					Logger::logOAuth(Wechat::getOpenID(), $openid, $subscribe_time);

					Util::setIclpCookie('wx_auth_openid', $user->openid);
					Util::setIclpCookie('cs_open_id', $user->openid);

					if ($saveResult) {
						$redirectUrl = $_GET['iclp_redirect'];
						if ($redirectUrl && strpos($redirectUrl, 'wechatAuthCallback') === false) {
							header('Location: '.$redirectUrl);
						} else {
							die('You are stopped from a redirecting loop.');
						}
					} else {
						die('Update User Info Failed.');
					}
				} else {
					Util::prt($result2);
				}
			} else {
				Util::prt($result);
			}
		} else {
			die('Invalid Request.');
		}
	}

	public function qrPageAction () {
		$view = new ViewModel('qr', array('__TITLE__' => 'QR Code 管理', 'environment' => Util::getEnvironment()));
		$view->platform = 'admin';
		return $view;
	}

	public function qrAction () {
		// error_reporting(0);
		$scenes = array();
		$zipName = '';
		$bg = false;
		$imgBg = null;
		$destX = 0;
		$destY = 0;
		$destWidth = 0;
		$destHeight = 0;
		$bgWidth = 0;
		$bgHeight = 0;
		$bgColor = null;
		$logo = false;
		$logoWidth = 0;
		$logoHeight = 0;
		$logoDestWidth = 0;
		$logoDestHeight = 0;
		$imgLogo = null;
		$text = array();

		if (!empty($_GET['recreate'])) {
			$files1 = glob(APP_DIR.DS.'tmp'.DS.'*.zip');
			$files2 = glob(APP_DIR.DS.'tmp'.DS.'*.jpg');
			if (!empty($files1)) {
				foreach ($files1 as $f) {
					unlink($f);
				}
			}
			if (!empty($files2)) {
				foreach ($files2 as $f) {
					unlink($f);
				}
			}
		}

		if (!empty($_GET['scene'])) {
			error_reporting(0);
			$scene = $_GET['scene'];
			$name = $_GET['scene'];
			if (!empty($_GET['name'])) {
				$name = $_GET['name'];
			}
			if (!empty($_GET['bg'])) {
				$bg = APP_DIR.DS.'uploads'.DS.$_GET['bg'];
				if (!file_exists($bg)) {
					$bg = false;
				}
			}
			if (!empty($_GET['logo'])) {
				$logo = APP_DIR.DS.'uploads'.DS.$_GET['logo'];
				if (!file_exists($logo)) {
					$logo = false;
				}
			}
			$qrImage = Wechat::getQRCode($scene);
			$imgData = file_get_contents($qrImage);
			header('Content-Type: image/jpeg');
			header('Content-Length: '.strlen($imgData));
			echo $imgData;
			exit;
		} else if (!empty($_GET['list'])) {
			$file = APP_DIR.DS.'config'.DS.Util::getEnvironment().DS.$_GET['list'].'.conf.php';

			if (file_exists($file)) {
				$zipName = $_GET['list'];
				$conf = include $file;

				if (is_array($conf)) {
					$bgConf = $conf['bg'];
					$sceneConf = $conf['scene_list'];

					if (is_array($bgConf)) {

						// open zip file:
						$zipPath = APP_DIR.DS.'tmp'.DS.$zipName.'.zip';
						$archive = new \ZipArchive();
						$archive->open($zipPath, \ZipArchive::OVERWRITE);

						// iterate though bg configs
						foreach ($bgConf as $type => $bg) {
							$imgBg = null;
							$imgLogo = null;
							$bgName = $bg['file'];
							$bgMime = $bg['mime'];
							$bgDestWidth = $bg['width'];
							$bgDestHeight = $bg['height'];
							$qrConf = $bg['qr'];
							$bgTextConf = $bg['text'];

							// get real background file path:
							if (!empty($bgName)) {
								$bgName = APP_DIR.DS.'uploads'.DS.$bgName;
								if (!file_exists($bgName)) {
									$bgName = false;
								}
							}

							// qr conf
							$qrX = $qrConf['x'];
							$qrY = $qrConf['y'];
							$qrDestWidth = $qrConf['width'];
							$qrDestHeight = $qrConf['height'];

							// logo conf
							$logoConf = $qrConf['logo'];
							if (is_array($logoConf)) {
								$logoName = APP_DIR.DS.'uploads'.DS.$logoConf['file'];
								$logoDestWidth = $logoConf['width'];
								$logoDestHeight = $logoConf['height'];
								$logoMime = $logoConf['mime'];

								switch ($logoMime) {
									case 'png':
										$imgLogo = imagecreatefrompng($logoName);
										break;
									default:
										$imgLogo = imagecreatefromjpeg($logoName);
										break;
								}
								$logoOriWidth = imagesx($imgLogo);
								$logoOriHeight = imagesy($imgLogo);
							}

							if ($bgName) {
								$imgBgTmp = null;
								switch ($bgMime) {
									case 'png':
										$imgBgTmp = imagecreatefrompng($bgName);
										break;
									default:
										$imgBgTmp = imagecreatefromjpeg($bgName);
										break;
								}
								$bgOriWidth = imagesx($imgBgTmp);
								$bgOriHeight = imagesy($imgBgTmp);
								$imgBg = imagecreatetruecolor($bgDestWidth, $bgDestHeight);
								$bgColor = imagecolorallocate($imgBg, 242, 243, 229);
								imagecopyresampled($imgBg, $imgBgTmp, 0, 0, 0, 0, $bgDestWidth, $bgDestHeight, $bgOriWidth, $bgOriHeight);
								imagedestroy($imgBgTmp);
								unset($imgBgTmp);
							}

							foreach ($sceneConf as $name => $scene) {
								$qrImage = Wechat::getQRCode($scene);
								if ($qrImage) {
									$fileName = APP_DIR.DS.'tmp'.DS.$name.'_'.$type.'.jpg';
									$fileCompositeName = APP_DIR.DS.'tmp'.DS.$name.'_'.$type.'_composite.jpg';
									if (!file_exists($fileName) || !empty($_GET['recreate'])) {
										file_put_contents($fileName, file_get_contents($qrImage));
									}

									if (!file_exists($fileCompositeName) || !empty($_GET['recreate'])) {
										copy($fileName, $fileCompositeName);
									}

									$img = null;
									$qrOriWidth = false;
									$qrOriHeight = false;
									if ($imgLogo) {
										$img = imagecreatefromjpeg($fileCompositeName);
										$qrOriWidth = imagesx($img);
										$qrOriHeight = imagesy($img);
										$logoX = ($qrOriWidth - $logoDestWidth) / 2;
										$logoY = ($qrOriHeight - $logoDestHeight) / 2;
										imagecopyresampled($img, $imgLogo, $logoX, $logoY, 0, 0, $logoDestWidth, $logoDestHeight, $logoOriWidth, $logoOriHeight);
									}
									if ($imgBg) {
										$img = $img ? $img : imagecreatefromjpeg($fileCompositeName);
										$qrOriWidth = $qrOriWidth ? $qrOriWidth : imagesx($img);
										$qrOriHeight = $qrOriHeight ? $qrOriHeight : imagesy($img);
										imagecopyresampled($imgBg, $img, $qrX, $qrY, 0, 0, $qrDestWidth, $qrDestHeight, $qrOriWidth, $qrOriHeight);
									}
									if ($imgBg) {
										if (is_array($bgTextConf)) {
											foreach ($bgTextConf as $t) {
												if (empty($t['color'])) {
													$t['color'] = '#000';
												}
												if (empty($t['font-family'])) {
													$t['font-family'] = 'arial';
												}
												$color = substr($t['color'], 1);
												$colors = array();
												if (strlen($color) == 3) {
													$colors['r'] = hexdec(str_repeat(substr($color, 0, 1), 2));
													$colors['g'] = hexdec(str_repeat(substr($color, 1, 1), 2));
													$colors['b'] = hexdec(str_repeat(substr($color, 2, 1), 2));
												} else if (strlen($color) == 6) {
													$colors['r'] = hexdec(substr($color, 0, 2));
													$colors['g'] = hexdec(substr($color, 2, 2));
													$colors['b'] = hexdec(substr($color, 4, 2));
												}
												$fontColor = imagecolorallocate($imgBg, $colors['r'], $colors['g'], $colors['b']);

												$txt = $t['text'];
												$txt = str_replace('{scene_list.key}', $name, $txt);

												imagefilledrectangle($imgBg, $t['x'] - 10, $t['y'] - 30, $t['x'] + 200, $t['y'] + 30, $bgColor);
												// imagestring($imgBg, 5, $t['x'], $t['y'], $txt, $fontColor);
												imagettftext($imgBg, $t['font-size'], 0, $t['x'], $t['y'], $fontColor, APP_DIR.DS.'uploads'.DS.'coolvetica_rg.ttf', $txt);
											}
										}
										imagejpeg($imgBg, $fileCompositeName);
									} else if ($imgLogo) {
										$result = imagejpeg($img, $fileCompositeName);
									}
									$archive->addFile($fileCompositeName, $type.'/'.$name.'.jpg');
									imagedestroy($img);
									unset($img);
								}
							}
							imagedestroy($imgBg);
						}
						$archive->close();
					}

					header('Content-Type: application/zip, application/octet-stream');
					header('Content-Disposition: attachment; filename='.$zipName.'.zip');
					header('Content-Length: '.filesize($zipPath));
					echo file_get_contents($zipPath);
					exit;
				}
			}
			die('Invalid qr config file');
		}
	}

	public function shortURLAction () {
		$view = new ViewModel('short_url', array('__TITLE__' => '短链接管理', 'environment' => Util::getEnvironment()));
		$view->platform = 'admin';
		return $view;
	}

	public function generateShortURLAction () {
		$result = new ResultObj(false);
		$url = $_POST['url'];
		if (empty($url)) {
			$result->setMessage('Invalid input long URL');
			return new JsonModel($result->toArray());
		}
		$res = Wechat::generateShortUrl($url);
		if ($res) {
			$url2 = Wechat::generateShortUrl('http://'.HOST_NAME.'/common/?ctrl=api&action=trackShortURL&url='.rawurlencode($res));
			if ($url2) {
				DB::exec("UPDATE `short_url` SET `track_url` = ".DB::quote($res)." WHERE `short_url` = ".DB::quote($url2), CORE_DB_CONN);
				$result->setSuccess(true);
				$result->setData(array('long_url'=>$url, 'short_url'=>$url2));
			} else {
				$result->setMessage('API error');
			}
		} else {
			$result->setMessage('API error');
		}
		return $result;
	}

	public function createCouponShelfAction () {
		$result = new ResultObj(false);
		$name = $_POST['name'];
		$bannerImg = $_POST['bannerImg'];
		$pageTitle = $_POST['pageTitle'];
		$couponList = $_POST['couponList'];
		$res = Wechat::createCouponShelf($name, $bannerImg, $pageTitle, $couponList);
		if ($res) {
			$result->setSuccess(true);
			$result->setData($res);
		}
		return $result;
	}

	public function getCouponShelvesAction () {
		$result = new ResultObj(false);
		$res = DB::fetchAll(
			"SELECT `name`, `coupon_1_id` AS `first_coupon`, `url`, `page_id`, `banner`, `title`, `coupon_1_icon` AS `first_coupon_icon` FROM `wechat_coupon_landing_page` WHERE `open_id` = ".DB::quote(Wechat::getOpenID()), CORE_DB_CONN
		);
		if ($res) {
			$result->setSuccess(true);
			$result->setData($res);
		}
		return $result;
	}

	public function createCouponShelfPageAction () {
		?>
		<html>
		<head>
			<title>卡券领取页管理</title>
			<style>
				div {margin: 10px;}
				input {font-size: 13px; padding: 5px;}
				input:not([type]) {width: 80%;}
				.pic {width: 130px; height: 80px; background-repeat: no-repeat; background-size: contain; overflow: auto; background-position: center center;}
				.url {width: 200px; overflow: auto;}
				table, td, th {border: 1px solid #000000; border-collapse: collapse; padding: 0; border-spacing: 0; cell-spacing: 0;}
			</style>
		</head>
		<body>
			<div><input class="name" placeholder="Name" /></div>
			<div><input class="banner" placeholder="Banner" /></div>
			<div><input class="title" placeholder="Title" /></div>
			<div><input class="id" placeholder="Card ID" /></div>
			<div><input class="icon" placeholder="Card Icon" /></div>
			<div><input type="button" class="reset" value="重置" />&nbsp;<input type="button" class="submit" value="提交" /></div>
			<div><pre class="result"></pre></div>
			<div><table class="existing"></table></div>
			<script type="text/javascript" src="js/lib/jquery-1.11.3.min.js"></script>
			<script type="text/javascript">
				$(function () {
					$.post(
						'index.php?ctrl=api&action=getCouponShelves',
						{},
						function (resp) {
							$('.existing').empty();
							var row = $('<tr><th>名称</th><th>页面ID</th><th>卡券ID</th><th>页面标题</th><th>封面图</th><th>页面地址</th><th>卡券图标</th></tr>');
							$('.existing').append(row);
							if (resp.success) {
								for (var i in resp.data) {
									var data = resp.data[i];
									var row = $('<tr>'
										+ '<td><div>' + data.name + '</div></td>'
										+ '<td><div>' + data.page_id + '</div></td>'
										+ '<td><div>' + data.first_coupon + '</div></td>'
										+ '<td><div>' + data.title + '</div></td>'
										+ '<td><div class="pic" style="background-image:url(' + data.banner + ');">' + data.banner + '</div></td>'
										+ '<td><div class="url">' + data.url + '</div></td>'
										+ '<td><div class="pic" style="background-image:url(' + data.first_coupon_icon + ');">' + data.first_coupon_icon + '</div></td>'
									+ '</tr>');
									$('.existing').append(row);
								}
							}
						}
					);
					$('.submit').on('click', function () {
						var name = $('.name').val();
						var banner = $('.banner').val();
						var title = $('.title').val();
						var id = $('.id').val();
						var icon = $('.icon').val();
						if (!name || !banner || !title || !id || !icon) {
							alert('输入错误，请重新检查！');
							return false;
						}
						$.post(
							'index.php?ctrl=api&action=createCouponShelf',
							{
								name: name,
								bannerImg: banner,
								pageTitle: title,
								couponList: [
									{
										id: id,
										icon: icon
									}
								]
							},
							function (resp) {
								$('.result').text(JSON.stringify(resp));
							}
						);
					});
				});
			</script>
		</body>
		</html>
		<?php
	}

	public function testTemplateMessageAction () {
		$templateID = $_REQUEST['template'];
		$users = $_POST['users'];

		unset($_POST['template']);
		unset($_POST['users']);
		$entries = $_POST;

		$template = new TemplateMessage('', $templateID, '');
		foreach ($entries as $key => $value) {
			$template->addEntry($key, $value);
		}
		if (is_array($users)) {
			foreach ($users as $wid) {
				$template->setTo($wid);
				Wechat::sendTemplateMessage($template);
			}
		}
		$result = new ResultObj(true, 0);
		return $result;
	}

	public function testTemplateMessagePageAction () {
		?>
		<html>
		<head>
			<title>测试模板消息</title>
		</head>
		<body>
			<label>选择用户</label>
			<br />
			<select multiple class="users">
				<option value="ouJjZjgso1nBavalzJxGH19vY2Uk">Joey</option>
				<option value="ouJjZjqJa7UkisDoUDK_YNg-oshw">Jeff</option>
				<option value="ouJjZjmp1mJTdS-begAWMytCRae8">Miranda</option>
				<option value="ouJjZjtGCA-lfflgDP7s60TLBXlM">Cathy</option>
				<option value="ouJjZjpv0tCgG0HtNV7aAjlkF2iY">Jackie</option>
			</select>
			<select class="template_id">
				<option value="">选择模板</option>
				<option value="rVwGSP4P6zBiokehNKDLSkSD6THBmVVhyi1zdEmDt8Q">成为会员通知</option>
				<option value="vb2kuUBWis-SIjleoiSjBJa8I96cZGknkaPAb9rCBSw">绑定会员通知</option>
				<option value="G_VH8CUWK8lkI5OU7Z8IsGuU45fSv8s5htPGPny_RL4">注册成功通知</option>
			</select>
			<br />
			<br />
			<div class="template_data"></div>
			<br />
			<pre class="member_info">
			</pre>
			<br />
			<input class="btn_send" type="button" value="send" />

			<script type="text/javascript" src="js/lib/jquery-1.11.3.min.js"></script>
			<script type="text/javascript">
				$('.template_id').on('change', function () {
					$('.template_data').empty();
					switch ($(this).val()) {
						case 'rVwGSP4P6zBiokehNKDLSkSD6THBmVVhyi1zdEmDt8Q':
							$('.template_data').append('<input class="entry" data-key="first" placeholder="first" /><br />');
							$('.template_data').append('<input class="entry" data-key="keyword1" placeholder="会员编号" /><br />');
							$('.template_data').append('<input class="entry" data-key="keyword2" placeholder="有效期至" /><br />');
							$('.template_data').append('<input class="entry" data-key="remark" placeholder="remark" /><br />');
							break;
						case 'vb2kuUBWis-SIjleoiSjBJa8I96cZGknkaPAb9rCBSw':
							$('.template_data').append('<input class="entry" data-key="first" placeholder="first" /><br />');
							$('.template_data').append('<input class="entry" data-key="keyword1" placeholder="会员号" /><br />');
							$('.template_data').append('<input class="entry" data-key="keyword2" placeholder="时间" /><br />');
							$('.template_data').append('<input class="entry" data-key="remark" placeholder="remark" /><br />');
							break;
						case 'G_VH8CUWK8lkI5OU7Z8IsGuU45fSv8s5htPGPny_RL4':
							$('.template_data').append('<input class="entry" data-key="first" placeholder="first" /><br />');
							$('.template_data').append('<input class="entry" data-key="keyword1" placeholder="会员姓名" /><br />');
							$('.template_data').append('<input class="entry" data-key="keyword2" placeholder="绑定手机号" /><br />');
							$('.template_data').append('<input class="entry" data-key="remark" placeholder="remark" /><br />');
							break;
					}
				});

				$('.users').on('change', function () {
					$.post(
						'',
						{},
						function () {

						}
					);
				});

				$('.btn_send').on('click', function () {
					var template = $('.template_id').val();
					var users = $('.users').val();
					if (!template || !users) {
						alert('请选择');
						return false;
					}
					var postData = {
						template: template,
						users: users
					};
					$('.entry').each(function () {
						postData[$(this).attr('data-key')] = $(this).val();
					});
					$.post(
						'index.php?ctrl=api&action=testTemplateMessage',
						postData,
						function (resp) {
							if (resp.success) {
								alert('Done');
							}
						}
					);
				});
			</script>
		</body>
		</html>
		<?php
	}

}
