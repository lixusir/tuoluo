<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
class Ranking_WxShopPage extends MobileLoginPage
{
	public function main() 
	{
		global $_W;
		
		global $_GPC;
		

		//更新下级人数
		$member = m('member')->getMember($_W['openid']);


		// m('member')->getmylower(3996,$arr);

		// echo '<pre>';
		//     print_r($arr);
		// echo '</pre>';
		// exit;
		if(empty($member['mobile'])) {

			// bind			
			// echo "<><>":
			echo '<script  type="text/javascript">alert("请去绑定手机号码!");location.href = "index.php?i=96&c=entry&m=wx_shop&do=mobile&r=member.bind";</script>';


		}


		if(empty($member['yqm'])) {
			while (1) {
				$yqm = m('member')->getYqm(6);
				$count = pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_member') . ' where uniacid=:uniacid and yqm=:yqm',array(':uniacid'=>$_W['uniacid'],'yqm'=>$yqm));
				if($count <= 0) {
					break;
				}
			}
			pdo_update('wx_shop_member',array('yqm'=>$yqm),array('id'=>$member['id']));

		}

		$phb = pdo_fetchall('select id,openid,mobile,nickname,avatar,sum(xia_num+xn_xia) as xias from '  .tablename('wx_shop_member') . ' where uniacid=:uniacid group by openid order by xias desc',array(':uniacid'=>$_W['uniacid']));

		$num = 0;
		$pm = 0;

		$arr = array();
		foreach ($phb as $key => $value) {
			if($value['id'] == $member['id']) {
				$pm = $key+1;
				$num = $value['xias'];
			}

			if($key < 30) {
				$arr[$key+1] = $value;
			}
		}

		include $this->template();
	}
}
?>