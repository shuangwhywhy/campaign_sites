<?php

namespace modules\sns\wechat\model;

abstract class WechatMessageHandler {

	public abstract function processMessage ($message, $handler=null);

}
