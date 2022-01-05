<?php

if (!(defined('IN_IA'))) 

{

	exit('Access Denied');

}

class Answer_WxShopPage extends MobilePage

{

	public function main()

	{

		global $_W,$_GPC;

	}



	//定义members表 dt_one，dt_two是答题的错题记录和状态（1表示过关）  错题类型为1

	public function answer() 

	{

		global $_W,$_GPC;

		$token = $_GPC['token'];


		$uid=pdo_fetchcolumn("select id from ".tablename("wx_shop_member")." where token=:token limit 1",array(":token"=>trim($token)));

		if(empty($uid)) {
			show_json(-2,'token错误!');
		}


		//查找用户上次答题数据

		$member = pdo_fetch('select dt_one,dt_two from ' . tablename('wx_shop_member') . ' where uniacid=:uniacid and id=:id',array(':uniacid'=>$_W['uniacid'],':id'=>$uid));


		//判断用户是否有过答题

		if($member['dt_one'] == 1 && $member['dt_two'] == 1)

		{

			show_json(-1,'恭喜你闯关成功!');

		}

		$condition = ' uniacid=:uniacid and type=:type order by id asc';

		$params = array(':uniacid' => $_W['uniacid'], ':type' => 0);

		//检测是否有错题
		$dt_onestr = strpos($member['dt_one'],',');
		$dt_twostr = strpos($member['dt_two'],',');
		if((!empty($member['dt_one'])&&$dt_onestr)  || !empty($member['dt_two']) || ($member['dt_one']===1&&$dt_twostr || (!empty($member['dt_one'])&&$member['dt_one']!=1))){
		$param = array(':uniacid' => $_W['uniacid']);
				//错题一
				$dt = $member['dt_one'];

			if(!empty($member['dt_two'])){
				$dt = $member['dt_two'];

			}
			$list = pdo_fetchall('SELECT * FROM ' . tablename('wx_shop_game_dtlist') . ' WHERE  ' . ' uniacid=:uniacid and id in ( '.$dt.') order by id asc',$param);

			show_json(1,array('timu'=>$list));

		}
		
		if(!empty($member['dt_one']) && !$dt_onestr){

		$params[':type'] = 1;	

		}

		$list = pdo_fetchall('SELECT * FROM ' . tablename('wx_shop_game_dtlist') . ' WHERE ' . $condition, $params);
		
		//返回

		show_json(1,array('timu'=>$list));

	}





	//答案

	public function keepanswer()

	{

		global $_W,$_GPC;

		$token = $_GPC['token'];


		$uid=pdo_fetchcolumn("select id from ".tablename("wx_shop_member")." where token=:token limit 1",array(":token"=>trim($token)));

		if(empty($uid)) {
				show_json(-2,'token错误!');
		}

		$answer = $_POST['answer'];//用户答案

		$type = $_GPC['type'];//题目类型



		if(empty($answer)|| empty($uid)){

			show_json(-1,'请重新答题!');

		}

		if(is_null(json_decode($answer))){

			show_json(-1,'数据有误，请重新提交!');

		}

		$history = '';



		//数据转换

		$dt_type = $type?'dt_two':'dt_one';

		$answers =  json_decode($answer, true);

		foreach ($answers as $key => $value) {

			//根据id查找答案对比 记录错误写进用户数据库

			$sql = "SELECT abcd FROM".tablename('wx_shop_game_dtlist')."  WHERE id=:id AND uniacid=:uniacid LIMIT 1";

			$a_w = pdo_fetchcolumn($sql,array(':id'=>$key,':uniacid'=>$_W['uniacid']));

			if($a_w != $value){

				$history .= $key.',';	

			}

		}



		//错题返回给你 1是成功答题 2有错题

		if(strpos($history,',')){

				//错题写入数据库

				$history = $history?$history:1;

				$history = substr($history,0,strlen($history)-1);

				pdo_update('wx_shop_member',array("$dt_type" => $history),array('id'=>$uid));//写入数据库

				$params = array(':uniacid' => $_W['uniacid']);

				$list = pdo_fetchall('SELECT * FROM ' . tablename('wx_shop_game_dtlist') . ' WHERE  ' . ' uniacid=:uniacid and id in ( '.$history.') order by id asc',$params);

				show_json(2 , array('timu'=>$list));

		}

		

		//答题奖励 credit_b币

		$sql = "SELECT ".$dt_type." FROM".tablename('wx_shop_game_set')."  WHERE 1=1 AND uniacid=:uniacid LIMIT 1";

		$dt_b = pdo_fetchcolumn($sql,array(':uniacid'=>$_W['uniacid']));

		m('game')->setMoney($uid,'credit_b',$dt_b,'答题奖励','答题赠送彩蛋币'.$dt_b);

		pdo_update('wx_shop_member',array("$dt_type" => 1),array('id'=>$uid));//写入数据库

		show_json(1,'恭喜你答题成功!');

	}

	public function xieyiarticle(){
		global $_W,$_GPC;
		$cid = $_GPC['article_category'];//用户26 隐私27	
		if($cid){
			$params = array(':uniacid' => $_W['uniacid'], ':article_category' => $cid);  
			$sql = "SELECT id,article_category,article_title,article_content FROM".tablename('wx_shop_article')."  WHERE article_category=:article_category AND uniacid=:uniacid order by id asc limit 1";
			$result = pdo_fetchall($sql, $params);
			if(!$result){
				show_json(-1,'参数错误');
			}
			$rs['content'] = $result;
			show_json(1,$rs);
		}
		show_json(-1,'分类错误');
	}
}

?>