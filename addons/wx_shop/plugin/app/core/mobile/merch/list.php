<?php

class List_WxShopPage extends PluginMobilePage
{
	public function main() 
	{
		global $_W;
		global $_GPC;
		$category = p('merch')->getCategory(array( 'isrecommand' => 1, 'status' => 1, 'orderby' => array('displayorder' => 'desc', 'id' => 'asc') ));
		$merchuser = p('merch')->getMerch(array( 'isrecommand' => 1, 'status' => 1, 'field' => 'id,uniacid,merchname,desc,logo,groupid,cateid', 'orderby' => array('id' => 'asc') ));
		$category_swipe = p('merch')->getCategorySwipe(array( 'status' => 1, 'orderby' => array('displayorder' => 'desc', 'id' => 'asc') ));
		// include $this->template();
		foreach ($category as $k => $v) {
			$category[$k]['thumb'] = tomedia($v['thumb']);
		}
		foreach ($merchuser as $k => $v) {
			$merchuser[$k]['logo'] = tomedia($v['logo']);
		}
		show_json(1, array('category_swipe'=>$category_swipe, 'category'=>$category, 'merchuser'=>$merchuser));
	}
	public function category() 
	{
		global $_W;
		global $_GPC;
		$data = array();
		if (!(empty($_GPC['keyword']))) 
		{
			$data['likecatename'] = $_GPC['keyword'];
		}
		$data = array_merge($data, array( 'status' => 1, 'orderby' => array('displayorder' => 'desc', 'id' => 'asc') ));
		$category = p('merch')->getCategory($data);
		foreach ($category as $k => $v) {
			$category[$k]['thumb'] = tomedia($v['thumb']);
		}
		// include $this->template();
		show_json(1, array(
			// 'data'=>$data, 
			'category'=>$category
		));
	}
	public function merchuser() 
	{
		global $_W;
		global $_GPC;
		$data = array();
		$data = array_merge($data, array( 'status' => 1, 'orderby' => array('displayorder' => 'desc', 'id' => 'asc') ));
		$category = p('merch')->getCategory($data);
		foreach ($category as &$value ) 
		{
			$value['thumb'] = tomedia($value['thumb']);
		}
		unset($value);
		// include $this->template();
		show_json(1, array(
			// 'data'=>$data, 
			'category'=>$category
		));
	}
	public function ajaxmerchuser() 
	{
		global $_W;
		global $_GPC;
		$data = array();
		$pindex = max(1, intval($_GPC['page']));
		$psize = 30;
		$lat = floatval($_GPC['lat']);
		$lng = floatval($_GPC['lng']);
		$sorttype = $_GPC['sorttype'];
		//
		$range = $_GPC['range'];
		if (empty($range)) 
		{
			$range = 10;
		}
		if (!(empty($_GPC['keyword']))) 
		{
			$data['like'] = array('merchname' => $_GPC['keyword']);
		}
		if (!(empty($_GPC['cateid']))) 
		{
			$data['cateid'] = $_GPC['cateid'];
		}
		
		
		$data = array_merge($data, array('status' => 1, 'field' => 'id,uniacid,merchname,desc,logo,groupid,cateid,address,tel,lng,lat'));
		
		if (!(empty($sorttype))) 
		{
			$data['orderby'] = array('id' => 'desc');
		}
		//var_dump($data);die;
		$merchuser = p('merch')->getMerch($data);
		if(empty($merchuser)){
			show_json(0, '找不到符合条件的商户名');
		}

		if (!(empty($merchuser))) 
		{
			$data = array();
			$data = array_merge($data, array( 'status' => 1, 'orderby' => array('displayorder' => 'desc', 'id' => 'asc') ));
			$category = p('merch')->getCategory($data);

			$cate_list = array();
			if (!(empty($category))) 
			{
				
				foreach ($category as $k => $v ) 
				{
					$cate_list[$v['id']] = $v;

				}
				
			}
			foreach ($merchuser as $k => $v ) 
			{
				//var_dump($merchuser);die;
				if (($lat != 0) && ($lng != 0) && !(empty($v['lat'])) && !(empty($v['lng']))) 
				{
					
					$distance = m('util')->GetDistance($lat, $lng, $v['lat'], $v['lng'], 2);
					//var_dump($distance);die;
					if ((0 < $range) && ($range < $distance)) 
					{

						unset($merchuser[$k]);
						continue;
					}
					$merchuser[$k]['distance'] = $distance;
				}
				
				elseif (empty($v['lat']) && empty($v['lng'])) {
					// echo 123;die;
					unset($merchuser[$k]);
					continue;
				}
				else 
				{
					$merchuser[$k]['distance'] = 100000;
				}
				$merchuser[$k]['catename'] = $cate_list[$v['cateid']]['catename'];
				$merchuser[$k]['url'] = mobileUrl('merch/map', array('merchid' => $v['id']));
				$merchuser[$k]['merch_url'] = mobileUrl('merch', array('merchid' => $v['id']));
				$merchuser[$k]['logo'] = tomedia($v['logo']);
			}
		}
		$total = count($merchuser);
		if(empty($total)){
			show_json(0, '找不到符合条件的商户名');
		}
		if ($sorttype == 0) 
		{
			$merchuser = m('util')->multi_array_sort($merchuser, 'distance');
		}
		$start = ($pindex - 1) * $psize;
		if (!(empty($merchuser))) 
		{
			$merchuser = array_slice($merchuser, $start, $psize);
		}
		show_json(1, array('list' => $merchuser, 'total' => $total, 'pagesize' => $psize));
	}
}
?>