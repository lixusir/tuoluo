<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
class Member_WxShopPage extends MobilePage
{
	public function main() 
	{
		//获取用户信息
		global $_W,$_GPC;
		$token = trim($_GPC['token']);
		$uid = m('game')->getuid($token);
		// $member = m('member')->getMember($uid);
		$member = pdo_fetch('select id,sn_id,yqm,mobile,credit_red,game_level,openid,gg_level,is_rz,nickname,avatar,is_sf_k,is_td_k,weixin,qq_name,province,city,area from ' . tablename('wx_shop_member') . ' where id=:id and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':id' => $uid));
		if(empty($member['sn_id'])) {
		
			$sn_id = m('game')->getSn_id();
			pdo_update('wx_shop_member',array('sn_id'=>$sn_id),array('id'=>$uid));
			$member['sn_id'] = $sn_id;
		}
		$member['is_sf_k'] = empty($member['is_sf_k'])?0:$member['is_sf_k'];
		$member['is_td_k'] = empty($member['is_td_k'])?0:$member['is_td_k'];
		if(empty($member)) {
			show_json_w(-1,null,'用户错误!');
		}
	
		//更新广告等级
		m('game')->setLevel($uid);
		
		m('game')->setBlevel($uid);
		//等级鸟
		// $member['game_level_name'] = pdo_fetchcolumn('select goodsname from ' . tablename('wx_shop_game_goods') . ' where uniacid=:uniacid and level=:level',array(':uniacid'=>$_W['uniacid'],'level'=>$member['game_level']));
		if($member['game_level'] == 38) {
			
			$zg_level = pdo_fetchcolumn('select goodsid from ' . tablename('wx_shop_game'.substr($member['id'],-1)) . ' where uniacid=:uniacid and uid=:uid and status=1 and  goodslevel=38 order by goodsid desc',array(':uniacid'=>$_W['uniacid'],':uid'=>$uid));
			if(empty($zg_level)) {
				$zg_level = 37;
			}
			$member['game_level_name'] = pdo_fetchcolumn('select g.goodsname from ' . tablename('wx_shop_game_goods') . ' g left join ' . tablename('wx_shop_game'.substr($member['id'], -1)) . ' gg on gg.goodsid=g.id where g.uniacid=:uniacid and g.id=:goodsid order by g.id desc',array(':uniacid'=>$_W['uniacid'],':goodsid'=>$zg_level));
		} else {
			$member['game_level_name'] = pdo_fetchcolumn('select goodsname from ' . tablename('wx_shop_game_goods') . ' where uniacid=:uniacid and level=:level',array(':uniacid'=>$_W['uniacid'],':level'=>$member['game_level']));
		}
		$member['gg_levels'] = pdo_fetch('select levelname from ' . tablename('wx_shop_game_level') . ' where uniacid=:uniacid and id=:id',array(':uniacid'=>$_W['uniacid'],':id'=>$member['gg_level']));
		//累计收益
		$lj_money = pdo_fetchcolumn('select sum(money) from ' . tablename('wx_shop_game_member_log') . ' where uniacid=:uniacid and uid=:uid and type!= 1 and status=3',array(':uniacid'=>$_W['uniacid'],':uid'=>$member['id']));
		if(empty($lj_money)) {
			$lj_money = 0;
		}
		//提现金额
		$tx_money = pdo_fetchcolumn('select sum(money) from ' . tablename('wx_shop_game_member_log') . ' where uniacid=:uniacid and uid=:uid and type=:type and status in(0,3)',array(':uniacid'=>$_W['uniacid'],':uid'=>$member['id'],':type'=>1));
		unset($member['uid']);
		if(empty($tx_money)) {
			$tx_money = 0;
		}
		//credit_red 账户积分余额
		//升级积分
		$red = pdo_fetch('select level,red_max from ' . tablename('wx_shop_game_goods') . ' where uniacid=:uniacid and level>:level and red_max!=0',array(':uniacid'=>$_W['uniacid'],':level'=>$member['game_level']));
		
		$red['cha'] = $red['level']-$member['game_level'];
		if($member['game_level'] == 38) {
			$red['cha'] = 0;
		}
		$red_is = pdo_fetch('select log.id,log.money,g.red_max from ' . tablename('wx_shop_game_member_log') . ' log left join ' .tablename('wx_shop_game_goods'). 'g on g.level=log.level where log.uniacid=:uniacid and log.uid=:uid and log.type=13 and log.status=2 order by log.level desc ',array(':uniacid'=>$_W['uniacid'],':uid'=>$uid));
		// echo "<pre>";
		// 	print_r($red_is);
		// echo "</pre>";
		$is_red = !empty($red_is)?1:0;
		$red['is_red'] = $is_red;
		$red['logid'] = $red_is['id'];
		
		$red['lq_red_max'] = $red_is['red_max'];
		// echo "<pre>";
		// 	print_r($red);
		// echo "</pre>";
		// echo '<pre>';
		//     print_r($_W);
		// echo '</pre>';
		// $member['lj'] = $_W['siteroot'] .'app/index.php?i=96&c=entry&m=wx_shop&do=mobile&r=account.register&yqm='.$member['yqm'];
		$set = pdo_fetch('select img_sq,xz_lj from ' . tablename('wx_shop_game_set') . ' where uniacid=:uniacid',array(':uniacid'=>$_W['uniacid']));
		$member['lj'] = $set['xz_lj'].'&yqm='.$member['yqm'];
		
		$member['img_sq'] = tomedia($set['img_sq']);
		//获取领取积分数据
		$lb_red = pdo_fetchall('select m.nickname,l.money from ' . tablename('wx_shop_game_member_log') . ' l left join '.tablename('wx_shop_member').' m on m.id=l.uid where l.uniacid=:uniacid and l.type=11 and l.status=3 order by l.createtime desc limit 50',array(':uniacid'=>$_W['uniacid']));
		foreach ($lb_red as &$val) {
			$val['content'] = '恭喜'.$val['nickname'].'领取'.$val['money'].'积分,积分正在路上';
		}
		unset($val);
		// echo "<pre>";
		// 	print_r($lb_red);
		// echo "</pre>";
		show_json_w(1,array('member'=>$member,'red'=>$red,'lj_money'=>$lj_money,'tx_money'=>$tx_money,'lb_red'=>$lb_red),'成功');
		// echo '<pre>';
		//     print_r($red);
		// echo '</pre>';
		// echo '<pre>';
		//     print_r($member);
		// echo '</pre>';
	}
	//更改昵称
	public function setName(){
		//获取用户信息
		global $_W,$_GPC;
		$token = trim($_GPC['token']);
		$uid = m('game')->getuid($token);
		$member = pdo_fetch('select id,nickname from ' . tablename('wx_shop_member') . ' where id=:id and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':id' => $uid));
		// $member = m('member')->getMember($uid);
		if(empty($member)) {
			show_json_w(-1,null,'用户错误!');
		}
		$nickname = trim($_GPC['nickname']);
		
		if($_W['ispost'] && !empty($nickname)) {
			pdo_update('wx_shop_member',array('nickname'=>$nickname),array('id'=>$uid));
			show_json_w(1,$nickname,'成功');
		}
		show_json_w(1,$member['nickname'],'成功');
	}
	public function chengshi(){
		global $_W,$_GPC;
		$token = trim($_GPC['token']);
		$uid = m('game')->getuid($token);
		$member = pdo_fetch('select id,agentid,aagenttype,aagentprovinces,aagentcitys,aagentareas,province,city,area,is_rz from ' . tablename('wx_shop_member') . ' where id=:id and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':id' => $uid));
		if(empty($member)) {
			show_json_w(-1,null,'用户错误!');
		}
		$list = array();
		$shifu =  pdo_fetch('select id,avatar,mobile,nickname,is_rz,province,city,area from ' . tablename('wx_shop_member') .'where uniacid=:uniacid and id=:id',array(':uniacid'=>$_W['uniacid'],':id'=>$member['agentid']));
		// $shi = null;
		// $qu = null;
		if($member['aagenttype'] == 0) {
			//普通用户
			
			//查询市区代理
		   	$aagentcitys = iserializer(array($member['province'].$member['city']));
				
		   	$shi = pdo_fetch('select id,nickname,avatar,mobile,province,city,aagenttype,is_rz from ' . tablename('wx_shop_member') . ' where uniacid=:uniacid and id<>:uid and aagentcitys=:aagentcitys and aagenttype=:aagenttype',array(':uniacid'=>$_W['uniacid'],':uid'=>$uid,':aagentcitys'=>$aagentcitys,':aagenttype'=>2));
		   	//查询区代理
		   	$aagentareas = iserializer(array($member['province'].$member['city'].$member['area']));
		   	$qu = pdo_fetch('select id,nickname,avatar,mobile,province,city,area,aagenttype,is_rz from ' . tablename('wx_shop_member') . ' where uniacid=:uniacid and id<>:uid and aagentareas=:aagentareas and aagenttype=:aagenttype',array(':uniacid'=>$_W['uniacid'],':uid'=>$uid,':aagentareas'=>$aagentareas,':aagenttype'=>3));
		} else if($member['aagenttype'] == 2) {
			//市代
			$shi = $member;
			//
			// $qu = array();
		} else if($member['aagenttype'] == 3) {
			//区代
			//查询市区代理
		   	$aagentcitys = iserializer(array($member['province'].$member['city']));
				
		   	$shi = pdo_fetch('select id,nickname,avatar,mobile,province,city,aagenttype,is_rz from ' . tablename('wx_shop_member') . ' where uniacid=:uniacid and id<>:uid and aagentcitys=:aagentcitys and aagenttype=:aagenttype',array(':uniacid'=>$_W['uniacid'],':uid'=>$uid,':aagentcitys'=>$aagentcitys,':aagenttype'=>2));
			$qu = $member;
		}
		if(!empty($shifu)) {
			$list[0] = $shifu;
		} else {
			$list[0] = null;
		}
		if(!empty($shi)) {
			$list[1] = $shi;
		} else {
			$list[1] = null;
		}
		if(!empty($qu)) {
			$list[2] = $qu;
		} else {
			$list[2] = null;
		}
		// $list = array_values($list);
		// $shifu = empty($shifu)?:;
		// show_json_w(1,array('shifu'=>$shifu,'shi'=>$shi,'qu'=>$qu),'成功!');
		show_json_w(1,$list,'成功!');
	}
	public function setInfo(){
		global $_W,$_GPC;
		$token = trim($_GPC['token']);
		$uid = m('game')->getuid($token);
		$member = pdo_fetch('select id from ' . tablename('wx_shop_member') . ' where id=:id and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':id' => $uid));
		if(empty($member)) {
			show_json_w(-1,null,'用户错误!');
		}
		$date = array();
		if(!empty($_GPC['mobile'])) {
			$mobile = !empty($_GPC['mobile']) ? trim($_GPC['mobile']) : show_json_w(-1,null, '手机号不能为空！');
			$code = !empty($_GPC['code']) ? $_GPC['code'] : show_json_w(-1,null, '验证码不能为空！');
			// m('game')->checkmobile($mobile,$code);//检验验证码
			$date['mobile'] = trim($_GPC['mobile']);
		}
		if(!empty($_GPC['avatar'])) {
			$date['avatar'] = trim($_GPC['avatar']);
		}
		if(!empty($_GPC['weixin'])) {
			$date['weixin'] = trim($_GPC['weixin']);
		}
		if(!empty($_GPC['qq_name'])) {
			$date['qq_name'] = trim($_GPC['qq_name']);
		}
		if(isset($_GPC['is_sf_k'])) {
			$date['is_sf_k'] = trim($_GPC['is_sf_k']);
		}
		if(isset($_GPC['is_td_k'])) {
			$date['is_td_k'] = trim($_GPC['is_td_k']);
		}
		if(empty($date)) {
			show_json_w(-1,null,'修改数据不能为空!');
		}
		pdo_update('wx_shop_member',$date,array('id'=>$uid));
		show_json_w(1,$date,'成功');
	}
	public function zx_time(){
		//获取用户信息
		global $_W,$_GPC;
		$token = trim($_GPC['token']);
		$uid = m('game')->getuid($token);
		$member = pdo_fetch('select id,logintime,zx_time from ' . tablename('wx_shop_member') . ' where id=:id and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':id' => $uid));
		// $member = m('member')->getMember($uid);
		if(empty($member)) {
			show_json_w(-1,null,'用户错误!');
		}
		$type = intval($_GPC['type']);
		if($type == 1) {
			//连接
			$res = pdo_update('wx_shop_member',array('logintime'=>time()),array('id'=>$member['id']));
			show_json_w(1,null,'连接成功!');
		} else if($type == 2){
			$time = time();
			//获取在线时长
			$zx_num = $time - $member['logintime'];
		  	// file_put_contents(dirname(__FILE__).'/zx_num',json_encode( $zx_num)); 
		  	// file_put_contents(dirname(__FILE__).'/zx_num1',json_encode( $member['zx_time'])); 
			// echo '<pre>';
			//     print_r($zx_num);
			// echo '</pre>';
			pdo_update('wx_shop_member',array('zx_time'=>$member['zx_time']+$zx_num),array('id'=>$member['id']));
			show_json_w(1,null,'退出成功更新在线时长!');
		}
	}
	//获取yqm
	public function getYqms(){
		//获取用户信息
		global $_W,$_GPC;
		$token = trim($_GPC['token']);
		$uid = m('game')->getuid($token);
		$member = pdo_fetch('select id,nickname,yqm,agentid from ' . tablename('wx_shop_member') . ' where id=:id and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':id' => $uid));
		// $member = m('member')->getMember($uid);
		if(empty($member)) {
			show_json_w(-1,null,'用户错误!');
		}
		$set = pdo_fetch('select xz_lj from ' . tablename('wx_shop_game_set') . ' where uniacid=:uniacid',array(':uniacid'=>$_W['uniacid']));
		$wd['lj'] = $set['xz_lj'].'&yqm='.$member['yqm'];
		// $wd['lj'] =  $_W['siteroot'] .'app/index.php?i=96&c=entry&m=wx_shop&do=mobile&r=account.register&yqm='.$member['yqm'];
		$wd['yqm'] = $member['yqm'];
		$one = pdo_fetchall('select id,agentid,nickname,createtime,game_level,is_rz,is_rzlq,is_rzlq_1,is_jh from ' .tablename('wx_shop_member').'where uniacid=:uniacid and agentid=:agentid',array(':uniacid'=>$_W['uniacid'],':agentid'=>$uid));
		$wd['td_num'] = count($one);
		$td_num_money = 0;
		// echo '<pre>';
		//     print_r($one);
		// echo '</pre>';
		foreach ($one as $key => $value) {
			$agentid .= $value['id'] . ',';
			// echo '<pre>';
			//     print_r($value['is_rzlq']);
			// echo '</pre>';
			if($value['is_rz'] == 1 && $value['is_rzlq'] != '超过每日上限') {
				$ars = explode(",", $value['is_rzlq']);
				// echo '<pre>';
				//     print_r($ars);
				// echo '</pre>';
				$td_num_money += $ars[0];
			}
		}	
		// echo '<pre>';
		//     print_r($td_num_money);
		// echo '</pre>';
		// $wd['td_num_money'] = pdo_fetchall;
		if(empty($td_num_money)) {
			$td_num_money = 0;
		}
		$wd['td_num_money'] = $td_num_money;
		$agentid = rtrim($agentid,',');
		if(!empty($one)) {
			$two = pdo_fetchall('select id,agentid,nickname,createtime,game_level,is_rz,is_rzlq,is_rzlq_1,is_jh from ' .tablename('wx_shop_member').'where uniacid=:uniacid and agentid in('.$agentid.')',array(':uniacid'=>$_W['uniacid']));
				
			$wd['ts_num'] = count($two);
		} else {
			$wd['ts_num'] = 0;
		}
		// echo '<pre>';
		//     print_r($two);
		// echo '</pre>';
		
		$wd['ts_num_money'] = pdo_fetchcolumn('select sum(money) from '  .tablename('wx_shop_game_log'.substr($member['id'], -1)) . ' where uniacid=:uniacid and uid=:uid and type=18',array(':uniacid'=>$_W['uniacid'],':uid'=>$member['id']));
		if(empty($wd['ts_num_money'])) {
			$wd['ts_num_money'] = 0;
		}
		$wd['lj_cd'] = $wd['ts_num_money']+$wd['td_num_money'];
		$wd['set'] = pdo_fetch('select rz_1,rzs_1 from ' . tablename('wx_shop_game_set') . ' where uniacid=:uniacid',array(':uniacid'=>$_W['uniacid']));
		// $wd['set']['rz_level'] = 10;
		$wd['sf'] = pdo_fetch('select id,nickname,yqm,avatar,game_level,mobile,qq_name,weixin,is_td_k,is_rz from ' . tablename('wx_shop_member') . ' where id=:id and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':id' => $member['agentid']));
		if($wd['sf']['is_td_k'] == 0) {
			unset($wd['sf']['qq_name']);
			unset($wd['sf']['weixin']);
		}
		if(empty($wd['sf'])){
			$wd['sf'] = null;
		}
		$wd['sm'] = '有效徒弟定义：完成实名认证和徒弟神鸟等级达到'.$set['rz_level'].'级。\n1、每邀请一个有效徒弟，你就可以获得'.$set['rz_1'].'彩蛋奖励；\n2、你邀请的有效徒弟，其邀请一个有效徒弟，即徒孙，你将获得'.$set['rzs_1'].'彩蛋奖励；\n3、你需要完成实名认证才能获得彩蛋奖励；\n4、系统会自动计算，只要达到有效用户条件，彩蛋会自动发放您的账号，可能有几分钟延迟，请稍后再查看。如果一直没有收到，请在APP里联系客服。\n5、神鸟世界保留对本活动的最终解释权，并将严查恶意刷徒弟等虚假邀请行为，一经发现将取消奖励资格。';
		show_json_w(1,$wd,'成功');
		// echo '<pre>';
		//     print_r($wd);
		// echo '</pre>';
		// $res = m('game')->createMyQrcode($member['yqm']);
	}
	//获取彩蛋币记录
	public function getCredit_b(){
		global $_W,$_GPC;
		$token = trim($_GPC['token']);
		$uid = m('game')->getuid($token);
		$pindex = max(1, intval($_GPC['page']));
		
		$psize = 10;
		$member = pdo_fetch('select id,nickname,yqm,agentid,mobile,lj_c,avatar from ' . tablename('wx_shop_member') . ' where id=:id and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':id' => $uid));
		// $member = m('member')->getMember($uid);
		if(empty($member)) {
			show_json_w(-1,null,'用户错误!');
		}
		$log = pdo_fetchall('select id,type,content,settype,time,money from ' . tablename('wx_shop_game_log'.substr($uid, -1)) . ' where uniacid=:uniacid and uid=:uid and settype=:settype and type!=1 order by time desc LIMIT ' . (($pindex - 1) * $psize) . ',' . $psize,array(':uniacid'=>$_W['uniacid'],':uid'=>$uid,':settype'=>'credit_b'));
		$paytype = m('game')->paytype();
		$paytype = array_flip($paytype);
		// echo "<pre>";
		// 	print_r($paytype);
		// echo "</pre>";
		foreach ($log as $key => $value) {
				
			$log[$key]['time'] = date("Y-m-d",$value['time']);
			$log[$key]['paytype'] = $paytype[$value['type']];
		}
		// echo "<pre>";
		// 	print_r($log);
		// echo "</pre>";
		show_json_w(1,array('log'=>$log,'lj_c'=>$member['lj_c']),'成功!');
	}
	//个人信息
	public function upMember(){
		global $_W,$_GPC;
		$token = trim($_GPC['token']);
		$uid = m('game')->getuid($token);
		$member = pdo_fetch('select id,nickname,yqm,agentid,mobile,is_rz,avatar from ' . tablename('wx_shop_member') . ' where id=:id and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':id' => $uid));
		// $member = m('member')->getMember($uid);
		if(empty($member)) {
			show_json_w(-1,null,'用户错误!');
		}
		$type = intval($_GPC['type']);
		if($type == 1) {
			
			//获取信息数据
			show_json_w(1,$member,'成功!');
		} else if($type == 2) {
		}
	}
	//获取图片
	public function getYmimg(){
		//获取用户信息
		global $_W,$_GPC;
		$token = trim($_GPC['token']);
		$uid = m('game')->getuid($token);
		$member = pdo_fetch('select id,nickname,yqm,agentid from ' . tablename('wx_shop_member') . ' where id=:id and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':id' => $uid));
		// $member = m('member')->getMember($uid);
		if(empty($member)) {
			show_json_w(-1,null,'用户错误!');
		}
		$set = pdo_fetch('select fx_img,xz_lj from ' . tablename('wx_shop_game_set') . ' where uniacid=:uniacid',array(':uniacid'=>$_W['uniacid']));
		$res = m('game')->createMyQrcode($member['yqm'],$set['xz_lj']);
		$wd['fx_img'] = tomedia($set['fx_img']);
		$wd['ma'] = $res;
		show_json_w(1,$wd,'成功');
	}
	public function lq_red(){
		//获取用户信息
		global $_W,$_GPC;
		$token = trim($_GPC['token']);
		$uid = m('game')->getuid($token);
		$logid = $_GPC['logid'];
		if(empty($uid)) {
			show_json_w(-1,null,'用户错误!');
		}
		if(empty($logid)) {
			show_json_w(-1,null,'封号吧!');
		}
		$logs = pdo_fetch('select id,money,uid,status from ' . tablename('wx_shop_game_member_log') . ' where uniacid=:uniacid and uid=:uid and id=:id and status=2',array(':uniacid'=>$_W['uniacid'],':uid'=>$uid,':id'=>$logid));
		if($logs['status']==3) {
			show_json_w(-1,null,'积分已经领取,请勿重复领取!');
		}
		$member = pdo_fetch('select credit_red from ' . tablename('wx_shop_member') . ' where id=:id and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':id' => $uid));
		pdo_update('wx_shop_game_member_log',array('status'=>3),array('id'=>$logs['id']));
		$moneys = $member['credit_red']+$logs['money'];
		pdo_update('wx_shop_member',array('credit_red'=>$moneys),array('id'=>$uid));
		show_json_w(1,$logs['money'],'领取成功!');
	}
	public function getWithdraw(){
		//获取用户信息
		global $_W,$_GPC;
		$token = trim($_GPC['token']);
		$uid = m('game')->getuid($token);
		// $member = m('member')->getMember($uid);
		$member = pdo_fetch('select id,openid,nickname,credit_red,is_rz from ' . tablename('wx_shop_member') . ' where id=:id and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':id' => $uid));
		if(empty($member)) {
			show_json_w(-1,null,'用户错误!');
		}
		$type = intval($_GPC['type']);
		$set = pdo_fetch('select tx_sxf,tx_sm,tx_moneys from ' . tablename('wx_shop_game_set') . ' where uniacid=:uniacid',array(':uniacid'=>$_W['uniacid']));
		$set['tx_moneys'] = explode(";",$set['tx_moneys']);
		$isa = pdo_fetch('select id from ' . tablename('wx_shop_game_member_log') . ' where uniacid=:uniacid and type=1 and status != -1 and uid=:uid and money=:money',array(':uniacid'=>$_W['uniacid'],'uid'=>$uid,':money'=>$set['tx_moneys'][0]));
		if($isa) {
			unset($set['tx_moneys'][0]);
			$set['tx_moneys'] = array_values($set['tx_moneys']);
		}
		if($type == 1 && $_W['ispost']) {
			//提现申请
			$money = floatval($_GPC['money']);
			
			$credit = m('game')->getMoney($uid);
			if ($money <= 0) 
			{
				show_json_w(-1,null, '提现金额错误!');
			}
			if ($credit['credit_red'] < $money) 
			{
				show_json_w(-1,null, '提现金额过大!');
			}
			if ($member['is_rz'] == 0) 
			{
				show_json_w(-1,null, '未实名认证无法提现!');
			}
			$apply = array();
			$realmoney = $money - ($set['tx_sxf']/100 * $money);
			
			// m('member')->setCredit($_W['openid'], 'credit_red', -$money, array(0, $_W['shopset']['set'][''] . '积分余额提现预扣除: ' . $money . ',实际到账金额:' . $realmoney . ',手续费金额:' . $set['tx_sxf']));
				
			// m('game')->setMoney();
			pdo_update('wx_shop_member',array('credit_red'=>$credit['credit_red']-$money),array('id'=>$uid));
			$logno = m('common')->createNO('game_member_log', 'logno', 'RW');
			$apply['uniacid'] = $_W['uniacid'];
			$apply['logno'] = $logno;
			$apply['uid'] = $uid;
			// $apply['openid'] = $member['openid'];
			$apply['title'] = '积分余额提现';
			$apply['type'] = 1;
			$apply['createtime'] = time();
			$apply['status'] = 0;
			$apply['money'] = $money;
			$apply['realmoney'] = $realmoney;
			$apply['deductionmoney'] = $set['tx_sxf'];
			$apply['applytype'] = 0;
			pdo_insert('wx_shop_game_member_log', $apply);
			$logid = pdo_insertid();
			// m('notice')->sendMemberLogMessage($logid);
			show_json_w(1,null,'成功');
		} else {
			show_json_w(1,array('credit_red'=>$member['credit_red'],'set'=>$set),'成功');
		}
	}
	//提现记录
	public function withdrawLog(){
		//获取用户信息
		global $_W,$_GPC;
		$token = trim($_GPC['token']);
		$uid = m('game')->getuid($token);
		// $member = m('member')->getMember($uid);
		$member = pdo_fetch('select id,openid,nickname,credit_red from ' . tablename('wx_shop_member') . ' where id=:id and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':id' => $uid));
		if(empty($member)) {
			show_json_w(-1,null,'用户错误!');
		}
		$pindex = max(1, intval($_GPC['page']));
		$psize = 10;
		$condition = ' and uid=:uid and uniacid=:uniacid and type in(1,2,4,9,10,11,12,13) and status != 2 and status != -1';
		$params = array(':uniacid' => $_W['uniacid'], ':uid' => $uid,);
		$list = pdo_fetchall('select uid,money,createtime,applytype,status,realmoney,title,type from ' . tablename('wx_shop_game_member_log') . ' where 1 ' . $condition . ' order by createtime desc LIMIT ' . (($pindex - 1) * $psize) . ',' . $psize, $params);
		$total = pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_game_member_log') . ' where 1 ' . $condition, $params);
		// $paytype = m('game')->paytype();
		foreach ($list as &$row ) 
		{
			$row['createtime'] = date('Y-m-d H:i', $row['createtime']);	
 
			if($row['type'] == 1) {
				$row['money'] = '-'.$row['money'];
			} else {
				$row['money'] = '+'.$row['money'];
			}
		}
		unset($row);
		show_json_w(1,$list,'成功');
		// show_json_w(1, array('list' => $list, 'total' => $total, 'pagesize' => $psize),'成功');
	}
	//收益
	public function shouyi(){
		//获取用户信息
		global $_W,$_GPC;
		$token = trim($_GPC['token']);
		$uid = m('game')->getuid($token);
		$member = m('member')->getMember($uid);
		if(empty($member)) {
			show_json_w(-1,null,'用户错误!');
		}
		m('game')->setLevel($uid);
		
		
		$jy = m('game')->getLevel($uid);
		
		$member['gg_levels']['mb'] = pdo_fetchcolumn('select jy from ' . tablename('wx_shop_game_level') . ' where uniacid=:uniacid and id>:id limit 1',array(':uniacid'=>$_W['uniacid'],':id'=>$member['gg_levels']['id']));
		// echo '<pre>';
		//     print_r($member);
		// echo '</pre>';
		// echo '<pre>';
		//     print_r($jy);
		// echo '</pre>';
		$jinri  = m('game')->jgetSy($uid);
		// echo '<pre>';
		//     print_r($jinri);
		// echo '</pre>';
		$zuori = m('game')->zgetSy($uid);
		$zongsy = m('game')->ljgetSy($uid);
		// echo '<pre>';
		//     print_r($zuori);
		// echo '</pre>';
		show_json_w(1,array('member'=>$member['gg_levels'],'jy'=>$jy,'jinri'=>$jinri,'zuori'=>$zuori,'zongsy'=>$zongsy),'成功');
		
		
	}
	//我的收益
	public function shouyiLog(){
		//获取用户信息
		global $_W,$_GPC;
		$token = trim($_GPC['token']);
		$uid = m('game')->getuid($token);
		// $member = m('member')->getMember($uid);
		$member = pdo_fetch('select id,openid,nickname,credit_red from ' . tablename('wx_shop_member') . ' where id=:id and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':id' => $uid));
		if(empty($member)) {
			show_json_w(-1,null,'用户错误!');
		}
		$pindex = max(1, intval($_GPC['page']));
		
		$psize = 10;
		$condition = ' uniacid=:uniacid and uid=:uid and type != 5';
		$params = array(':uniacid' => $_W['uniacid'],':uid'=>$uid);
		$sql = ' select * from (' .alltable($condition).') log where 1 order by log.time desc , log.id desc LIMIT ' . (($pindex - 1) * $psize) . ',' . $psize;;
		$list = pdo_fetchall($sql, $params);
		foreach ($list as $key => $value) {
			$list[$key]['times'] = date("Y-m-d",$value['time']);
			$list[$key]['lj'] = $value['fx_money'] + $value['fh_money'];
		}	
		$total = pdo_fetchcolumn('select count(*) from (' . alltable($condition) . ') log  where 1 ', $params);
		show_json_w(1,$list,'成功');
		// show_json_w(1,array('list'=>$list, 'total' => $total, 'pagesize' => $psize),'成功');
		// echo '<pre>';
		//     print_r($list);
		// echo '</pre>';
	}
	public function renwu() {
		//获取用户信息
		global $_W,$_GPC;
		$token = trim($_GPC['token']);
		$uid = m('game')->getuid($token);
		$member = m('member')->getMember($uid);
		if(empty($member)) {
			show_json_w(-1,null,'用户错误!');
		}
		$type = intval($_GPC['type']);
		$stime = mktime(0,0,0,date("m"),date("d"),date("Y"));
		$etime = mktime(23,59,59,date("m"),date("d"),date("Y"));
		//获取签到记录
		$qd = pdo_fetch('select id from '  .tablename('wx_shop_game_log'.substr($uid, -1)) . ' where uniacid=:uniacid and uid=:uid and time>=:stime and time<=:etime and type=15 limit 1',array(':uniacid'=>$_W['uniacid'],':uid'=>$uid,':stime'=>$stime,':etime'=>$etime));
		
		$set = pdo_fetch('select * from ' . tablename('wx_shop_game_set') . ' where uniacid=:uniacid ',array(':uniacid'=>$_W['uniacid']));
		//获取在线秒数
		$zx = time() - $member['logintime'] + $member['zx_time'];
		// echo '<pre>';
		//     print_r(time() - $member['logintime']);
		// echo '</pre>';
		// echo '<pre>';
		//     print_r($zx);
		// echo '</pre>';
		//向下取整
		$zx_fz = floor($zx / 60);
		// echo '<pre>';
		//     print_r($zx_fz);
		// echo '</pre>';
		$zx_log = pdo_fetch('select id from '  .tablename('wx_shop_game_log'.substr($uid, -1)) . ' where uniacid=:uniacid and uid=:uid and time>=:stime and time<=:etime and type=20 limit 1',array(':uniacid'=>$_W['uniacid'],':uid'=>$uid,':stime'=>$stime,':etime'=>$etime));
		if(empty($qd)) {
			$wd['is_qd'] = 0;
		} else {
			$wd['is_qd'] = 1;
		}
		if(empty($zx_log)) {
			$wd['is_zx'] = 0;
		} else {
			$wd['is_zx'] = 1;
		}
		if($type == 1) {
			//签到
			
			if($wd['is_qd'] == 0){
				m('game')->setMoney($member['id'],'credit_b',$set['qd_money'],'签到奖励','签到奖励'.$set['qd_money']);
				show_json_w(1,$set['qd_money'],'成功');
			} else {
				show_json_w(-1,null,'今日已经成功签到无需重复！');
			}	
		} else if($type == 2) {
			//在线领取奖励
			if($zx_fz >= 30) {
				if($wd['is_zx'] == 0) {
					// $set['zx_money'] = 5;
					m('game')->setMoney($member['id'],'credit_b',$set['zx_money'],'在线奖励','在线奖励'.$set['zx_money']);
					show_json_w(1,$set['zx_money'],'成功');
				} else {
					show_json_w(-1,null,'今日已经成功领取奖励,无需重复！');
				}
			} else {
				show_json_w(-1,null,'今日在线时间不足,无法领取');
			}
		}
		$rz = pdo_fetchcolumn('select count(id) from '  .tablename('wx_shop_game_log'.substr($member['id'], -1)) . ' where uniacid=:uniacid and uid=:uid and time>=:stime and time<=:etime and type=17',array(':uniacid'=>$_W['uniacid'],':uid'=>$member['id'],':stime'=>$stime,':etime'=>$etime));
		$wd['yq_num'] = $rz / 2;
		$wd['yq_max'] = $set['rz_max'];
		$wd['zx_fz'] = $zx_fz;
		$wd['is_rz'] = $member['is_rz'];
		$wd['is_wx'] = $member['is_wx'];
		$wd['lj_c'] = $member['lj_c'];
		$wd['qd_money'] = $set['qd_money'];
		
		$wd['wxbind'] = $set['wxbind'];
		$wd['rz'] = $set['rz'];
		
		$wd['rz_1'] = $set['rz_1'];
		$wd['rz_2'] = $set['rz_2'];
		$wd['zx_money'] = $set['zx_money'];
		$wd['rz_level'] = $set['rz_level'];
		$wd['dt'] = 1;
		$wd['dt_money'] = $set['dt_one'];
		if($member['dt_one']==1 && !strpos($member['dt_one'],',')){
			$wd['dt_money'] = $set['dt_two'];
			$wd['dt'] = 2;
		}
		if($member['dt_two']== 1 && $member['dt_one']==1){
			$wd['dt_money'] = $set['dt_two'];
			$wd['dt'] = 3;
		}
		// echo '<pre>';
		//     print_r($member);
		// echo '</pre>';
		$wd['img'] = tomedia($member['c_levels']['img']);
		
		show_json_w(1,$wd,'成功');
		// echo '<pre>';
		//     print_r($wd);
		// echo '</pre>';
	}
	public function rz() {
		//实名认证
		global $_W,$_GPC;
		$token = trim($_GPC['token']);
		$uid = m('game')->getuid($token);
		$member = m('member')->getMember($uid);
		if(empty($member)) {
			show_json_w(-1,null,'用户错误!');
		}
		if($member['is_rz'] == 1) {
			show_json_w(-1,null,'已经绑定无需重复!');
		}
		$sfz = trim($_GPC['sfz']);
		$realname = trim($_GPC['realname']);
		if(empty($realname)) {
			show_json_w(-1,null,'姓名不能为空!');
		}
		$res = m('game')->isCreditNo($sfz);
		// $res = 1;
		if(empty($res)) {
			show_json_w(-1,null,'身份证错误,请认真填写!');
		}
		$stime = mktime(0,0,0,date("m"),date("d"),date("Y"));
		$etime = mktime(23,59,59,date("m"),date("d"),date("Y"));
		$set = pdo_fetch('select * from ' . tablename('wx_shop_game_set') . ' where uniacid=:uniacid ',array(':uniacid'=>$_W['uniacid']));
		$agent = pdo_fetch('select id,agentid from ' . tablename('wx_shop_member') . ' where uniacid=:uniacid and id=:id limit 1',array(':uniacid'=>$_W['uniacid'],':id'=>$member['agentid']));
		// echo '<pre>';
		//     print_r($agent);
		// echo '</pre>';
		// $is_rzlq = 0;
		// $is_rzlq_1 = 0;
		if(!empty($agent) && $member['game_level'] >= $set['rz_level']) {
			
			//上级获取领取认证奖励记录
			$rz = pdo_fetchcolumn('select count(id) from '  .tablename('wx_shop_game_log'.substr($agent['id'], -1)) . ' where uniacid=:uniacid and uid=:uid and time>=:stime and time<=:etime and type=17',array(':uniacid'=>$_W['uniacid'],':uid'=>$agent['id'],':stime'=>$stime,':etime'=>$etime));
			// echo '<pre>';
			//     print_r($rz);
			// echo '</pre>';
			//发放上级奖励
			if( ($rz / 2)   < $set['rz_max']) {
				m('game')->setMoney($agent['id'],'credit_b',$set['rz_1'],'徒弟认证奖励','徒弟认证奖励彩蛋币'.$set['rz_1']);
				m('game')->setMoney($agent['id'],'yqq',$set['rz_2'],'徒弟认证奖励','徒弟认证奖励邀请券'.$set['rz_2']);
				$wd['is_rzlq'] = $set['rz_1'].','.$set['rz_2'];
			} else {
				//超过上限
				$wd['is_rzlq'] = '超过每日上限';
			}
			$two = pdo_fetch('select id from ' . tablename('wx_shop_member') . ' where uniacid=:uniacid and id=:id limit 1',array(':uniacid'=>$_W['uniacid'],':id'=>$agent['agentid']));
			// echo '<pre>';
			//     print_r($two);
			// echo '</pre>';
			if(!empty($two)) {
				//上上级获取领取认证奖励记录
				$rz1 = pdo_fetchcolumn('select count(id) from '  .tablename('wx_shop_game_log'.substr($two['id'], -1)) . ' where uniacid=:uniacid and uid=:uid and time>=:stime and time<=:etime and type=18',array(':uniacid'=>$_W['uniacid'],':uid'=>$two['id'],':stime'=>$stime,':etime'=>$etime));
				//发放上上级奖励
				if($rz1   < $set['rzs_max']) {
					m('game')->setMoney($two['id'],'credit_b',$set['rzs_1'],'徒孙认证奖励','徒孙认证奖励彩蛋币'.$set['rzs_1']);
					$wd['is_rzlq_1'] = $set['rzs_1'];
				} else {
					$wd['is_rzlq_1'] = '超过每日上限';
				}
			}
		
		}
		$wd['realname'] = $realname;
		$wd['sfz'] = $sfz;
		$wd['is_rz']  = 1;
		pdo_update('wx_shop_member',$wd,array('id'=>$member['id']));
		m('game')->setMoney($member['id'],'credit_b',$set['rz'],'认证奖励','认证奖励彩蛋币'.$set['rz']);
		
		show_json_w(1,$set['rz'],'奖励彩蛋币'.$set['rz']);
	}
	//我的称号
	public function chenghao() {
		//获取用户信息
		global $_W,$_GPC;
		$token = trim($_GPC['token']);
		$uid = m('game')->getuid($token);
		$member = m('member')->getMember($uid);
		if(empty($member)) {
			show_json_w(-1,null,'用户错误!');
		}
		$chao = m('game')->getBlevel_cg($uid);
		$member['c_levels']['img'] = tomedia($member['c_levels']['img']);
		$set = pdo_fetch('select ch_img,xz_lj from ' . tablename('wx_shop_game_set') . ' where uniacid=:uniacid',array(':uniacid'=>$_W['uniacid']));
		$res = m('game')->createMyQrcode($member['yqm'],$set['xz_lj']);
		$wd['ch_img'] = tomedia($set['ch_img']);
		$wd['ma'] = $res;
		// echo '<pre>';
		//     print_r($member);
		// echo '</pre>';
		show_json_w(1,array('c_levels'=>$member['c_levels'],'chao'=>$chao,'wd'=>$wd),'成功');
		// echo '<pre>';
		//     print_r($chao);
		// echo '</pre>';
	}
	//帮助
	public function bz(){
		//获取用户信息
		global $_W,$_GPC;
		$token = trim($_GPC['token']);
		$uid = m('game')->getuid($token);
		$member = m('member')->getMember($uid);
		if(empty($member)) {
			show_json_w(-1,null,'用户错误!');
		}
		$list = pdo_fetchall('SELECT * FROM ' . tablename('wx_shop_article_category') . ' WHERE uniacid =' . $_W['uniacid'] . ' and isshow=1 ORDER BY id asc');
		foreach ($list as $key => $value) {
			$list[$key]['img'] = tomedia($value['img']);
		}
		$rebang = pdo_fetchall('SELECT id,article_title FROM ' . tablename('wx_shop_article') . ' WHERE uniacid =' . $_W['uniacid'] . ' and article_readnum_v != 0  order by article_readnum_v desc limit 10');
		foreach ($rebang as $key => $value) {
		
			$rebang[$key]['lj'] = $_W['siteroot'] .'app/index.php?i=96&c=entry&m=wx_shop&do=mobile&r=article&aid='.$value['id'];
		}
		// echo '<pre>';
		//     print_r($rebang);
		// echo '</pre>';
		show_json_w(1,array('list'=>$list,'re'=>$rebang),'成功!');
	}
	//帮助类表信息
	//帮助
	public function bz_list(){
		//获取用户信息
		global $_W,$_GPC;
		$token = trim($_GPC['token']);
		$uid = m('game')->getuid($token);
		$member = m('member')->getMember($uid);
		if(empty($member)) {
			show_json_w(-1,null,'用户错误!');
		}
		$id = intval($_GPC['id']);
			
		$rebang = pdo_fetchall('SELECT id,article_title FROM ' . tablename('wx_shop_article') . ' WHERE uniacid =' . $_W['uniacid'] . ' and article_state=1 and article_category='.$id.' order by article_readnum_v desc');
		foreach ($rebang as $key => $value) {
		
			$rebang[$key]['lj'] = $_W['siteroot'] .'app/index.php?i=96&c=entry&m=wx_shop&do=mobile&r=article&aid='.$value['id'];
		}
		show_json_w(1,$rebang,'成功!');
	}
	public function getChenhao(){
		//获取用户信息
		global $_W,$_GPC;
		$token = trim($_GPC['token']);
		$uid = m('game')->getuid($token);
		$member = m('member')->getMember($uid);
		if(empty($member)) {
			show_json_w(-1,null,'用户错误!');
		}
    	
    	$level = pdo_fetchall('select * from' . tablename('wx_shop_game_blevel') . ' where uniacid=:uniacid  order by level asc',array(':uniacid'=>$_W['uniacid']));
    	$max = count($level);
    	// echo "<pre>";
    	// 	print_r($max);
    	// echo "</pre>";
    	foreach ($level as $key => $value) {
    		if($key+1 >= $max) {
    			$level[$key]['caidan'] = $value['jy'].'-'.'999999999';
    		} else {
    			$level[$key]['caidan'] = $value['jy'].'-'.($level[$key+1]['jy']-1);
    		}
    		$level[$key]['img'] = tomedia($value['img']);
    	}
    	show_json_w(1,$level,'成功');
	}
	//团队
	public function td() {
		//获取用户信息
		global $_W,$_GPC;
		$token = trim($_GPC['token']);
		$uid = m('game')->getuid($token);
		// echo "<pre>";
		// 	print_r($uid);
		// echo "</pre>";
		$member = m('member')->getMember($uid);
		if(empty($member)) {
			show_json_w(-1,null,'用户错误!');
		}
		// echo '<pre>';
		//     print_r($member);
		// echo '</pre>';
		$one = pdo_fetchall('select mobile,id,agentid,nickname,createtime,game_level,is_rz,is_rzlq,is_rzlq_1,is_jh,is_sf_k,weixin,qq_name,avatar from ' .tablename('wx_shop_member').'where uniacid=:uniacid and agentid=:agentid',array(':uniacid'=>$_W['uniacid'],':agentid'=>$uid));
		
		$type = intval($_GPC['type']);
		$set = pdo_fetch('select rz_level from ' . tablename('wx_shop_game_set') . ' where uniacid=:uniacid ',array(':uniacid'=>$_W['uniacid']));
		foreach ($one as $key => $value) {
			$agentid .= $value['id'] . ',';
			$one[$key]['createtime'] = date("Y-m-d H:i:s",$value['createtime']);
			if($type == 1) {
				if($value['is_sf_k'] == 0) {
					// echo '<pre>';
					//     print_r($value['is_sf_k']);
					// echo '</pre>';
					unset($one[$key]['qq_name']);
					unset($one[$key]['weixin']);
				}
				if($value['is_rz'] == 0) {
					$one[$key]['xx'] = '未认证';
					continue;
				} else if($value['game_level'] < $set['rz_level']) {
					$one[$key]['xx'] = '未达到等级';
					continue;
				} else {
					$abc = explode(',',$value['is_rzlq']);
					$one[$key]['xx'] = '+'.$abc[0];
					continue;
				}
				
			} else if($type == 3) {
				if($value['is_jh'] == 1) {
					$one[$key]['xx'] = '激活';
					continue;
				} else {
					$one[$key]['xx'] = '未激活';
					continue;
				}
			}
		
		}
		// echo '<pre>';
		//     print_r($one);
		// echo '</pre>';
		// echo '<pre>';
		//     print_r($one);
		// echo '</pre>';
		// echo '<pre>';
		//     print_r($type);
		// echo '</pre>';
		//徒孙
		if($type == 1) {
			
			show_json_w(1,array('list'=>$one,'total'=>count($one)),'成功');
		
		} else if($type == 2) {
			$agentid = rtrim($agentid,',');
			if(!empty($agentid)) {
				$two = pdo_fetchall('select mobile,id,agentid,nickname,createtime,game_level,is_rz,is_rzlq,is_rzlq_1,is_jh,weixin,qq_name,avatar from ' .tablename('wx_shop_member').'where uniacid=:uniacid and agentid in('.$agentid.')',array(':uniacid'=>$_W['uniacid']));
			} else {
				$two = null;
			}
			if(!empty($two)) {
				
				foreach ($two as $key => $value) {
					$two[$key]['createtime'] = date("Y-m-d H:i:s",$value['createtime']);
					if($value['is_rzlq_1'] == '超过每日上限') {
						$two[$key]['xx'] = $value['is_rzlq_1'];
						continue;
					} else if($value['is_rz'] == 0){
						$two[$key]['xx'] = '未认证';
						continue;
					} else {
						$two[$key]['xx'] = '+'.$value['is_rzlq_1'];
						continue;
					}
				}
			} else {
				$two = null;
			}
			show_json_w(1,array('list'=>$two,'total'=>count($two)),'成功');
		} else if($type == 3) { 
			foreach ($one as $key => $value) {
				if($value['is_jh'] == 1) {
					unset($one[$key]);
				}
			}
			show_json_w(1,array('list'=>$one,'total'=>count($one)),'成功');
		}
	}
	//分红详情
	public function fh()
	{
		//获取用户信息
		global $_W,$_GPC;
		$token = trim($_GPC['token']);
		$uid = m('game')->getuid($token);
		$member = m('member')->getMember($uid);
		if(empty($member)) {
			show_json_w(-1,null,'用户错误!');
		}
		$setime = mktime(0,0,0,date("m"),date("d")-1,date("Y"));
		
		//昨天23.59
		$zsetime = mktime(23,59,59,date("m"),date("d")-1,date("Y"));
		//昨日广告全网收益
		$zuo_fx_money = pdo_fetchcolumn('select sum(money) from ' . tablename('wx_shop_game_video') . ' where uniacid=:uniacid and time>:stime and time<=:etime ',array(':uniacid'=>$_W['uniacid'],':stime'=>$setime,':etime'=>$zsetime));
		$zuo_fx_money = empty($zuo_fx_money)?0:$zuo_fx_money;
		//神鸟用户
		//昨日分红神鸟全网收益
		$zuo_fh_money = 0;
		//历史总收益
		// $zong_fx_money = 0;
		$zong_fx_money = pdo_fetchcolumn('select sum(money) from ' . tablename('wx_shop_game_video') . ' where uniacid=:uniacid ',array(':uniacid'=>$_W['uniacid']));
		$zong_fx_money = empty($zong_fx_money)?0:$zong_fx_money;
		//全网神鸟数量
		$sn_goods = 0;
		//待产出
		$d_cc = 0;
		for ($i=0; $i < 10; $i++) { 
				
			// echo '<pre>';
			//     print_r($i);
			// echo '</pre>';
			$reslog = pdo_fetchall('select id,uid,fx_money,fh_money,video_id,type,bili,sn_money from ' . tablename('wx_shop_game_redlog'.$i) . ' where uniacid=:uniacid  and time>=:setime and time<=:zsetime',array(':uniacid'=>$_W['uniacid'],':setime'=>$setime,':zsetime'=>$zsetime));
			if(!empty($reslog)) {
				// echo '<pre>';
				//     print_r($reslog);
				// echo '</pre>';
				foreach ($reslog as $key => $value) {
					// $zuo_fx_money += $value['fx_money'];
					
					$zuo_fh_money += $value['sn_money'];
				}
			}
			// $reslog_zong = pdo_fetchall('select id,uid,fx_money,fh_money,video_id,type from ' . tablename('wx_shop_game_redlog'.$i) . ' where uniacid=:uniacid and status=:status',array(':uniacid'=>$_W['uniacid'],':status'=>1));
			// if(!empty($reslog_zong)) {
			// 	// echo '<pre>';
			// 	//     print_r($reslog);
			// 	// echo '</pre>';
			// 	foreach ($reslog_zong as $key => $value) {
			// 		// $zong_fx_money += $value['fx_money'];
			// 	}
			// }
		
		}
		//全网神鸟数量
		// $sn_goods = pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_game_sn') . ' where uniacid=:uniacid and goodsid=:goodsid ',array(':uniacid'=>$_W['uniacid'],':goodsid'=>46));
		
		//前天的数量
		$zrtime = mktime(23,59,59,date("m"),date("d")-2,date("Y"));
		$sn_goods = pdo_fetchcolumn('select num from ' . tablename('wx_shop_game_zrsn') . ' where uniacid=:uniacid and times=:times',array(':uniacid'=>$_W['uniacid'],':times'=>$zrtime));
		$sn_goods = empty($sn_goods) ? 0: $sn_goods;
		//待产出
		// $d_cc = pdo_fetchcolumn('select sum(fh_money) from ' . tablename('wx_shop_game_redlog'.$i) . ' where uniacid=:uniacid and status=:status',array(':uniacid'=>$_W['uniacid'],':status'=>0));
		$set = pdo_fetch('select sn_zong from ' . tablename('wx_shop_game_set') . ' where uniacid=:uniacid ',array(':uniacid'=>$_W['uniacid']));
		$jetime = mktime(0,0,0,date("m"),date("d"),date("Y"));
		
		$jr_cc = pdo_fetchcolumn('select count(*)  from ' . tablename('wx_shop_game_sn') . ' where uniacid=:uniacid and  time>=:jetime',array(':uniacid'=>$_W['uniacid'],':jetime'=>$jetime));
		// echo '<pre>';
		//     print_r($jr_cc);
		// echo '</pre>';
		// $d_cc += $d_cc;
		$wd['zuo_fx_money'] = $zuo_fx_money;
		$wd['zuo_fh_money'] = $zuo_fh_money / $sn_goods;
		$wd['zong_fx_money'] = $zong_fx_money;
		$wd['sn_goods'] = $sn_goods;
		$wd['d_cc'] = $set['sn_zong'] - $sn_goods;
		$wd['jr_cc'] = $jr_cc;
		show_json_w(1,$wd,'成功');
		// echo '<pre>';
		//     print_r($wd);
		// echo '</pre>';
	}
	//微信授权登录
	public function wxlogin(){
		
		global $_W,$_GPC;
		$code=$_GPC['code'];
		$appid="wx33176228458d46a0";
		$secret="f53bad3aacb8174b831b739ede447776";
		$wxactokeninfo=file_get_contents("https://api.weixin.qq.com/sns/oauth2/access_token?appid=$appid&secret=$secret&code=$code&grant_type=authorization_code");
		$wxactokeninfo=json_decode($wxactokeninfo,true);
		  file_put_contents(dirname(__FILE__).'/wxactokeninfo',json_encode( $wxactokeninfo)); 
		
		if($wxactokeninfo['openid']==""){
			show_json_w(-1,null,"微信授权code错误");
		}else{
			$wxinfo=file_get_contents("https://api.weixin.qq.com/sns/userinfo?access_token=".$wxactokeninfo['access_token']."&openid=".$wxactokeninfo['openid']);
			$wxinfo=json_decode($wxinfo,true);
		}
		$info=pdo_fetch('select id,nickname,avatar from ' . tablename('wx_shop_member') . ' where  openid=:openid', array(':openid' =>$wxinfo['openid']));
		if($info){
			$data['token'] = md5(serialize($wxinfo['openid']).time());
	        $data['logintime']=time();
	        
	        $data['is_jh']=1;
			pdo_update("wx_shop_member",$data,array("id"=>$info['id']));
			$returnjson['token']=$data['token'];
			$returnjson['nickname']=$info['nickname'];
			$returnjson['avatar']=$info['avatar'];
			
			$returnjson['uid']=$info['id'];
			show_json_w(1,$returnjson,'成功');
		}else{
		  $bindcode=time().mt_rand(0000,9999);
		  $redis=m("game")->getRedis();
		  $redis->setex($bindcode,600,serialize($wxinfo));
		  $json['nickname']=$wxinfo['nickname'];
		  $json['avatar']=$wxinfo['headimgurl'];
		  $json['bindcode']=$bindcode;
	      // echo json_encode($json,true);exit;
	      show_json_w(3,$json,'成功');
		}
	}
	//绑定
	public function wxbind(){
		global $_W,$_GPC;
		$mobile = !empty($_GPC['mobile']) ? trim($_GPC['mobile']) : show_json_w(-1,null, '手机号不能为空！');
		$yqm = !empty($_GPC['yqm']) ? trim($_GPC['yqm']) : show_json_w(-1,null, '邀请码不能为空！');
		//$password = !empty($_GPC['password']) ? trim($_GPC['password']) : show_json_w(-1,null, '登录密码不能为空！');
		$code = !empty($_GPC['code']) ? $_GPC['code'] : show_json_w(-1,null, '验证码不能为空！');
		// m('game')->checkmobile($mobile,$code);//检验验证码
		$set = pdo_fetch('select * from ' . tablename('wx_shop_game_set') . ' where uniacid=:uniacid ',array(':uniacid'=>$_W['uniacid']));
		$redis=m("game")->getRedis();
		if($yqm == '666666') {
			$agentid = 0;
		} else {
			$shang = pdo_fetch('select id from ' . tablename('wx_shop_member') . ' where yqm=:yqm limit 1', array(':yqm' => $yqm));
			
			if(empty($shang)) {
				
				show_json_w(-1,null, '邀请码错误,请认真填写！');
			
			} else {
				$agentid = $shang['id'];
			}
		}
		$bindcode = $_GPC['bindcode'];
		$wxinfo = $redis->get($bindcode);
		if($wxinfo){
			$wxinfo = unserialize($wxinfo);
			$info = pdo_fetch('select id,nickname,avatar,is_wx from ' . tablename('wx_shop_member') . ' where mobile=:mobile', array(':mobile'=>$mobile));
			//手机号码存在 更换openid
			if($info){
				pdo_update("wx_shop_member",array("openid"=>$wxinfo['openid'],"is_wx"=>1),array("mobile"=>$mobile));
				$data['token'] = md5(serialize($wxinfo['openid']).time());
				$data['logintime']=time();
				
				$data['is_jh']=1;
				pdo_update("wx_shop_member",$data,array("id"=>$info['id']));
				$returnjson['token']=$data['token'];
				$returnjson['nickname']=$info['nickname'];
				$returnjson['avatar']=$info['avatar'];
				$returnjson['uid']=$info['id'];
				show_json_w(1,$returnjson,'成功');
			}else{
				$UserData['openid']=$wxinfo['openid'];
				$UserData['mobile']=$mobile;
				$UserData['pwd']=md5(123456);
				$UserData['nickname']=$wxinfo['nickname'];
				$UserData['avatar']=$wxinfo['headimgurl'];
				$UserData['createtime']=time();
				
				//登录时间
				$UserData['logintime']=time();
				$UserData['uniacid']=$_W['uniacid'];
				$UserData['is_wx']=1;
				
				$UserData['is_jh']=1;
				$UserData['agentid'] = $agentid;
				$UserData['token'] = md5(serialize($wxinfo['openid']).time());
				while (1) {
					$yqm = m('member')->getYqm(6);
					$count = pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_member') . ' where uniacid=:uniacid and yqm=:yqm',array(':uniacid'=>$_W['uniacid'],'yqm'=>$yqm));
					if($count <= 0) {
						break;
					}
				}
				$UserData['yqm'] = $yqm;
				pdo_insert('wx_shop_member',$UserData);
				$UserData['id'] = pdo_insertid();
				//微信绑定赠送彩蛋币
				$money = $set['wxbind'];
				m('game')->setMoney($UserData['id'],'credit_b',$money,'微信绑定','微信绑定赠送彩蛋币'.$money);
				$returnjson['token']=$UserData['token'];
				$returnjson['nickname']=$wxinfo['nickname'];
				$returnjson['avatar']=$wxinfo['headimgurl'];
				$returnjson['uid']=$UserData['id'];
		  		file_put_contents(dirname(__FILE__).'/returnjson',json_encode( $returnjson)); 
				show_json_w(1,$returnjson,'成功');
			}
		}else{
			 show_json_w(-1,null,"绑定失败，请重新授权登录");
		}
	}
    // public function
	public function sendcode(){
		//发送短信
		global $_W,$_GPC;

    	$mobile = !empty($_GPC['mobile']) ? trim($_GPC['mobile']) : show_json_w(-1,null, '手机号不能为空！');
//            show_json_w(1,null,'成功！');
             m('game')->sendcode($mobile,$_GPC['isres']);
        }

    public function mobilelogin(){

	    global $_W;
	    global $_GPC;

        $mobile = !empty($_GPC['mobile']) ? trim($_GPC['mobile']) : show_json_w(-1,null, '手机号不能为空！');

        $code = !empty($_GPC['code']) ? $_GPC['code'] : show_json_w(-1,null, '验证码不能为空！');
        // m('game')->checkmobile($mobile,$code);//检验验证码
        $set = pdo_fetch('select * from ' . tablename('wx_shop_game_set') . ' where uniacid=:uniacid ',array(':uniacid'=>$_W['uniacid']));

        $redis=m("game")->getRedis();

        if($redis->get($mobile.'codetime') <= time() || $code != $redis->get($mobile."code")){

            show_json_w(-1,null, '验证码错误或已过期！');
        }

        $agentid = 0;

        $info = pdo_fetch('select id,nickname,avatar,openid from ' . tablename('wx_shop_member') . ' where mobile=:mobile', array(':mobile'=>$mobile));
        //手机号码存在 更换openid
        if($info){
            $data['token'] = md5(serialize($info['openid']).time());
            $data['logintime']=time();
            $data['is_jh']=1;
            pdo_update("wx_shop_member",$data,array("id"=>$info['id']));
            $returnjson['token']=$data['token'];
            $returnjson['nickname']=$info['nickname'];
            $returnjson['avatar']=$_W['siteroot'].$info['avatar'];
            $returnjson['uid']=$info['id'];
            show_json_w(1,$returnjson,'成功');
        }else{
            $UserData['mobile']=$mobile;
            $UserData['pwd']=md5(123456);
            $UserData['nickname']= substr($mobile,0,3).'****'.substr($mobile,7,11);
            $UserData['openid']= md5($mobile);
            $UserData['avatar']= $_W['siteroot'].'../addons/wx_shop/static/images/noface.png';
            $UserData['createtime']=time();

            //登录时间
            $UserData['logintime']=time();
            $UserData['uniacid']=$_W['uniacid'];
            $UserData['is_wx']=0;

            $UserData['is_jh']=1;
            $UserData['agentid'] = $agentid;
            $UserData['token'] = md5(serialize($UserData['openid']).time());
            while (1) {
                $yqm = m('member')->getYqm(6);
                $count = pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_member') . ' where uniacid=:uniacid and yqm=:yqm',array(':uniacid'=>$_W['uniacid'],'yqm'=>$yqm));
                if($count <= 0) {
                    break;
                }
            }
            $UserData['yqm'] = $yqm;
            pdo_insert('wx_shop_member',$UserData);
            $UserData['id'] = pdo_insertid();

            $returnjson['token']=$UserData['token'];
            $returnjson['nickname']=$UserData['nickname'];
            $returnjson['avatar']=$UserData['avatar'];
            $returnjson['uid']=$UserData['id'];
            file_put_contents(dirname(__FILE__).'/returnjson',json_encode( $returnjson));
            show_json_w(1,$returnjson,'成功');
        }

    }
}
    function alltable($condition){
	    for ($i=0; $i < 10; $i++) {
            if($i == 9) {
                $sql .= 'select * from ' . tablename('wx_shop_game_redlog'.$i) . ' where '.$condition;
            } else {
                $sql .= 'select * from ' . tablename('wx_shop_game_redlog'.$i) . ' where '.$condition.' union all ';
            }
	    }
	    return $sql;
	// echo '<pre>';
	//     print_r($sql);
	// echo '</pre>';
	// exit;

    }


?>