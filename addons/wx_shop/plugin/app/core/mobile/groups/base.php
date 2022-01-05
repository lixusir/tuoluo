<?php
global $_W;
global $_GPC;


if(substr_count($_GPC['openid'], 'sns_wa_') > 1){
	$_GPC['openid'] = $_W['openid'] = 'sns_wa_'.str_replace('sns_wa_', '', $_GPC['openid']);
}

// show_json(1,array('w'=>$_W['openid'], 'g'=>$_GPC['openid']));