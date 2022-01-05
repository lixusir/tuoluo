<?php
require '../../../../framework/bootstrap.inc.php';
require '../../../../addons/wx_shop/defines.php';
require '../../../../addons/wx_shop/core/inc/functions.php';
$notify = $_POST;
$result = p('grant')->verifyNotify($_POST);
$order = pdo_fetch('select * from ' . tablename('wx_shop_system_plugingrant_order') . ' where logno = \'' . $notify['out_trade_no'] . '\'');
if (($notify['trade_status'] == 'TRADE_SUCCESS') && $result) 
{
	pdo_update('wx_shop_system_plugingrant_order', array('paytime' => time(), 'paystatus' => 1), array('logno' => $notify['out_trade_no']));
	$plugind = explode(',', $order['pluginid']);
	$data = array('logno' => $order['logno'], 'uniacid' => $order['uniacid'], 'type' => 'pay', 'month' => $order['month'], 'createtime' => time());
	foreach ($plugind as $key => $value ) 
	{
		$plugin = pdo_fetch('select `identity` from ' . tablename('wx_shop_plugin') . ' where id = ' . $value . ' ');
		$data['identity'] = $plugin['identity'];
		$data['pluginid'] = $value;
		$log = pdo_fetchcolumn('select count(1) from ' . tablename('wx_shop_system_plugingrant_log') . ' where logno = \'' . $notify['out_trade_no'] . '\' and pluginid = ' . $value . ' ');
		if (!($log)) 
		{
			pdo_query('update ' . tablename('wx_shop_system_plugingrant_plugin') . ' set sales = sales + 1 where pluginid = ' . $value . ' ');
			pdo_insert('wx_shop_system_plugingrant_log', $data);
			$id = pdo_insertid();
			if (p('grant')) 
			{
				p('grant')->pluginGrant($id);
			}
		}
	}
	echo 'SUCCESS';
}
?>