<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
class Level_WxShopPage extends PluginWebPage 
{
	public function main() 
	{
		global $_W;
		global $_GPC;
		$set = $_W['shopset']['abonus'];
        $levelcondition = $set['levelcondition'];

        $default = array('id' => 'default', 'levelname' => empty($set['levelname']) ? '默认等级' : $set['levelname'],'weight'=> empty($set['weight']) ? 1 : $set['weight'], 'bonus1' => $set['bonus1'], 'bonus2' => $set['bonus2'], 'bonus3' => $set['bonus3']);

		$others = pdo_fetchall('SELECT * FROM ' . tablename('wx_shop_abonus_level') . ' WHERE uniacid = \'' . $_W['uniacid'] . '\' ORDER BY id asc');
		$list = array_merge(array($default), $others);
		include $this->template();
	}

	public function add() 
	{
		$this->post();
	}
	public function edit() 
	{
		$this->post();
	}
	protected function post() 
	{
		global $_W;
		global $_GPC;
		$set = $_W['shopset']['abonus'];
		$leveltype = intval($set['leveltype']);
		$id = trim($_GPC['id']);
		if ($id == 'default') {
            $level = array('id' => 'default', 'levelname' => empty($set['levelname']) ? '默认等级' : $set['levelname'], 'weight'=> empty($set['weight']) ? 1 : $set['weight'],  'bonus1' => $set['bonus1'], 'bonus2' => $set['bonus2'], 'bonus3' => $set['bonus3']);
		} else {
			$level = pdo_fetch('SELECT * FROM ' . tablename('wx_shop_abonus_level') . ' WHERE id=:id and uniacid=:uniacid limit 1', array(':id' => intval($id), ':uniacid' => $_W['uniacid']));
		}

		if ($_W['ispost']) 
		{
            //判断权重是否有相同的
            $weight =intval($_GPC['weight']);
            $weight_result = pdo_fetchall('SELECT id,uniacid,weight FROM ' . tablename('wx_shop_abonus_level') . ' WHERE weight in( ' . $weight. ' ) AND uniacid=' . $_W['uniacid']);
            if($weight==1){
                if(empty($id) || $id != 'default'){
                    show_json(0,'该权重已存在，请认真填写!');
                }
            }

            if (empty($id) && $weight_result) {
                show_json(0,'该权重已存在，请认真填写!');
            }else if($id && $weight_result){
                foreach ($weight_result as $w){
                    if($w['id']!=$id){
                        show_json(0,'该权重已存在，请认真填写!');
                    }
                }
            }

			$data = array('weight' => $weight, 'uniacid' => $_W['uniacid'], 'levelname' => trim($_GPC['levelname']), 'bonus1' => trim(trim($_GPC['bonus1']), '%'), 'bonus2' => trim(trim($_GPC['bonus2']), '%'), 'bonus3' => trim(trim($_GPC['bonus3']), '%'), 'commissionmoney' => trim($_GPC['commissionmoney'], '%'), 'ordermoney' => $_GPC['ordermoney'], 'ordercount' => intval($_GPC['ordercount']), 'downcount' => intval($_GPC['downcount']),'bonusmoney' => trim($_GPC['bonusmoney'], '%'),'teamcount'=>$_GPC['teamcount'],'teamcount1'=>$_GPC['teamcount1'],'ordercount1'=>$_GPC['ordercount1'],'ordercount2'=>$_GPC['ordercount2'],'ordermoney1'=>$_GPC['ordermoney1'],'ordermoney2'=>$_GPC['ordermoney2'],'downcount1'=>$_GPC['downcount1']);

            $data['levelcondition'] = intval($_GPC['levelcondition']);
            $data['leveltypes_json'] = iserializer($_GPC['leveltypes']);

			if (!(empty($id))) 
			{
				if ($id == 'default') 
				{
					$updatecontent = '<br/>等级名称: ' . $set['levelname'] . '->' . $data['levelname'] . '<br/>等级权重: ' . $set['weight'] . '->' . $data['weight'] .'<br/>省级分红比例: ' . $level['bonus1'] . '->' . $data['bonus1'] . '<br/>市级红比例: ' . $level['bonus2'] . '->' . $data['bonus2'] . '<br/>区级分红比例: ' . $level['bonus3'] . '->' . $data['bonus3'];
					$set['levelname'] = $data['levelname'];
					$set['bonus1'] = $data['bonus1'];
					$set['bonus2'] = $data['bonus2'];
					$set['bonus3'] = $data['bonus3'];
					$this->updateSet($set);
					plog('abonus.level.edit', '修改代理商默认等级' . $updatecontent);
				}
				else 
				{
					$updatecontent = '<br/>等级名称: ' . $level['levelname'] . '->' . $data['levelname'] . '<br/>等级权重: ' . $level['weight'] . '->' . $data['weight'] . '<br/>省级分红比例: ' . $level['bonus1'] . '->' . $data['bonus1'] . '<br/>市级红比例: ' . $level['bonus2'] . '->' . $data['bonus2'] . '<br/>区级分红比例: ' . $level['bonus3'] . '->' . $data['bonus3'];
					pdo_update('wx_shop_abonus_level', $data, array('id' => $id, 'uniacid' => $_W['uniacid']));
					plog('abonus.level.edit', '修改代理商等级 ID: ' . $id . $updatecontent);
				}
			}
			else 
			{
				pdo_insert('wx_shop_abonus_level', $data);
				$id = pdo_insertid();
				plog('abonus.level.add', '添加代理商等级 ID: ' . $id);
			}
			show_json(1, array('url' => webUrl('abonus/level')));
		}

        $data=[
            'levelcondition' => $level['levelcondition'],
            'leveltypes_json'=>$level['leveltypes_json'],
        ];

		include $this->template();
	}
	public function delete() 
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);
		if (empty($id)) 
		{
			$id = ((is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0));
		}
		$items = pdo_fetchall('SELECT id,levelname FROM ' . tablename('wx_shop_abonus_level') . ' WHERE id in( ' . $id . ' ) AND uniacid=' . $_W['uniacid']);
		foreach ($items as $item ) 
		{
			pdo_delete('wx_shop_abonus_level', array('id' => $item['id']));
			plog('abonus.level.delete', '删除代理商等级 ID: ' . $id . ' 等级名称: ' . $item['levelname']);
		}
		show_json(1);
	}
}
?>