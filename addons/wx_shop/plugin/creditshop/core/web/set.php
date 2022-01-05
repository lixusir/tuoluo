<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
class Set_WxShopPage extends PluginWebPage
{
	public function main() 
	{
		global $_W;
		global $_GPC;
		if ($_W['ispost']) 
		{
			ca('creditshop.set.edit');
			$data = ((is_array($_GPC['data']) ? $_GPC['data'] : array()));
			$data['share_icon'] = save_media($data['share_icon']);
			$exchangekeyword = $data['exchangekeyword'];
			$keyword = m('common')->keyExist($exchangekeyword);
			if (!(empty($keyword))) 
			{
				if ($keyword['name'] != 'wx_shop:creditshop')
				{
					show_json(0, '关键字已存在!');
				}
			}
			$rule = pdo_fetch('select * from ' . tablename('rule') . ' where uniacid=:uniacid and module=:module and name=:name  limit 1', array(':uniacid' => $_W['uniacid'], ':module' => 'wx_shop', ':name' => 'wx_shop:creditshop'));
			if (empty($rule)) 
			{
				$rule_data = array('uniacid' => $_W['uniacid'], 'name' => 'wx_shop:creditshop', 'module' => 'wx_shop', 'displayorder' => 0, 'status' => 1);
				pdo_insert('rule', $rule_data);
				$rid = pdo_insertid();
				$keyword_data = array('uniacid' => $_W['uniacid'], 'rid' => $rid, 'module' => 'wx_shop', 'content' => trim($exchangekeyword), 'type' => 1, 'displayorder' => 0, 'status' => 1);
				pdo_insert('rule_keyword', $keyword_data);
			}
			else 
			{
				pdo_update('rule_keyword', array('content' => trim($exchangekeyword)), array('rid' => $rule['id']));
			}
			$data['set_realname'] = intval($_GPC['data']['set_realname']);
			$data['set_mobile'] = intval($_GPC['data']['set_mobile']);
			$data['style'] = 'default';
			$data['isdetail'] = intval($_GPC['data']['isdetail']);
			$data['isnoticedetail'] = intval($_GPC['data']['isnoticedetail']);
			$data['detail'] = m('common')->html_images($_GPC['data']['detail']);
			$data['noticedetail'] = m('common')->html_images($_GPC['data']['noticedetail']);
			$this->updateSet($data);
			m('cache')->set('template_' . $this->pluginname, $data['style']);
			plog('creditshop.set.edit', '修改积分商城基本设置');
			 // m('common')->updatePluginset(array('creditshop' => array('on_show_wxapp'=>$_GPC['set']['on_show_wxapp'])));
			show_json(1, array('url' => webUrl('creditshop/set', array('tab' => str_replace('#tab_', '', $_GPC['tab'])))));
		}
		$styles = array();
		$dir = IA_ROOT . '/addons/wx_shop/plugin/' . $this->pluginname . '/template/mobile/';
		if ($handle = opendir($dir)) 
		{
			while (($file = readdir($handle)) !== false) 
			{
				if (($file != '..') && ($file != '.')) 
				{
					if (is_dir($dir . '/' . $file)) 
					{
						$styles[] = $file;
					}
				}
			}
			closedir($handle);
		}
		$data = $this->set;
		$pay = m('common')->getSysset('pay');
		include $this->template();
	}
}
?>