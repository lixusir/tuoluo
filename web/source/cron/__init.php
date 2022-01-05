<?php
/**
 * [WECHAT]Copyright (c) 2014 HaiSheng.Com
 
 */
if($action != 'entry') {
	define('FRAME', 'setting');
	$frames = buildframes(array(FRAME));
	$frames = $frames[FRAME];
}
