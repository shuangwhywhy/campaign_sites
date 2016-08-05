<?php

namespace modules\sns\wechat\model;

class Coupon extends \modules\core\common\model\DataModel {

	const DATE_TYPE_FIX_TIME_RANGE = 'DATE_TYPE_FIX_TIME_RANGE';
	const DATE_TYPE_FIX_TERM = 'DATE_TYPE_FIX_TERM';

	const CODE_TYPE_QRCODE = 'CODE_TYPE_QRCODE';
	const CODE_TYPE_BARCODE = 'CODE_TYPE_BARCODE';
	const CODE_TYPE_ONLY_QRCODE = 'CODE_TYPE_ONLY_QRCODE';
	const CODE_TYPE_TEXT = 'CODE_TYPE_TEXT';
	const CODE_TYPE_NONE = 'CODE_TYPE_NONE';

}
