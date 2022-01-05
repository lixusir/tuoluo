<?php
error_reporting(0);
require '../../../../../framework/bootstrap.inc.php';
require '../../../../../addons/wx_shop/defines.php';
require '../../../../../addons/wx_shop/core/inc/functions.php';
global $_W;
global $_GPC;
ignore_user_abort();
set_time_limit(0);



$sets = pdo_fetchall('select uniacid from ' . tablename('wx_shop_sysset'));
// echo '<pre>';
//     print_r($sets);
// echo '</pre>';
foreach ($sets as $set ) 
{
	$_W['uniacid'] = $set['uniacid'];
	if (empty($_W['uniacid'])) 
	{
		continue;
	}

	$data = m('common')->getPluginset('commission');


	if($data['k_set'] == 1) {

		$time = time();

		if($time >= $data['k_num']) {
			// echo 1;

			$res = pdo_fetchall('select id from ' .tablename('wx_shop_member') . ' where uniacid=:uniacid and isagent=0 and status=0 and agenttime=0',array(':uniacid'=>$_W['uniacid']));

			if($res) {
				foreach ($res as $key => $value) {
					pdo_update('wx_shop_member',array('isagent'=>1,'status'=>1,'agenttime'=>$time),array('id'=>$value['id']));
				}
			}

		}


	}
	// echo '<pre>';
	//     print_r($data['k_set']);
	// echo '</pre>';


}
?>