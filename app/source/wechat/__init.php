<?php
/**
 * [HaiSheng System] Copyright (c) 2014 HaiShengCMS.COM

 */
defined('IN_IA') or exit('Access Denied');
checkauth();
load()->model('coupon');
load()->classs('coupon');
if(empty($_W['acid'])) {
	message('acid不存在', referer(), 'error');
}



