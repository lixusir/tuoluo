<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

$isnew = false;
require_once 'core/model_task.php';
$isnew = new Task2Model();
$isnew = $isnew->isnew();
$task_arr = '';
if ($isnew) 
{
	$task_arr =  array( 
		array('title' => '任务概述', 'route' => 'task.main'), 
		array('title' => '任务管理', 'route' => 'task.tasklist'), 
		array('title' => '任务记录', 'route' => 'task.record'), 
		array('title' => '奖励记录', 'route' => 'task.reward'), 
		array('title' => '消息通知', 'route' => 'task.notice'), 
		array('title' => '入口设置', 'route' => 'task.setting')
			 );
}else{
	$task_arr =  array( 
		array('title' => '海报任务', 'route' => ''), 
		array('title' => '单次任务', 'route' => 'task.extension.single'), 
		array('title' => '周期任务', 'route' => 'task.extension.repeat'),  
		array('title' => '通知设置', 'route' => 'task.default'), 
		array('title' => '入口设置', 'route' => 'task.default.setstart')
			 );
}


return array(
	'version' => '1.0',
	'id'      => 'lottery',
	'name'    => '游戏系统',
	'v3'      => true,
	'menu'    => array(
		'title'     => '页面',
		'plugincom' => 1,
		'icon'      => 'page',
		'items'     => array(
			array(
				'title' => '游戏中心',
				'items' => array(
					array('title' => '活动管理', 'route' => ''),
					array('title' => '说明&通知设置', 'route' => 'setting.setlottery'),
					array('title' => '入口设置', 'route' => 'setting.setstart')
					)
			),
			//兑换中心
			array(
				'title' => '兑换中心',
				'items' => array(
					array('title' => '商品兑换', 'route' => 'exchange.goods', 
						'extends' => array('exchange.exchange.goods.nostart', 'exchange.goods.end', 'exchange.goods.setting', 'exchange.goods.dno', 'exchange.goods.dyet', 'exchange.goods.dend')
					),
					array('title' => '余额兑换', 'route' => 'exchange.balance', 'extends' => array('exchange.balance.nostart', 'exchange.balance.end', 'exchange.balance.setting', 'exchange.balance.dno', 'exchange.balance.dyet', 'exchange.balance.dend')),
					array('title' => '红包兑换', 'route' => 'exchange.redpacket', 'extends' => array('exchange.redpacket.nostart', 'exchange.redpacket.end', 'exchange.redpacket.setting', 'exchange.redpacket.dno', 'exchange.redpacket.dyet', 'exchange.redpacket.dend')), 
					array('title' => '积分兑换', 'route' => 'exchange.score', 'extends' => array('exchange.score.nostart', 'exchange.score.end', 'exchange.score.setting', 'exchange.score.dno', 'exchange.score.dyet', 'exchange.score.dend')), 
					array('title' => '优惠券兑换', 'route' => 'exchange.coupon', 'extends' => array('exchange.coupon.nostart', 'exchange.coupon.end', 'exchange.coupon.setting', 'exchange.coupon.dno', 'exchange.coupon.dyet', 'exchange.coupon.dend')), 

					array('title' => '组合兑换', 'route' => 'exchange.group', 'extends' => array('exchange.group.nostart', 'exchange.group.end', 'exchange.group.setting', 'exchange.group.dno', 'exchange.group.dyet', 'exchange.group.dend')),
					array('title' => '待发货', 'route' => 'exchange.record.daifahuo'), 
					array('title' => '待收货', 'route' => 'exchange.record.daishouhuo'), 
					array('title' => '待付款', 'route' => 'exchange.record.daifukuan'), 
					array('title' => '已关闭', 'route' => 'exchange.record.yiguanbi'), 
					array('title' => '已完成', 'route' => 'exchange.record.yiwancheng'), 
					array('title' => '全部订单', 'route' => 'exchange.record'),
					array('title' => '兑换记录', 'route' => 'exchange.history'), 
					array('title' => '文件管理', 'route' => 'exchange.setting.download'), 
					array('title' => '其他设置', 'route' => 'exchange.setting.other')
					)
				),
			array(
				'title' => '快速购买',
				'items' => array(
					array('title' => '全部页面', 'route' => 'quick.pages', 'route_must' => 1),
					array('title' => '新建页面', 'route' => 'quick.pages.add'),
					array('title' => '幻灯片', 'route' => 'quick.adv')
					)
				),
			array(
				'title' => '任务中心',
				'items' => $task_arr
				),
			)
		)
	);

?>
