<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
class Recharge_WxShopPage extends WebPage 
{
	public function main() 
	{
		global $_W;
		global $_GPC;
		$type = trim($_GPC['type']);
		if (!(cv('finance.recharge.' . $type))) 
		{
			$this->message('你没有相应的权限查看');
		}
		$id = intval($_GPC['id']);
		$profile = m('member')->getMember($id, true);
		if ($_W['ispost']) 
		{
			// $typestr = (($type == 'credit1' ? '积分' : '余额'));


            if($type =='jinbi') {
            	$typestr = '金币';
            }else if($type=='credit_red'){
            	$typestr = '红包';
            }else if($type=='credit_b'){
            	$typestr = '彩蛋币';
            }else if($type=='dzp'){
            	$typestr = '大转盘券';
            }else if($type=='qd_num'){
            	$typestr = '敲蛋次数';
            }else if($type=='yqq'){
            	$typestr = '邀请券';
            }

			$num = floatval($_GPC['num']);
			$remark = trim($_GPC['remark']);
			if ($num <= 0) 
			{
				show_json(0, array('message' => '请填写大于0的数字!'));
			}
			$changetype = intval($_GPC['changetype']);
			if ($changetype == 2) 
			{
				$num -= $profile[$type];
			}
			else if ($changetype == 1) 
			{
				$num = -$num;
			}
			m('member')->setCredit($profile['openid'], $type, $num, array($_W['uid'], '后台会员充值' . $typestr . ' ' . $remark));
			$changetype = 0;
			$changenum = 0;
			if (0 <= $num) 
			{
				$changetype = 0;
				$changenum = $num;
			}
			else 
			{
				$changetype = 1;
				$changenum = -$num;
			}
			// if ($type == 'credit1') 
			// {
			// 	m('notice')->sendMemberPointChange($profile['openid'], $changenum, $changetype);
			// }
			// if ($type == 'credit2') 
			// {
				$set = m('common')->getSysset('shop');
				$logno = m('common')->createNO('member_log', 'logno', 'RC');
				$data = array('openid' => $profile['openid'], 'logno' => $logno, 'uniacid' => $_W['uniacid'], 'type' => '0', 'createtime' => TIMESTAMP, 'status' => '1', 'title' => $set['name'] . '会员充值'.$typestr, 'money' => $num, 'remark' => $remark, 'rechargetype' => 'system');
				pdo_insert('wx_shop_member_log', $data);
				$logid = pdo_insertid();
				m('notice')->sendMemberLogMessage($logid, 0, true);
			// }
			plog('finance.recharge.' . $type, '充值' . $typestr . ': ' . $_GPC['num'] . ' <br/>会员信息: ID: ' . $profile['id'] . ' /  ' . $profile['openid'] . '/' . $profile['nickname'] . '/' . $profile['realname'] . '/' . $profile['mobile']);
			show_json(1, array('url' => referer()));
		}
		include $this->template();
	}
}
?>