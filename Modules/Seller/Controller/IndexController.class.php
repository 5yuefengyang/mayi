<?php
namespace Seller\Controller;
use Admin\Model\StatisticsModel;
class IndexController extends CommonController {
   	protected function _initialize(){
   	    parent::_initialize();
   	    
   	}
	
    public function index(){
		
		//cookie('http_refer',$_SERVER['HTTP_REFERER']);
		//cookie('last_login_page');
		
		$is_new = I('get.is_new', 0 );
		
		//获取有多少条的通知
		$day_time = strtotime( date('Y-m-d '.'00:00:00') );
		$day_key = 'new_ordernotice_'.$day_time;
		$day_arr = S( $day_key );

		$order_count = 0;

		if( !empty($day_arr) )
		{
		 $order_count = count($day_arr);
		}

		$this->order_count = $order_count;
		
		if( $is_new == 1 )
		{
			//切换到新后台，
			cookie('is_new_backadmin',1);
		}else if( $is_new == 2 ){
			//切换到旧后台，
			cookie('is_new_backadmin',2);
		}
		
		$is_show_notice = true;
		
		$is_show_notice001 = D('Home/Front')->get_config_by_name('is_show_notice001');
		
		if( !isset($is_show_notice001) )
		{
			$data = array();
			$data['is_show_notice001'] = 1;
			
			D('Seller/Config')->update($data);
		}
		
		$this->is_show_notice001 = $is_show_notice001;
		
		$is_new_backadmin = cookie('is_new_backadmin');
		
		if( empty($is_new_backadmin) || $is_new_backadmin == 2 )
		{
			$this->display();
		}else{
			$this->display('new_index');
		}
		
		//$this->display();
        //$this->display('new_index');
    }
	
	public function analys ()
	{
		$this->display();
	}
	
	public function order_count()
	{
		
		//语音播报
		$voice_notice = D('Home/Front')->get_config_by_name('is_open_order_voice_notice'); 
		
		//获取有多少条的通知
		$day_time = strtotime( date('Y-m-d '.'00:00:00') );
		$day_key = 'new_ordernotice_'.$day_time;
		$day_arr = S( $day_key );

		$order_count = 0;

		if( !empty($day_arr) )
		{
		 $order_count = count($day_arr);
		}
		$info =array();
		if(!empty($order_count)){
		
			$info =array(
					"resultCode"=>200,
					"message"=>"success",
					"data"=>$order_count,
					"voice_notice"=>$voice_notice
			);
			
		}
		echo json_encode($info);
		die();
	}
}