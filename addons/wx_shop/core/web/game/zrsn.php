<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
class Zrsn_WxShopPage extends WebPage
{
	public function main() 
	{
		global $_W;
		global $_GPC;
		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;
		$condition = ' and uniacid=:uniacid ';
		
		$params = array(':uniacid' => $_W['uniacid']);

		$sql = 'select * from ' . tablename('wx_shop_game_zrsn') . ' where 1 ' . $condition . ' order by id desc ';
		if (empty($_GPC['export'])) 
		{
			$sql .= 'LIMIT ' . (($pindex - 1) * $psize) . ',' . $psize;
		}

		$money = 0;
		$list = pdo_fetchall($sql, $params);
		if (!(empty($list))) 
		{
			foreach ($list as $key => $value ) 
			{
				$list[$key]['timea'] = date("Y-m-d",$value['times']);

				$ars = unserialize($value['sn_member']);

				$aa = array();
				
				$str = '';
				
				if(!empty($ars)) {
					
					foreach ($ars as $val) {
							
						$str .= $val.',';

						$members=pdo_fetch("select id,avatar,nickname from ".tablename("wx_shop_member")." where id=:id limit 1",array(":id"=>$val));
						// echo "<pre>";
						// 	print_r($members);
						// echo "</pre>";
						if(!empty($members)) {
							$aa[] = $members;

						}

					}

				}

				$list[$key]['members'] = $aa;
				$list[$key]['mema'] = $str;

				// echo "<pre>";
				// 	print_r($ars);
				// echo "</pre>";
				
			}
		}

		// echo '<pre>';
		//     print_r($list);
		// echo '</pre>';
		// exit;
		$total = pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_game_zrsn') . ' where 1 ' . $condition . ' ' . $condition1, $params);
		$pager = pagination2($total, $pindex, $psize);
		include $this->template();
	}



	// public function recharge() 
	// {
	// 	$this->main(0);
	// }
	// public function withdraw() 
	// {
	// 	$this->main(1);
	// }
}
?>