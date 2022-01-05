<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
class index_WxShopPage //extends PluginMobilePage 
{
	private $new = false;
	public $model;
	public $set;
	public function __construct() 
	{
		global $_W;
		global $_GPC;
		
		$this->model = m('plugin')->loadModel('task');
		
		$this->set = $this->model->getSet();
		// var_dump($this->set);
		
		$this->new = $this->model->isnew();
		// echo 11;exit;

	}
	// 获取会员
	public function getMember($openid = '')
	{
		global $_W;
		$uid = (int) $openid;

		if ($uid == 0) {
			$info = pdo_fetch('select * from ' . tablename('wx_shop_member') . ' where  openid=:openid and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));

			if (empty($info)) {
				if (strexists($openid, 'sns_qq_')) {
					$openid = str_replace('sns_qq_', '', $openid);
					$condition = ' openid_qq=:openid ';
					$bindsns = 'qq';
				}
				else if (strexists($openid, 'sns_wx_')) {
					$openid = str_replace('sns_wx_', '', $openid);
					$condition = ' openid_wx=:openid ';
					$bindsns = 'wx';
				}
				else {
					if (strexists($openid, 'sns_wa_')) {
						$openid = str_replace('sns_wa_', '', $openid);
						$condition = ' openid_wa=:openid ';
						$bindsns = 'wa';
					}
				}

				if (!empty($condition)) {
					$info = pdo_fetch('select * from ' . tablename('wx_shop_member') . ' where ' . $condition . '  and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));

					if (!empty($info)) {
						$info['bindsns'] = $bindsns;
					}
				}
			}
		}
		else {
			$info = pdo_fetch('select * from ' . tablename('wx_shop_member') . ' where id=:id and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':id' => $openid));
		}

		if (!empty($info)) {
			if (!strexists($info['avatar'], 'http://') && !strexists($info['avatar'], 'https://')) {
				$info['avatar'] = tomedia($info['avatar']);
			}

			if ($_W['ishttps']) {
				$info['avatar'] = str_replace('http://', 'https://', $info['avatar']);
			}

			$info = $this->updateCredits($info);
		}

		return $info;
	}

	// public function getMyTaskList($condition = '=')
	// {
	// 	global $_W;
	// 	$condition2 = '';

	// 	if ($condition == '=') {
	// 		$condition2 .= ' AND  a.endtime > ' . time();
	// 	}


	// 	$sql = 'SELECT a.* FROM ' . tablename('wx_shop_task_extension_join') . ' a JOIN ' . tablename('wx_shop_task') . ' b ON a.taskid = b.id WHERE a.openid = :openid AND a.completetime ' . $condition . ' 0 ' . $condition2 . ' AND a.uniacid = :uniacid';
	// 	// var_dump($sql);
	// 	return pdo_fetchall($sql, array(':uniacid' => $_W['uniacid'], ':openid' => $_W['openid']));
	// }



	public function main() 
	{
		if ($this->new) 
		{
			$this->main_new();
			exit();
		}
		global $_W;
		global $_GPC;
		$openid = !empty($_W['openid'])?$_W['openid']:$_GPC['openid'];
		$member = m('member')->getMember($openid);
		$list1 = $this->model->getUserTaskList(1);
		foreach($list1 as $k=>$v){
			$list1[$k]['explain']=htmlspecialchars_decode($v['explain']);
			$list1[$k]['require_data']= unserialize($v['require_data']);
			$list1[$k]['reward_data']= unserialize($v['reward_data']);
			$list1[$k]['logo']=tomedia($v['logo']);
		}
		$list2 = $this->model->getUserTaskList(2);
		foreach ($list2 as $key => $value) {
			
			$list2[$key]['require_data'] = unserialize($value['require_data']);
			$list2[$key]['reward_data'] = unserialize($value['reward_data']);
			$list2[$key]['explain'] = htmlspecialchars_decode($value['explain']); 
			$list2[$key]['logo'] = tomedia($value['logo']); 
			
		}

		$poster = $this->taskposter();
        foreach($poster[0] as $k=>$v){
        	 $poster[0][$k]['bg']= tomedia($v['bg']);
             $poster[0][$k]['titleicon']= tomedia($v['titleicon']);
             $poster[0][$k]['poster_banner']=tomedia($v['poster_banner']);
        }
		$bgimg = pdo_get('wx_shop_task_default', array('uniacid' => $_W['uniacid']), array('bgimg'));
		$bgimg['bgimg'] = tomedia($bgimg['bgimg']);

		show_json(1,array('list1'=>$list1,'list2'=>$list2,'member'=>$member,'poster'=>$poster,'bgimg'=>$bgimg));
		// include $this->template();
	}
// 新任务
	public function newtask() 
	{
		global $_W;
		global $_GPC;
		$id = $_GPC['id'];
		$res = $this->model->getNewTask($id);
        if (is_string($res))
        {
            show_json(0, $res);
        }

		show_json(1, $res);
	}
	// 我的任务
	public function mytask() 
	{
		global $_W;
		global $_GPC;
		if(empty($_W['openid'])){
			$_W['openid']=$_GPC['openid'];
		}
		$dolist = $this->model->getMyTaskList();
		
		foreach($dolist as $k=>$v){
		    $dolist[$k]['require_data']=unserialize($v['require_data']);
		    $dolist[$k]['progress_data']=unserialize($v['progress_data']);
		    $dolist[$k]['reward_data']=unserialize($v['reward_data']);
		    $dolist[$k]['pickuptime']=date('y-m-d',$v['pickuptime']);
		    $dolist[$k]['endtime']=date('y-m-d',$v['endtime']);
            $dolist[$k]['logo']=tomedia($v['logo']);
            foreach ($dolist[$k]['require_data'] as $key => $value) {
            	$dolist[$k]['require_data'][$key]['returnName'] = $this->model->returnName($key);
            }
		}
		$donelist = $this->model->getMyTaskList('>');
		// var_dump($dolist);
		$poster = $this->taskposter();

		$fail = $this->model->failTask();
		foreach($fail as $k=>$v){
			$fail[$k]['require_data'] = unserialize($v['require_data']);
			$fail[$k]['progress_data'] = unserialize($v['progress_data']);
			$fail[$k]['reward_data'] = unserialize($v['reward_data']);
			$fail[$k]['logo'] = tomedia($v['logo']);
			$fail[$k]['pickuptime']=date('y-m-d',$v['pickuptime']);
			$fail[$k]['endtime']=date('y-m-d',$v['endtime']);
		}
		
      
		// foreach($dolist as $k=>$v){
		// 	$require = $v['require_data'];
			
			
		// }
		//     foreach($require as $ek=>$v){
  //              $require['returnName']=$this->model->returnName($ek);
  //           }
        	   

		show_json(1,array('dolist'=>$dolist,'donelist'=>$donelist,'poster'=>$poster,'fail'=>$fail,'require'=>$require));
		// include $this->template();
	}
	// 任务详情
	public function detail() 
	{
		if ($this->new) 
		{
			$this->detail_new();
			exit();
		}
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);
		$poster = intval($_GPC['poster']);
		if ($poster) 
		{  
			$sql = 'SELECT * FROM ' . tablename('wx_shop_task_poster') . ' WHERE id = :id';
			$detail = pdo_fetch($sql, array(':id' => $id));
			// $detail['require_data']=unserialize($detail['require_data']);
			$detail['createtime']=date('Y-m-d',$detail['createtime']);
			$detail['timestart']=date('Y-m-d',$detail['timestart']);
			$detail['timeend']=date('Y-m-d',$detail['timeend']);
			 $detail['reward_data']=unserialize($detail['reward_data']);
			 // var_dump($detail['reward_data']);
			$reward_data = $detail['reward_data'];
            // foreach($detail as $k=>$v){
            // 	$detail[$k]['reward_data']=unserialize($v['reward_data']);
            // }
            // include $this->template();
			show_json(2,array('detail'=>$detail,'reward_data'=>$reward_data,'poster'=>$poster));
		}
		else 
		{
			$sql = 'SELECT * FROM ' . tablename('wx_shop_task') . ' WHERE id = :id';
			$detail = pdo_fetch($sql, array(':id' => $id));
			$detail['explain'] = tomedia($detail['explain']); 
			$detail['reward_data']=unserialize($detail['reward_data']);
			$detail['require_data']=unserialize($detail['require_data']);
			$detail['logo']=tomedia($detail['logo']);
			$reward_data = $detail['reward_data'];
			$require_data = $detail['require_data'];
            // var_dump($this->model);
            $detail['starttime']=date('Y-m-d',$detail['starttime']);
			$detail['endtime']=date('Y-m-d',$detail['endtime']);
            foreach($require_data as $k=>$v){
            	if(!empty($v['num'])){
            		$require_data[$k]['returnName'] =$this->model->returnName($k);
            	}
            	
            }
		show_json(1,array('detail'=>$detail,'reward_data'=>$reward_data,'require_data'=>$require_data,'getDesc'=>$this->getDesc(),'hgetDesc'=>htmlspecialchars_decode($this->getDesc()),'poster'=>$poster));
		}
		
		show_json(1,array('poster'=>$poster, 'detail'=>$detail));
		// include $this->template();
	}
    // 任务海报
	public function taskposter() 
	{
		global $_W;
		global $_GPC;
		$tabpage = $_GPC['tabpage'];
		// var_dump($tabpage);
		// $openid = trim($_W['openid']);
		$openid = !empty(trim($_W['openid']))?trim($_W['openid']):$_GPC['openid'];
		// var_dump($openid);
		$is_menu = $this->model->getdefault('menu_state');
	     // var_dump($is_menu);
		$member = m('member')->getMember($openid);
		$now_time = time();
		// var_dump($now_time);
		$task_sql = 'SELECT * FROM ' . tablename('wx_shop_task_poster') . ' WHERE timestart<=' . $now_time . ' AND timeend>' . $now_time . ' AND uniacid=' . $_W['uniacid'] . ' AND `status`=1 AND `is_delete`=0 ORDER BY `createtime` DESC';
		$task_list = pdo_fetchall($task_sql);
		 // print_r($task_list);
		foreach ($task_list as $key => $val ) 
		{
			$task_list[$key]['reward_data'] = unserialize($val['reward_data']);
			$task_list[$key]['poster_reward'] = unserialize($val['poster_reward']);

			$task_list[$key]['bg'] = tomedia($val['bg']);
			$task_list[$key]['titleicon'] = tomedia($val['titleicon']);
			$task_list[$key]['poster_banner'] = tomedia($val['poster_banner']);
			$task_list[$key]['respthumb'] = tomedia($val['respthumb']);


			if ($val['poster_type'] == 1) 
			{
				$val['reward_data'] = unserialize($val['reward_data']);
				$recward = $val['reward_data']['rec'];
				if (isset($recward['credit']) && (0 < $recward['credit'])) 
				{
					$task_list[$key]['is_credit'] = 1;
				}
				if (isset($recward['money']['num']) && (0 < $recward['money']['num'])) 
				{
					$task_list[$key]['is_money'] = 1;
				}
				if (isset($recward['bribery']) && (0 < $recward['bribery'])) 
				{
					$task_list[$key]['is_bribery'] = 1;
				}
				if (isset($recward['goods']) && count($recward['goods'])) 
				{
					$task_list[$key]['is_goods'] = 1;
				}
				if (isset($recward['coupon']['total']) && (0 < $recward['coupon']['total'])) 
				{
					$task_list[$key]['is_coupon'] = 1;
				}
			}
			else if ($val['poster_type'] == 2) 
			{
				$val['reward_data'] = unserialize($val['reward_data']);
				$recward = $val['reward_data']['rec'];
				foreach ($recward as $k => $v ) 
				{
					if (isset($v['credit']) && (0 < $v['credit'])) 
					{
						$task_list[$key]['is_credit'] = 1;
					}
					if (isset($v['money']['num']) && (0 < $v['money']['num'])) 
					{
						$task_list[$key]['is_money'] = 1;
					}
					if (isset($v['bribery']) && (0 < $v['bribery'])) 
					{
						$task_list[$key]['is_bribery'] = 1;
					}
					if (isset($v['goods']) && count($v['goods'])) 
					{
						$task_list[$key]['is_goods'] = 1;
					}
					if (isset($v['coupon']['total']) && (0 < $v['coupon']['total'])) 
					{
						$task_list[$key]['is_coupon'] = 1;
					}
				}
			}
		}
		$running_sql = 'SELECT `join`.*,`task`.title,`task`.reward_data AS `poster_reward`,`task`.titleicon,`task`.poster_type FROM ' . tablename('wx_shop_task_join') . ' AS `join` LEFT JOIN ' . tablename('wx_shop_task_poster') . ' AS `task` ON `join`.task_id=`task`.`id` WHERE `join`.`failtime`>' . $now_time . ' AND `join`.`join_user`="' . $openid . '" AND `join`.uniacid=' . $_W['uniacid'] . ' AND `join`.`is_reward` = 0 AND `task`.`is_delete` = 0 ORDER BY `join`.`addtime` DESC LIMIT 0,15';
		$task_running = pdo_fetchall($running_sql);
		foreach ($task_running as $key => $val ) 
		{
			$task_running[$key]['poster_reward'] = unserialize($val['poster_reward']);
			$task_running[$key]['reward_data'] = unserialize($val['reward_data']);

			$task_running[$key]['bg'] = tomedia($val['bg']);
			$task_running[$key]['titleicon'] = tomedia($val['titleicon']);
			$task_running[$key]['poster_banner'] = tomedia($val['poster_banner']);
			$task_running[$key]['respthumb'] = tomedia($val['respthumb']);
			if ($val['poster_type'] == 1) 
			{
				$val['reward_data'] = unserialize($val['poster_reward']);
				$recward = $val['reward_data']['rec'];
				if (isset($recward['credit']) && (0 < $recward['credit'])) 
				{
					$task_running[$key]['is_credit'] = 1;
				}
				if (isset($recward['money']['num']) && (0 < $recward['money']['num'])) 
				{
					$task_running[$key]['is_money'] = 1;
				}
				if (isset($recward['bribery']) && (0 < $recward['bribery'])) 
				{
					$task_running[$key]['is_bribery'] = 1;
				}
				if (isset($recward['goods']) && count($recward['goods'])) 
				{
					$task_running[$key]['is_goods'] = 1;
				}
				if (isset($recward['coupon']['total']) && (0 < $recward['coupon']['total'])) 
				{
					$task_running[$key]['is_coupon'] = 1;
				}
			}
			else if ($val['poster_type'] == 2) 
			{
				$val['reward_data'] = unserialize($val['poster_reward']);
				$recward = $val['reward_data']['rec'];
				foreach ($recward as $k => $v ) 
				{
					if (isset($v['credit']) && (0 < $v['credit'])) 
					{
						$task_running[$key]['is_credit'] = 1;
					}
					if (isset($v['money']['num']) && (0 < $v['money']['num'])) 
					{
						$task_running[$key]['is_money'] = 1;
					}
					if (isset($v['bribery']) && (0 < $v['bribery'])) 
					{
						$task_running[$key]['is_bribery'] = 1;
					}
					if (isset($v['goods']) && count($v['goods'])) 
					{
						$task_running[$key]['is_goods'] = 1;
					}
					if (isset($v['coupon']['total']) && (0 < $v['coupon']['total'])) 
					{
						$task_running[$key]['is_coupon'] = 1;
					}
				}
			}
		}
		$complete_sql = 'SELECT `join`.*,`task`.title,`task`.titleicon,`task`.poster_type FROM ' . tablename('wx_shop_task_join') . ' AS `join` LEFT JOIN ' . tablename('wx_shop_task_poster') . ' AS `task` ON `join`.task_id=`task`.`id` WHERE `join`.uniacid=' . $_W['uniacid'] . ' AND `join`.`join_user`="' . $openid . '" AND `join`.`is_reward`=1 AND `task`.`is_delete` = 0 ORDER BY `join`.`addtime` DESC LIMIT 0,15';
		$task_complete = pdo_fetchall($complete_sql);

		foreach ($task_complete as $key => $val ) 
		{
			$task_complete[$key][$key]['poster_reward'] = unserialize($val['poster_reward']);
			$task_complete[$key]['reward_data'] = unserialize($val['reward_data']);

			$task_complete[$key]['bg'] = tomedia($val['bg']);
			$task_complete[$key]['titleicon'] = tomedia($val['titleicon']);
			$task_complete[$key]['poster_banner'] = tomedia($val['poster_banner']);
			$task_complete[$key]['respthumb'] = tomedia($val['respthumb']);
			if ($val['poster_type'] == 1) 
			{
				$task_complete[$key]['reward_data'] = unserialize($val['reward_data']);
				$val['reward_data'] = unserialize($val['reward_data']);
				$recward = $val['reward_data'];
				if (isset($recward['credit']) && (0 < $recward['credit'])) 
				{
					$task_complete[$key]['is_credit'] = 1;
				}
				if (isset($recward['money']['num']) && (0 < $recward['money']['num'])) 
				{
					$task_complete[$key]['is_money'] = 1;
				}
				if (isset($recward['bribery']) && (0 < $recward['bribery'])) 
				{
					$task_complete[$key]['is_bribery'] = 1;
				}
				if (isset($recward['goods']) && count($recward['goods'])) 
				{
					$task_complete[$key]['is_goods'] = 1;
				}
				if (isset($recward['coupon']['total']) && (0 < $recward['coupon']['total'])) 
				{
					$task_complete[$key]['is_coupon'] = 1;
				}
			}
			else if ($val['poster_type'] == 2) 
			{
				$val['reward_data'] = unserialize($val['reward_data']);
				$recward = $val['reward_data'];
				foreach ($recward as $k => $v ) 
				{
					if (isset($v['credit']) && (0 < $v['credit'])) 
					{
						$task_complete[$key]['is_credit'] = 1;
					}
					if (isset($v['money']['num']) && (0 < $v['money']['num'])) 
					{
						$task_complete[$key]['is_money'] = 1;
					}
					if (isset($v['bribery']) && (0 < $v['bribery'])) 
					{
						$task_complete[$key]['is_bribery'] = 1;
					}
					if (isset($v['goods']) && count($v['goods'])) 
					{
						$task_complete[$key]['is_goods'] = 1;
					}
					if (isset($v['coupon']['total']) && (0 < $v['coupon']['total'])) 
					{
						$task_complete[$key]['is_coupon'] = 1;
					}
				}
			}
		}
		$faile_sql = 'SELECT `join`.*,`task`.title,`task`.reward_data AS `poster_reward`,`task`.titleicon,`task`.poster_type FROM ' . tablename('wx_shop_task_join') . ' AS `join` LEFT JOIN ' . tablename('wx_shop_task_poster') . ' AS `task` ON `join`.task_id=`task`.`id` WHERE `join`.`failtime`<=' . $now_time . ' AND `join`.`join_user`="' . $openid . '" AND `join`.uniacid=' . $_W['uniacid'] . ' AND `join`.`is_reward`=0 AND `task`.`is_delete` = 0 ORDER BY `join`.`addtime` DESC LIMIT 0,15';
		$faile_complete = pdo_fetchall($faile_sql);
		foreach ($faile_complete as $key => $val ) 
		{
			$faile_complete[$key][$key]['poster_reward'] = unserialize($val['poster_reward']);
			$faile_complete[$key]['reward_data'] = unserialize($val['reward_data']);

			$faile_complete[$key]['bg'] = tomedia($val['bg']);
			$faile_complete[$key]['titleicon'] = tomedia($val['titleicon']);
			$faile_complete[$key]['poster_banner'] = tomedia($val['poster_banner']);
			$faile_complete[$key]['respthumb'] = tomedia($val['respthumb']);

			if ($val['poster_type'] == 1) 
			{
				$val['reward_data'] = unserialize($val['poster_reward']);
				$recward = $val['reward_data']['rec'];
				if (isset($recward['credit']) && (0 < $recward['credit'])) 
				{
					$faile_complete[$key]['is_credit'] = 1;
				}
				if (isset($recward['money']['num']) && (0 < $recward['money']['num'])) 
				{
					$faile_complete[$key]['is_money'] = 1;
				}
				if (isset($recward['bribery']) && (0 < $recward['bribery'])) 
				{
					$faile_complete[$key]['is_bribery'] = 1;
				}
				if (isset($recward['goods']) && count($recward['goods'])) 
				{
					$faile_complete[$key]['is_goods'] = 1;
				}
				if (isset($recward['coupon']['total']) && (0 < $recward['coupon']['total'])) 
				{
					$faile_complete[$key]['is_coupon'] = 1;
				}
			}
			else if ($val['poster_type'] == 2) 
			{
				$val['reward_data'] = unserialize($val['poster_reward']);
				$recward = $val['reward_data']['rec'];
				foreach ($recward as $k => $v ) 
				{
					if (isset($v['credit']) && (0 < $v['credit'])) 
					{
						$faile_complete[$key]['is_credit'] = 1;
					}
					if (isset($v['money']['num']) && (0 < $v['money']['num'])) 
					{
						$faile_complete[$key]['is_credit'] = 1;
					}
					if (isset($v['bribery']) && (0 < $v['bribery'])) 
					{
						$faile_complete[$key]['is_money'] = 1;
					}
					if (isset($v['goods']) && count($v['goods'])) 
					{
						$faile_complete[$key]['is_goods'] = 1;
					}
					if (isset($v['coupon']['total']) && (0 < $v['coupon']['total'])) 
					{
						$faile_complete[$key]['is_coupon'] = 1;
					}
				}
			}
		}
		// var_dump( $task_complete, $faile_complete);die;
		return array($task_list, $task_running, $task_complete, $faile_complete);
	}
   
	public function gettask() 
	{
		global $_W;
		global $_GPC;
		$content = trim($_GPC['content']);
		$timeout = 10;
		$url = mobileUrl('task/build', array('timestamp' => TIMESTAMP), true);
		ihttp_request($url, array('openid' => $_W['openid'], 'content' => urlencode($content)), array(), $timeout);
		show_json(1);
	}
	// 获取赏金
	public function getreward() 
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);
	$rewarded = pdo_get('wx_shop_task_extension_join', array('uniacid' => $_W['uniacid'], 'id' => $_GPC['id']));
		$rewarded = $rewarded['rewarded'];
		$rewarded = @unserialize($rewarded['rewarded']);
		// $rewarded = unserialize(isset($rewarded['rewarded'])?$rewarded['rewarded']:"");
		if (empty($rewarded)) 
		{
			show_json(0, '奖励发放失败');
		}
		$this->model->sendReward($rewarded, 1, 0, $rewarded['id']);
		show_json(1, '奖励已发放');
	}
	// 获取海报图标
	private function getpostericon($id) 
	{
		global $_W;
		global $_GPC;
		return pdo_fetchcolumn('SELECT titleicon FROM ' . tablename('wx_shop_task_poster') . ' WHERE id = :id AND uniacid = :uniacid', array(':id' => $id, ':uniacid' => $_W['uniacid']));
	}

	private function checkJoined($taskid) 
	{
		global $_W;
		$sql = 'SELECT COUNT(*) FROM ' . tablename('wx_shop_task_extension_join') . ' WHERE openid = :openid AND taskid = :taskid';
		return pdo_fetchcolumn($sql, array(':openid' => $_W['openid'], ':taskid' => $taskid));
	}
	private function getDesc() 
	{
		global $_W;
		$sql = 'SELECT `data` FROM ' . tablename('wx_shop_task_default') . ' WHERE uniacid = :uniacid';
		$data = pdo_fetchcolumn($sql, array(':uniacid' => $_W['uniacid']));
		$arr = unserialize($data);
		return unserialize($arr['taskinfo']);
	}

	public function main_new() 
	{
		global $_W;
		global $_GPC;
		// var_dump($_W['openid']);
		$_W['openid']=!empty($_W['openid']) ? $_W['openid'] : $_GPC['openid'];
		// var_dump( $_GPC['openid']);
		$my = m('member')->getInfo($_W['openid']);
		$info = m('member')->getInfo($_W['openid']);
		$tableList = tablename('wx_shop_task_list');
		// var_dump($tableList);
		$tableRecord = tablename('wx_shop_task_record');
		// var_dump($tableRecord);
		$now = date('Y-m-d H:i:s');
		$sql = 'select li.*,re.task_demand,re.task_progress,re.id as rid from ' . $tableList . ' li left join ' . "\r\n" . '                (select *,max(id) from ' . $tableRecord . ' where (stoptime>\'' . $now . '\' or stoptime = \'0000-00-00 00:00:00\') and openid = \'' . $_W['openid'] . '\' and finishtime = 0' . "\r\n" . '                group by taskid order by id desc) re on li.id = re.taskid ' . "\r\n" . '                where li.starttime < \'' . $now . '\' and li.endtime >\'' . $now . '\' and li.uniacid = :uniacid ' . "\r\n" . '                order by li.displayorder desc,li.id desc';
		$params = array(':uniacid' => $_W['uniacid']);
		$list = pdo_fetchall($sql, $params);
		$set = pdo_fetchcolumn('select bg_img from ' . tablename('wx_shop_task_set') . ' where uniacid = ' . $_W['uniacid']);
		if(!empty($list)){
			foreach ($list as $k => $v) {
				$list[$k]['reward'] = json_decode($v['reward'],true);
			}
		}

		show_json(1,array('set'=>$set,'info'=>$info,'list'=>$list,'my'=>$my));
		// include $this->template('task/index_new');
	}
    	//获取赏金 
	public function reward() 
	{
		global $_W;
		$list = $this->rewardlist();

		show_json(1,array('list'=>$list));
		// include $this->template();
	}
	// 赏金表单
	public function rewardlist() 
	{
		global $_W;
		global $_GPC;
		
		$_W['openid']=!empty($_W['openid']) ? $_W['openid'] : $_GPC['openid'];
		// var_dump($_W['openid']);
		$page = intval($_GPC['page']);
		$page = max(1, $page);
		$psize = 100;
		$pstart = ($page - 1) * $psize;
		$sql = 'select * from ' . tablename('wx_shop_task_reward') . ' where openid = :openid and `get` = 1 and uniacid = :uniacid order by gettime desc limit ' . $pstart . ',' . $psize;
		$list = pdo_fetchall($sql, array(':openid' => $_W['openid'], ':uniacid' => $_W['uniacid']));
		$new_arr = array();
		$new_arr = array();
		foreach ($list as $k => $v) {
			if ($v['reward_type'] == 'goods' || $v['reward_type'] == 'coupon') {
				$detail = pdo_fetchcolumn('select reward_data from '.tablename('wx_shop_task_record').' where id=:id and uniacid=:uniacid', array(':id' => $v['recordid'], ':uniacid' => $_W['uniacid']));
				$reward = json_decode($detail, true);
				$i = $k;
				if ($v['reward_type'] == 'goods') {
					foreach ($reward['goods'] as $k1 => $v1) {
						$v['goodsList'] = $v1;
						$new_arr[$i] = $v;
						$i++;
					}
				}
				if ($v['reward_type'] == 'coupon') {
					foreach ($reward['coupon'] as $k1 => $v1) {
						$v['coupon'] = $v1;
						$new_arr[$i] = $v;
						$i++;
					}
				}
			} else {
				$new_arr[$k] = $v;
			}
		}
		return $new_arr;
	}
	// 
	public function mine() 
	{
		global $_W;
		global $_GPC;
		$_W['openid']=!empty($_W['openid']) ? $_W['openid'] : $_GPC['openid'];
		
		$status = intval($_GPC['status']);
		$condition = '';
		$time0 = '\'0000-00-00 00:00:00\'';
		switch ($status) 
		{
			case 1: $condition = ' and (stoptime > "' . date('Y-m-d H:i:s') . '" or stoptime = ' . $time0 . ') and finishtime = ' . $time0;
			break;
			case 2: $condition = ' and finishtime > ' . $time0 . '';
			break;
			case 3: $condition = ' and stoptime != "0000-00-00 00:00:00" and stoptime < \'' . date('Y-m-d H:i:s') . '\' and finishtime = ' . $time0;
			break;
			// default: header('location:' . mobileUrl('task.mine', array('status' => 1)));
			show_json(0, mobileUrl('task.mine', array('status' => 1)));
			exit();
		}
		$sql = 'select * from ' . tablename('wx_shop_task_record') . ' where openid = :openid and uniacid = :uniacid ' . $condition . ' order by id desc';
		$list = pdo_fetchall($sql, array(':openid' => $_W['openid'], ':uniacid' => $_W['uniacid']));

		show_json(1,array('list'=>$list,'status'=>$status));
		// include $this->template();
	}
	// 新任务详情
	public function detail_new() 
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);
		$rid = intval($_GPC['rid']);
		$_W['openid']=!empty($_W['openid']) ? $_W['openid'] : $_GPC['openid'];
		 // var_dump($rid);
		if (!(empty($rid))) 
		{
			$sql = 'select * from ' . tablename('wx_shop_task_record') . ' where id = :id and uniacid = :uniacid';
			$detail = pdo_fetch($sql, array(':id' => $rid, ':uniacid' => $_W['uniacid']));
			$reward = json_decode($detail['reward_data'], true);
			$reward_goods = pdo_fetchall('select * from ' . tablename('wx_shop_task_reward') . ' where recordid = ' . $detail['id'] . ' and openid = \'' . $_W['openid'] . '\' and uniacid = ' . $_W['uniacid'] . ' and isjoiner = 0 and reward_type = \'goods\' and `level` = 0');
			// $reward_goods = pdo_fetchall('select * from'.tablename('wx_shop_task_reward'));
			$reward1 = $reward2 = $reward3 = array();
			$reward_goods1 = $reward_goods2 = $reward_goods3 = array();
			if ($detail['tasktype'] == 'poster') 
			{
				if ($detail['level2'] == 0) 
				{
					$detail['level1'] = $detail['task_demand'];
					$reward1 = $reward;
					$reward_goods1 = $reward_goods;
				}
				else if (($detail['level2'] < $detail['task_demand']) && ($detail['level1'] < $detail['task_demand'])) 
				{
					$reward1 = json_decode($detail['reward_data1'], true);
					$reward_goods1 = pdo_fetchall('select * from ' . tablename('wx_shop_task_reward') . ' where recordid = ' . $detail['id'] . ' and openid = \'' . $_W['openid'] . '\' and uniacid = ' . $_W['uniacid'] . ' and isjoiner = 0 and reward_type = \'goods\' and `level` = 1');
					$reward2 = json_decode($detail['reward_data2'], true);
					$reward_goods2 = pdo_fetchall('select * from ' . tablename('wx_shop_task_reward') . ' where recordid = ' . $detail['id'] . ' and openid = \'' . $_W['openid'] . '\' and uniacid = ' . $_W['uniacid'] . ' and isjoiner = 0 and reward_type = \'goods\' and `level` = 2');
					$reward3 = $reward;
					$reward_goods3 = $reward_goods;
					$detail['level3'] = $detail['task_demand'];
				}
				else 
				{
					$reward1 = json_decode($detail['reward_data1'], true);
					$reward_goods1 = pdo_fetchall('select * from ' . tablename('wx_shop_task_reward') . ' where recordid = ' . $detail['id'] . ' and openid = \'' . $_W['openid'] . '\' and uniacid = ' . $_W['uniacid'] . ' and isjoiner = 0 and reward_type = \'goods\' and `level` = 1');
					$reward2 = $reward;
					$reward_goods2 = $reward_goods;
				}
			}
			$followreward = json_decode($detail['followreward_data'], true);

			$joiner = pdo_fetchall('select DISTINCT openid,headimg,nickname,gettime from ' . tablename('wx_shop_task_reward') . ' where isjoiner = 1 and recordid = ' . $rid . ' and `get`=1 and uniacid = ' . $_W['uniacid']);
		}
		if (empty($detail) && !(empty($id))) 
		  {
			$sql = 'select * from ' . tablename('wx_shop_task_list') . ' where id = :id and uniacid = :uniacid';
			$detail = pdo_fetch($sql, array(':id' => $id, ':uniacid' => $_W['uniacid']));
			$reward = json_decode($detail['reward'], true);
			if ($detail['type'] == 'poster') 
			{
				$detail['tasktype'] = 'poster';
				$detail['level1'] = $detail['demand'];
				$detail['demand'] = max($detail['demand'], $detail['level2'], $detail['level3']);
				if ($detail['level2'] == 0) 
				{
					$reward1 = $reward;
				}
				else if (0 < $detail['level3']) 
				{
					$reward1 = $reward;
					$reward2 = json_decode($detail['reward2'], true);
					$reward3 = json_decode($detail['reward3'], true);
				}
				else if (0 < $detail['level2']) 
				{
					$reward1 = $reward;
					$reward2 = json_decode($detail['reward2'], true);
				}
			}
			$followreward = json_decode($detail['followreward'], true);
	    }

		if (empty($detail)) 
		{
			
			show_json(0,'任务不存在');
		}

		!(empty($detail['tasktype'])) && ($type = $detail['tasktype']);

		!(empty($detail['type'])) && ($type = $detail['type']);

		$taskType = $this->model->getTaskType($type);
		$desc = $taskType['verb'];
		if (!(empty($taskType['unit']))) 
		{
			$desc .= $detail['task_demand'] . $detail['demand'] . $taskType['unit'];
		}
		if (isset($detail['tasktype']) && ($detail['tasktype'] == 'poster')) 
		{
			$poster = $this->model->create_poster(array('id' => $detail['id'], 'design_data' => $detail['design_data'], 'design_bg' => $detail['design_bg']));
		}
        // include $this->template('task/detail_new');
        
	   show_json(1,array('rid'=>$rid,'detail'=>$detail,'reward'=>$reward,'taskType'=>$taskType,'desc'=>$desc,  'reward_goods'=>$reward_goods, 'followreward'=>$followreward, 'reward_goods1'=>$reward_goods1,'reward_goods2'=>$reward_goods2,'reward_goods3'=>$reward_goods3,'reward1'=>$reward1,'reward2'=>$reward2,'reward3'=>$reward3,'poster'=>$poster,'reward_goods'=>$reward_goods));
	}
    // 海报视图
	public function viewposter() 
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);
	}
    // 选择任务
	public function picktask() 
	{
		global $_W;
		global $_GPC;
		if ($_W['ispost']) 
		{
			$openid = $_W['openid'];
			$taskid = intval($_GPC['id']);
			empty($taskid) && show_json(0, '任务不存在');
			$ret = $this->model->pickTask($taskid, $openid);
			if (is_error($ret)) 
			{
				show_json(0, $ret['message']);
			}
			logg('id', $ret);
			show_json(1, $ret);
		}
	}
	// 发送我们聊天
	public function sendtowechat() 
	{
		global $_W;
		global $_GPC;
		$recordid = intval($_GPC['recordid']);
		$mediaid = pdo_fetchcolumn('select mediaid from ' . tablename('wx_shop_task_qr') . ' where recordid = :recordid', array(':recordid' => $recordid));
		$ret = m('message')->sendImage($_W['openid'], $mediaid);
		if (is_error($ret)) 
		{
			show_json(0, $ret['message']);
		}
		show_json(1);
	}
	// 获取任务奖励
	public function getred() 
	{
		global $_W;
		global $_GPC;
		$rewardid = intval($_GPC['id']);
		$money = pdo_fetchcolumn('select reward_data from ' . tablename('wx_shop_task_reward') . ' where id = ' . $rewardid . ' and `get` = 1 and sent = 0 and openid = \'' . $_W['openid'] . '\'');
		if (empty($money)) 
		{
			show_json(0, '任务不存在');
		}
		$params = array('openid' => $_W['openid'], 'tid' => time(), 'send_name' => '任务中心', 'money' => floatval($money), 'wishing' => '恭喜您获得了任务奖励', 'act_name' => '任务中心', 'remark' => '任务中心完成奖励');
		$result = m('common')->sendredpack($params);
		if (is_error($result)) 
		{
			show_json(0, $result['message']);
		}
		pdo_update('wx_shop_task_reward', array('sent' => 1, 'senttime' => time()), array('id' => $rewardid));
		show_json(1);
	}
	// 测试
	public function test() 
	{
		global $_W;
		global $_GPC;
		// 核擦任务进度
		p('task')->checkTaskProgress(1, 'pyramid_num');
	}
  


}
?>