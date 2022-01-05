<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
class Set_WxShopPage extends WebPage 
{
	public function main() 
	{
		global $_W;
		global $_GPC;

		$item = pdo_fetch('select * from ' . tablename('wx_shop_game_set') . ' where uniacid=:uniacid',array(':uniacid'=>$_W['uniacid']));


		// $ars = array($val['uid'],'jinbi',$val['income'],'5秒收益','5秒收益等级为'.$val['goodslevel']);
		// echo "<pre>";
		// 	print_r(3/1);
		// echo "</pre>";
		// exit;
		// echo "<pre>";
		// 	print_r($ars);
		// echo "</pre>";
		// exit;

		// echo "<pre>";
		// 	print_r();
		// echo "</pre>";
		// exit;

		// for ($i=0; $i < 10; $i++) { 
		// 	pdo_update('wx_shop_game_redlog'.$i,array('status'=>1),array('type'=>1));
		// 	pdo_update('wx_shop_game_redlog'.$i,array('status'=>1),array('type'=>2));
		// }

		$piclist = unserialize($item['gaozi']);

		if ($_W['ispost']) 
		{
			$data = array(
				'gg_money'=>intval($_GPC['gg_money']),
				'kefu'=>$_GPC['kefu'],
				'fx_one'=>$_GPC['fx_one'],
				'fx_two'=>$_GPC['fx_two'],
				'sn_bili'=>$_GPC['sn_bili'],
				'fh_one'=>$_GPC['fh_one'],
				'fh_two'=>$_GPC['fh_two'],
				'tx_sxf'=>$_GPC['tx_sxf'],
				'tx_sm'=>$_GPC['tx_sm'],
				'dt_two'=>$_GPC['dt_two'],
				'dt_one'=>$_GPC['dt_one'],
				'qd_money'=>$_GPC['qd_money'],
				'wxbind'=>$_GPC['wxbind'],
				'rz'=>$_GPC['rz'],
				'rz_1'=>$_GPC['rz_1'],
				'rz_2'=>$_GPC['rz_2'],
				'rz_max'=>$_GPC['rz_max'],
				'rzs_max'=>$_GPC['rzs_max'],
				'rzs_1'=>$_GPC['rzs_1'],
				'tx_moneys'=>$_GPC['tx_moneys'],
				'fx_img'=>$_GPC['fx_img'],
				'rz_level'=>$_GPC['rz_level'],
				'img_sq'=>$_GPC['img_sq'],
				'sn_zong'=>$_GPC['sn_zong'],
				'fx_lj'=>$_GPC['fx_lj'],
				'fx_sm'=>$_GPC['fx_sm'],
				'dzp_bili'=>$_GPC['dzp_bili'],
				'dzp_zong'=>$_GPC['dzp_zong'],
				'yx_bili'=>$_GPC['yx_bili'],
				'yx_zong'=>$_GPC['yx_zong'],
				'ch_img'=>$_GPC['ch_img'],

			);

			//更换下载链接
			if($_GPC['xz_lj'] != $item['xz_lj']) {
				$dir = IA_ROOT.'/addons/sz_yi/data/qrcode/96';
				$wj = opendir($dir);

				while ($file=readdir($wj)) {
				    if($file!="." && $file!="..") {
				        $fullpath=$dir."/".$file;
				        if(!is_dir($fullpath)) {
		                   unlink($fullpath);
		                } else {
		                   deldir($fullpath);
		                }
				    }
				}

				$data['xz_lj'] = $_GPC['xz_lj'];
			}

			if (is_array($_GPC['gaozi'])) 
			{
				$gaozi = $_GPC['gaozi'];
				$gaozi_url = array();
				foreach ($gaozi as $th ) 
				{
					$gaozi_url[] = trim($th);
				}
				// echo '<pre>';
				//     print_r($gaozi_url);
				// echo '</pre>';

				$data['gaozi'] = serialize($gaozi_url);

				// echo '<pre>';
				//     print_r($data);
				// echo '</pre>';
			} else {
				$data['gaozi'] = '';
			}
			// echo '<pre>';
			// 	    print_r($_GPC['gaozi']);
			// 	echo '</pre>';	

			if(empty($item)) {

				$data['uniacid']=$_W['uniacid'];

				pdo_insert('wx_shop_game_set',$data);

			} else {

				pdo_update('wx_shop_game_set',$data,array('uniacid'=>$_W['uniacid']));

			}


			show_json(1);

		}

		include $this->template();
	}
}

?>