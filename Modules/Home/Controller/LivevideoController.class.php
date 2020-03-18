<?php
/**
 * lionfish 商城系统
 *
 * ==========================================================================
 * @link      http://www.liofis.com/
 * @copyright Copyright (c) 2015 liofis.com. 
 * @license   http://www.liofis.com/license.html License
 * ==========================================================================
 * 小程序直播模块
 * @author J_da
 *
 */
namespace Home\Controller;

class LivevideoController extends CommonController {
	private $re_access_token;
	
	protected function _initialize()
    {
    	parent::_initialize();

    	$weixin_config = array();
		$weixin_config['appid'] = D('Home/Front')->get_config_by_name('wepro_appid');
		$weixin_config['appscert'] = D('Home/Front')->get_config_by_name('wepro_appsecret');
		$jssdk = new \Lib\Weixin\Jssdk( $weixin_config['appid'], $weixin_config['appscert']);
		$this->re_access_token = $jssdk->getweAccessToken();
    }
	
	/**
	 * 获取房间列表
	 * "start" => 0, // 为起始页
	 * "limit" => 10 // 为每页多少个  最大100
	 * 接口500次/天
	 * @return [type] [description]
	 */
	public function get_roominfo()
	{
		$gpc = I('request.');
		$page = intval($gpc['page'], 1);

		$showTabbar = false;
		$tabbar_out_type = D('Home/Front')->get_config_by_name('tabbar_out_type');
		if($tabbar_out_type==7) $showTabbar = true;

		// 是否开启Redis
		$open_redis_server = D('Home/Front')->get_config_by_name('open_redis_server');

		if(!empty($open_redis_server) && $open_redis_server == 1)
		{
			if(!class_exists('Redis')){
				echo json_encode( array('code' => 1, msg=>"Redis未安装", 'showTabbar'=>$showTabbar) );
				die();
			}

			$redis = D('Seller/Redisorder')->get_redis_object_do();

			$day_time = strtotime( date('Y-m-d'.' 00:00:00') );
			$inc_key = "_inc_livevideo_".$day_time;

			$livevideo_server  = $redis->get($inc_key);
			$res = $this->_write_file($livevideo_server, 1);
		}else{
			$day_time = strtotime( date('Y-m-d'.' 00:00:00') );
			$livevideo_server = S('_inc_livevideo_'.$day_time);

			$res = $this->_write_file($livevideo_server, 0);
		}
		
		$list = array();
		if($res) {
			$room_info = $res->room_info;
			if(count($room_info) > $page*10) {
				$list = array_slice($room_info, ($page-1)*10, 10);
			} else {
				$list = array_slice($room_info, ($page-1)*10);
			}
		}
		
		if($list)
		{
			foreach ($list as $key => &$val) {
				$val->start_time = date('Y-m-d H:i', $val->start_time);
				$val->end_time = date('Y-m-d H:i', $val->end_time);
			}
			echo json_encode( array('code' => 0, 'data'=>$list, 'showTabbar'=>$showTabbar) );
			die();
		} else{
			echo json_encode( array('code' => 1, 'showTabbar'=>$showTabbar) );
			die();
		}
		
	}

	private function _write_file($livevideo_server, $is_redis)
	{
		$day_time = strtotime( date('Y-m-d'.' 00:00:00') );
		if($is_redis==1){
			$redis = D('Seller/Redisorder')->get_redis_object_do();
			$inc_key = "_inc_livevideo_".$day_time;
		}

		if( empty($livevideo_server) )
		{
			$res = D('Livevideo')->get_wx_roomInfo($this->re_access_token);
			if($res) {
				$is_redis==1 ? $redis->set($inc_key, $res) : S($inc_key, $res);
			}
			return $res;
		}else{
			$expire_time = $livevideo_server->expire_time ? $livevideo_server->expire_time : 0;
			if( time() - $expire_time > 300 || !$expire_time ) {
				$res = D('Livevideo')->get_wx_roomInfo($this->re_access_token);
				if($res) {
					$is_redis==1 ? $redis->set($inc_key, $res) : S($inc_key, $res);
				}
				return $res;
			} else {
				$total = $livevideo_server->total;
				$page = $livevideo_server->page;
				if($total>$page*50) {
					$page += 1;
					$resPage = D('Livevideo')->get_wx_roomInfo($this->re_access_token, $page);
					$res = (object) array('page' => $page);
					$res->expire_time = time();
					$res->room_info = array_merge($livevideo_server->room_info, $resPage->room_info);

					$is_redis==1 ? $redis->set($inc_key, $res) : S($inc_key, $res);
					return $res;
				}
				return $livevideo_server;
			}
		}
	}
	
}