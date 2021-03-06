<?php

/**
 * CodeIgniter
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2014 - 2017, British Columbia Institute of Technology
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package	CodeIgniter
 * @author	EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc. (https://ellislab.com/)
 * @copyright	Copyright (c) 2014 - 2017, British Columbia Institute of Technology (http://bcit.ca/)
 * @license	http://opensource.org/licenses/MIT	MIT License
 * @link	https://codeigniter.com
 * @since	Version 1.0.0
 * @filesource
 */
defined ( 'BASEPATH' ) or exit ( 'No direct script access allowed' );

/**
 * Application Controller Class
 *
 * This class object is the super class that every library in
 * CodeIgniter will be assigned to.
 *
 * @package CodeIgniter
 * @subpackage Libraries
 * @category Libraries
 * @author EllisLab Dev Team
 * @link https://codeigniter.com/user_guide/general/controllers.html
 */
class CI_Controller {
	
	/**
	 * Reference to the CI singleton
	 *
	 * @var object
	 */
	private static $instance;
	var $cache;
	var $currentuid = array ();
	var $setting = array ();
	var $category = array ();
	var $usergroup = array ();
	var $whitelist;
	var $time;
	var $ip;
	/**
	 * Class constructor
	 *
	 * @return void
	 */
	public function __construct() {
		self::$instance = & $this;
		
		// Assign all the class objects that were instantiated by the
		// bootstrap file (CodeIgniter.php) to local class variables
		// so that CI can run as one big super object.
		foreach ( is_loaded () as $var => $class ) {
			$this->$var = & load_class ( $class );
		}
		
		$this->load = & load_class ( 'Loader', 'core' );
		$this->load->initialize ();
		$this->load->library ( 'form_validation' );
		if (PHP_VERSION < 7.2) {
			$this->load->library ( 'encrypt' );
		}
		
		if (strstr ( trim ( config_item ( 'mobile_domain' ) ), $_SERVER ['HTTP_HOST'] )) {
			defined ( 'SITE_URL' ) or define ( 'SITE_URL', config_item ( 'mobile_domain' ) );
		} else {
			defined ( 'SITE_URL' ) or define ( 'SITE_URL', config_item ( 'base_url' ) );
		}
		
		// ???????????????????????????
		$this->isinstall ();
		
		$this->init_cache ();
		// $this->checkurl ();
		$this->init_user ();
		
		$this->banned ();
		
		$this->canviewpage ();
		
		$this->time = time ();
		$this->ip = getip ();
	}
	/**
	 *
	 * ????????????url???????????????????????????????????????????????????
	 *
	 * @date: 2019???11???17??? ??????9:39:07
	 *
	 * @author : 61703
	 *        
	 * @param
	 *        	: variable
	 *        	
	 * @return :
	 *
	 */
	function checkurl() {
		// ??????????????????????????????
		$regular = strtolower ( $this->uri->rsegments [1] ) . '/' . strtolower ( $this->uri->rsegments [2] );
		global $setting;
		$url = $_SERVER ['REQUEST_URI'];
		
		$p = pathinfo ( $url );
		// ???????????????????????????????????????html,php??????????????????
		$_fix = $p ['extension'];
		
		// ????????????????????????????????????????????????
		if (strstr ( $_fix, '?' )) {
			$_fix = substr ( $_fix, 0, strrpos ( $_fix, '?' ) );
		}
		
		if (strstr ( $url, 'index.php?' ) || $this->uri->rsegments [1] == 'rss' || $this->uri->rsegments [1] == 'user' || $this->uri->rsegments [1] == 'appstore' || $this->uri->rsegments [1] == 'custom' || $this->uri->rsegments [1] == 'pay' || $this->uri->rsegments [1] == 'ebank' || $this->uri->rsegments [1] == 'api_user' || strstr ( $this->uri->rsegments [1], 'app_' )) {
		} else {
			// ?????????????????????
			if (strstr ( $regular, 'index/index' )) {
				// ????????????????????????????????? php???????????????????????????
				if ($_fix && $_fix != trim ( $setting ['seo_suffix'], '.' )) {
					// ?????????????????????????????????php
					if ($_fix != 'php') {
						show_404 ();
					}
				}
			} else {
				// ????????????????????????seo????????????????????????????????????????????????????????????404
				if (strstr ( $regular, 'seo/index' ) || strstr ( $regular, 'ask/index' ) || strstr ( $regular, 'category/view' ) || strstr ( $regular, 'topic/catlist' )) { // ????????????????????????????????????url??????
				                                                                                                                                                            // ??????????????? ???????????????????????????404??????????????????????????????
					if ($_fix && $_fix != trim ( $setting ['seo_suffix'], '.' )) {
						show_404 ();
					}
				} else {
					
					if (strstr ( $regular, '/index' )) {
						// ??????????????????
					} else {
						// ???????????????????????????????????????????????????????????????????????????
						if ($_fix != trim ( $setting ['seo_suffix'], '.' )) {
							show_404 ();
						}
					}
				}
			}
		}
	}
	// ????????????????????????
	function isinstall() {
		if (strtolower ( $this->uri->rsegments [1] ) != "install") {
			if (! file_exists ( FCPATH . 'data/install.lock' )) {
				header ( 'location:' . base_url () . 'install/index.php' );
				exit ();
			}
		}
	}
	/* ??????setting?????????????????????????????????????????????cache */
	public function init_cache() {
		global $setting, $category, $badword, $keyword, $usergroup;
		$this->load->database ();
		
		$setting = $this->setting = $this->cache->load ( 'setting' );
		$category = $this->category = $this->cache->load ( 'category', 'id', 'displayorder' );
		$badword = $this->cache->load ( 'badword', 'find' );
		$keyword = $this->cache->load ( 'keywords', 'find' );
		$usergroup = $this->usergroup = $this->cache->load ( 'usergroup', 'groupid' );
	}
	function init_user() {
		
		global $user;
		$user = array ();
		if(!$_SESSION){
			session_start();
		}
		$sid=getip();
		$uid=0;
		$password='';
		if($_SESSION['loginuid']){
			$uid=intval($_SESSION['loginuid']);
		}
		if($_SESSION['loginpassword']){
			$password=$_SESSION['loginpassword'];
		}
	
		$this->load->model ( 'user_model' );
		if ($uid && $password) {
			$user = $this->user_model->get_by_uid ( $uid, 0 );
			($password != $user ['password']) && $user = array ();
		}
		$baseUrl = 'http://' . $_SERVER ['HTTP_HOST'] . '/?' . urlmap ( $_SERVER ['QUERY_STRING'], 1 );
		//$arr = parse_url ( $baseUrl );
		//echo $baseUrl;exit();
		if (! $user) {
			$user ['uid'] = 0;
			$user ['groupid'] = 6;
			if ($this->setting ['needlogin'] == 1) {
				$method = $this->uri->segments [2];
				
				if ($this->uri->segments [1] != 'account' && $this->uri->segments [1] != 'plugin_weixin' && $this->uri->segments [1] != 'pccaiji_question' && $this->uri->segments [1] != 'pccaiji_catgory' && $this->uri->segments [1] != 'api_user' && $method != 'code' && $method != 'login' && $method != 'register'&& $method != 'ajaxsendpwdmail'&& $method != 'getsmscode'&& $method != 'getpwdsmscode'&& $method != 'getphonepass' && $method != 'getpass' && $method != 'resetpass' && $method != 'checkemail' && $method != 'getsmscode') {
					
					$url = url ( 'user/login' );
					header ( "Location:$url" );
					exit ();
				}
			}
		}
		if ($user ['isblack'] == 1) {
			
			exit ( "??????????????????????????????" );
		}
	
		if ($user ['uid'] && $user ['invatecode'] == null) {
			$this->user_model->sendinvatecodetouid ( $user ['uid'] );
		}
		$user ['sid'] = $sid;
		$user ['ip'] =getip();
		$user ['uid'] && $user ['loginuser'] = $user ['username'];
		$user ['avatar'] = get_avatar_dir ( $user ['uid'] );
		
		$user = $this->user = array_merge ( $user, $this->usergroup [$user ['groupid']] );
		if ($user ['uid']) {
			// ?????????????????????????????????????????????????????????????????????????????????????????????
			// frominvatecode
			if (! isset ( $user ['frominvatecode'] )) {
				if (isset ( $_SESSION ['invatecode'] ) && $user ['invatecode'] != $_SESSION ['invatecode']) {
					$this->user_model->updateinvatecode ( $user ['uid'], $_SESSION ['invatecode'] );
					unset ( $_SESSION ['invatecode'] );
				}
			}
		}else{
			//session_destroy();
			unset($_SESSION['loginuid']);
			unset($_SESSION['loginpassword']);
		}
		
	}
	/**
	 *
	 * ?????????sql?????????????????????
	 *
	 * @date: 2018???11???4??? ??????3:05:25
	 *
	 * @author : 61703
	 *        
	 * @param
	 *        	: variable
	 *        	
	 * @return : ??????????????????????????????????????????????????????
	 *        
	 */
	function getlistbysql($sql) {
		$mlist = array ();
		$query = $this->db->query ( $sql );
		foreach ( $query->result_array () as $md ) {
			$mlist [] = $md;
		}
		return $mlist;
	}
	/* ???????????????????????????????????????????????????????????????????????????????????? */
	function fromcache($cachename, $cachetime = 3) {
		$cachetime = ($this->setting ['index_life'] == 0) ? 1 : $this->setting ['index_life'] * 60;
		if ($cachetime == 'static') {
			$cachedata = $this->cache->read ( $cachename, 0 );
		} else {
			$cachedata = $this->cache->read ( $cachename, $cachetime );
		}
		
		if ($cachedata)
			return $cachedata;
		switch ($cachename) {
			// ????????????
			case 'userauthorlist' :
				// ????????????????????????
				$this->load->model ( 'topic_model' );
				
				$cachedata = $this->topic_model->get_user_articles ( 0, 5 );
				break;
			case 'headernavlist' :
				
				$this->load->model ( 'nav_model' );
				
				$cachedata = $this->nav_model->get_format_url ();
				break;
			case 'cweixin' :
				$this->load->model ( 'weixin_setting_model' );
				$cachedata = $this->weixin_setting_model->get ();
				break;
			case 'duizhang' :
				$this->load->model ( 'duizhang_model' );
				$cachedata = $this->duizhang_model->getlastpaylog ( 0, 10 );
				break;
			case 'nosolvelist' : // ?????????????????????????????????
				
				$this->load->model ( 'question_model' );
				$cachedata = $this->question_model->list_by_cfield_cvalue_status ( '', 0, '1', 0, $this->setting ['list_indexnosolve'] );
				break;
			case 'solvelist' : // ???????????????
				$this->load->model ( 'question_model' );
				$cachedata = $this->question_model->list_by_cfield_cvalue_status ( '', 0, 2, 0, $this->setting ['list_indexnosolve'] );
				break;
			case 'rewardlist' : // ???????????????
				$this->load->model ( 'question_model' );
				$cachedata = $this->question_model->list_by_cfield_cvalue_status ( '', 0, 4, 0, $this->setting ['list_indexreward'] );
				break;
			case 'shangjinlist' : // ?????????????????????
				$this->load->model ( 'question_model' );
				$cachedata = $this->question_model->list_by_shangjin ( 0, $this->setting ['list_indexreward'] );
				break;
			case 'yuyinlist' : // ???????????????
				$this->load->model ( 'question_model' );
				$cachedata = $this->question_model->list_by_yuyin ( 0, $this->setting ['list_indexreward'] );
				break;
			case 'attentionlist' : // ?????????????????????
				$this->load->model ( 'question_model' );
				$cachedata = $this->question_model->get_hots ( 0, $this->setting ['list_indexnosolve'] );
				break;
			case 'weekuserlist' : // ???????????????
				$this->load->model ( 'user_model' );
				$cachedata = $this->user_model->list_by_credit ( 1, $this->setting ['list_indexweekscore'] );
				break;
			case 'alluserlist' : // ????????????
				$this->load->model ( 'user_model' );
				$cachedata = $this->user_model->list_by_credit ( 0, $this->setting ['list_indexallscore'] );
				break;
			case 'newtaglist' : // ????????????
				$this->load->model ( "tag_model" );
				$cachedata = $this->tag_model->getalltaglist ( 0, $this->setting ['list_indexhottag'] );
				break;
			case 'hosttaglist' : // ????????????
				$this->load->model ( "tag_model" );
				$cachedata = $this->tag_model->gethotalltaglist ( 0, $this->setting ['list_indexhottag'] );
				break;
			case 'categorylist' : // ????????????????????????
				$this->load->model ( 'category_model' );
				$cachedata = $this->category_model->list_by_grade ();
				break;
			case 'topdata' : // ??????????????????????????????
				$this->load->model ( 'topdata_model' );
				if (! isset ( $this->setting ['list_topdatanum'] )) {
					$cachedata = $this->topdata_model->get_list ( 0, 3 );
				} else {
					$cachedata = $this->topdata_model->get_list ( 0, $this->setting ['list_topdatanum'] );
				}
				
				break;
			
			case 'notelist' : // ????????????????????????
				$this->load->model ( 'note_model' );
				$cachedata = $this->note_model->get_list ( 0, 10 );
				break;
			case 'statistics' : // ??????????????????????????????????????????
				$this->load->model ( 'question_model' );
				$cachedata = array ();
				$cachedata ['solves'] = $this->question_model->getallsolvequestion (); // ??????????????????
				$cachedata ['nosolves'] = $this->question_model->getallnosolvequestion (); // ??????????????????
				break;
			
			case 'doinglist' : // ????????????
				$this->load->model ( 'doing_model' );
				
				$cachedata = $this->doing_model->list_by_type_andquestionorartilce_cache ( 0, $this->setting ['list_default'] );
				
				break;
			case 'topiclist' :
				$this->load->model ( 'topic_model' );
				$cachedata = $this->topic_model->get_list ( 1, 0, $this->setting ['list_default'], 10 );
				break;
			case 'weektopiclist' : // ?????????????????? ?????????????????????????????????
				$this->load->model ( 'topic_model' );
				$cachedata = $this->topic_model->get_weeklist ( 0, 10 );
				break;
			case 'hottopiclist' :
				$list_indextopiccat = isset ( $this->setting ['list_indexcommend'] ) && $this->setting ['list_indexcommend'] > 0 ? intval ( $this->setting ['list_indexcommend'] ) : 6;
				$this->load->model ( 'topic_model' );
				$cachedata = $this->topic_model->get_hotlist ( 1, 0, $list_indextopiccat, 12 );
				break;
			case 'topiclistinphone' :
				$this->load->model ( 'topic_model' );
				$cachedata = $this->topic_model->get_list_bywhere ( 2, 5 );
				break;
			case 'waptopiclist' :
				$this->load->model ( 'topic_model' );
				$cachedata = $this->topic_model->get_list ( 1, 0, 8, 8 );
				break;
			case 'expertlist' :
				$this->load->model ( 'expert_model' );
				$cachedata = $this->expert_model->get_list ( 0, 0, $this->setting ['list_indexexpert'] );
				break;
			case 'link' : // ????????????
				$this->load->model ( 'link_model' );
				$cachedata = $this->link_model->get_list ();
				break;
			
			case 'newuser' :
				
				$pagesize = $this->setting ['list_default'];
				$this->load->model ( 'user_model' );
				$cachedata = $this->user_model->get_active_list ( 1, $pagesize );
				
				break;
			case 'onlineusernum' :
				$this->load->model ( 'user_model' );
				$cachedata = $this->user_model->rownum_onlineuser ();
				break;
			case 'allusernum' :
				$this->load->model ( 'user_model' );
				$cachedata = $this->user_model->rownum_alluser ();
				break;
			case 'adlist' :
				$this->load->model ( "ad_model" );
				$cachedata = $this->ad_model->get_list ();
				break;
			case 'activeuser' :
				$this->load->model ( 'user_model' );
				$cachedata = $this->user_model->get_active_list ( 0, 6 );
				break;
			case 'hotwords' :
				$this->load->model ( 'setting_model' );
				$cachedata = unserialize ( $this->setting_model->get_hot_words ( $this->setting ['list_hot_words'] ) );
				break;
			case 'articlelist' :
				if (isset ( $this->setting ['cms_open'] ) && $this->setting ['cms_open'] == 1) {
					$this->load->model ( "cms_model" );
					$cachedata = $this->cms_model->get_list ();
				} else {
					$cachedata = array ();
				}
				
				break;
		}
		$this->cache->write ( $cachename, $cachedata );
		return $cachedata;
	}
	/* IP?????? */
	function banned() {
		global $setting;
		$ips = $this->cache->load ( 'banned' );
		$ips = ( bool ) $ips ? $ips : array ();
		$userip = explode ( ".", getip () );
		foreach ( $ips as $ip ) {
			$bannedtime = $ip ['expiration'] + $ip ['time'] - $this->time;
			if ($bannedtime > 0 && ($ip ['ip1'] == '*' || $ip ['ip1'] == $userip [0]) && ($ip ['ip2'] == '*' || $ip ['ip2'] == $userip [1]) && ($ip ['ip3'] == '*' || $ip ['ip3'] == $userip [2]) && ($ip ['ip4'] == '*' || $ip ['ip4'] == $userip [3])) {
				exit ( 'IP????????????????????????' );
			}
		}
	}
	/* ?????????????????? */
	function credit($uid, $credit1, $credit2 = 0, $credit3 = 0, $operation = '') {
		if (! $operation) {
			$operation = strtolower ( $this->uri->segment ( 1 ) . '/' . $this->uri->segment ( 2 ) );
		}
		if ($credit1 == '' || $credit1 == null) {
			$credit1 = 0;
		}
		if ($credit2 == '' || $credit2 == null) {
			$credit2 = 0;
		}
		// ???????????????????????????
		if ($operation == 'api_user/loginapi') {
			$query = $this->db->get_where ( 'credit', array (
					'uid' => $uid,
					'operation' => 'api_user/loginapi',
					'time>=' => strtotime ( date ( "Y-m-d" ) ) 
			) );
			$m = $query->row_array ();
			if ($m) {
				return false;
			}
		}
		// ???????????????
		$data = array (
				'uid' => $uid,
				'time' => time (),
				'operation' => $operation,
				'credit1' => $credit1,
				'credit2' => $credit2 
		);
		$this->db->insert ( 'credit', $data );
		
		// ????????????????????????
		$this->db->set ( 'credit2', "credit2+$credit2", FALSE )->set ( 'credit1', "credit1+$credit1", FALSE )->set ( 'credit3', "credit2+$credit3", FALSE )->where ( array (
				'uid' => $uid 
		) )->update ( 'user' );
		
		if (2 == $this->user ['grouptype']) {
			$currentcredit1 = $this->user ['credit1'] + $credit1;
			$query = $this->db->query ( "SELECT g.groupid FROM " . $this->db->dbprefix . "usergroup g WHERE  g.`grouptype`=2  AND $currentcredit1 >= g.creditslower ORDER BY g.creditslower DESC LIMIT 0,1" );
			$usergroup = $query->row_array ();
			// ????????????????????????
			if (is_array ( $usergroup ) && ($this->user ['groupid'] != $usergroup ['groupid'])) {
				$groupid = $usergroup ['groupid'];
				$this->db->set ( 'groupid', $groupid )->where ( array (
						'uid' => $uid 
				) )->update ( 'user' );
			}
		}
	}
	function send($uid, $qid, $type, $aid = 0) {
		$query = $this->db->get_where ( 'question', array (
				"id" => $qid 
		) );
		$question = $query->row_array ();
		$msgtpl = unserialize ( $this->setting ['msgtpl'] );
		// ????????????
		$message = array ();
		foreach ( $msgtpl [$type] as $msg => $val ) {
			$message [$msg] = str_replace ( '{wtbt}', $question ['title'], $val );
			$message [$msg] = str_replace ( '{wtms}', $question ['description'], $message [$msg] );
			$message [$msg] = str_replace ( '{wzmc}', $this->setting ['site_name'], $message [$msg] );
			if ($aid) {
				$query = $this->db->get_where ( 'answer', array (
						"id" => $aid 
				) );
				$answer = $query->row_array ();
				
				$message [$msg] = str_replace ( '{hdnr}', $answer ['content'], $message [$msg] );
			}
		}
		
		$message ['content'] .= '<br /> <a href="' . url ( 'question/view/' . $qid, 1 ) . '">??????????????????</a>';
		$time = time ();
		$msgfrom = $this->setting ['site_name'] . '?????????';
		$query = $this->db->get_where ( 'user', array (
				"uid" => $uid 
		) );
		$touser = $query->row_array ();
		
		// 1,3,5,7 ??????????????????
		if ((1 & $touser ['isnotify']) && $this->setting ['notify_message']) {
			$data = array (
					'from' => $msgfrom,
					'fromuid' => 0,
					'touid' => $uid,
					'subject' => $message ['title'],
					'time' => $time,
					'content' => $message ['content'] 
			);
			$this->db->insert ( 'message', $data );
		}
		// 2,3,6,7 ???????????????
		if ((2 & $touser ['isnotify']) && $this->setting ['notify_mail']) {
			sendmail ( $touser, $message ['title'], $message ['content'] );
		}
		
		// 4,5,6,7 ?????????????????????
	}
	// ????????????
	function canviewpage() {
		$controlname = isset ( $this->router->routes [$this->uri->rsegments [1]] ) ? $this->router->routes [$this->uri->rsegments [1]] : $this->uri->rsegments [1];
		defined ( 'ROUTE_A' ) or define ( 'ROUTE_A', $controlname );
		$regular = strtolower ( $this->uri->rsegments [1] ) . '/' . strtolower ( $this->uri->rsegments [2] );
		
		$flag = false;
	
		$querystring = $regular; // isset ( $_SERVER ['REQUEST_URI'] ) ? $_SERVER ['REQUEST_URI'] : '';
		$querystring = str_replace ( '.html', '', $querystring );
		$querystring = str_replace ( '/?', '', $querystring );
		$pos = strrpos ( $querystring, '.' );
		if ($pos !== false) {
			$querystring = substr ( $querystring, 0, $pos );
		}
		/* ????????????url */
		$pos = strpos ( $querystring, '-' );
		$pos2 = strpos ( $querystring, '=' );
		$pos3 = strpos ( $querystring, '/' );
		if ($pos !== false) {
			$tempmaparr = explode ( '-', $querystring ); // ????????????
			                                             // ???????????????????????????????????????
			if ($tempmaparr [0] !== 'tag' && $tempmaparr [0] !== 'topictag') {
				// ????????????????????????????????????
				if (is_numeric ( $tempmaparr [1] )) {
					$querystring = urlmap ( $querystring );
				} else {
					// ??????????????????????????????
					$querystring = urlmap ( $querystring, 2 );
				}
			} else {
				// ????????????????????????????????????
				$querystring = urlmap ( $querystring );
			}
		}
		($pos2 !== false) && $querystring = urlmap ( $querystring );
		($pos3 !== false) && $querystring = urlmap ( $querystring );
		$andpos = strpos ( $querystring, "&" );
		$andpos && $querystring = substr ( $querystring, 0, $andpos );
		if (strpos ( $querystring, '/' ) !== FALSE) {
			
			$querystring_arr = explode ( '/', $querystring );
			if (is_array ( $querystring_arr )) {
				
				$querystring = $querystring_arr [0] . '/' . $querystring_arr [1];
				$regular = $querystring;
			}
		}
		$isajax = (0 === strpos ( isset ( $querystring_arr [1] ) ? $querystring_arr [1] : $this->uri->rsegments [2], 'ajax' ));
		$isapi = ('api' == substr ( strtolower ( isset ( $querystring_arr [0] ) ? $querystring_arr [0] : $this->uri->rsegments [1] ), 0, 3 ));
		$isapp = ('app' == substr ( strtolower ( isset ( $querystring_arr [0] ) ? $querystring_arr [0] : $this->uri->rsegments [1] ), 0, 3 ));
		if ($this->whitelist) {
			
			$whitelist = explode ( ',', strtolower ( $this->whitelist ) );
			$flag = in_array ( isset ( $querystring_arr [1] ) ? $querystring_arr [1] : $this->uri->rsegments [2], $whitelist );
		}
		
		if (config_item ( 'dir_name' ) . "/index" == $regular) {
			$regular = "index/index";
		}
		// $regular
		if (strstr ( $regular, 'from=singlemessage' )) {
			$regular = str_replace ( 'from=singlemessage', 'index', $regular );
		}
		if (strstr ( $regular, 'from=timeline' )) {
			$regular = str_replace ( 'from=timeline', 'index', $regular );
		}
		if (strstr ( $regular, 'from=groupmessage' )) {
			$regular = str_replace ( 'from=groupmessage', 'index', $regular );
		}
	
		if ($this->checkable ( $regular, $querystring ) || $isapp || $isajax || ! empty ( $flag )) {
			// ???????????????????????????????????????????????????
		} else {
			
			if ($this->user ['uid'] > 0) {
				$this->message ( '?????????????????????????????????????????????<br/> ?????????????????????(' . $this->user ['grouptitle'] . ')????????????????????????', 'index' );
				exit ();
			} else {
				if(substr($regular, strlen($regular)-6)=='/index'){
					show_404();
					exit();
				}
				header ( "Location:" . url ( 'user/login' ) );
				exit ();
			}
		}
	}
	/*
	 * ??????????????????
	 * $ishtml=1 ??????????????????????????????
	 */
	function message($message, $url = '') {
		if(trim($url)=='index'||trim($url)=='index/index'){
			$url='';
		}
		$seotitle = '????????????';
		if ('' == $url) {
			$redirect = isset ( $_SERVER ['HTTP_REFERER'] ) ? $_SERVER ['HTTP_REFERER'] : base_url ();
		} else if ('BACK' == $url || 'STOP' == $url || strstr ( $url, 'http:' )) {
			$redirect = $url;
		} else {
			
			$redirect = url ( $url );
		}
		$tpldir = (0 === strpos ( $this->uri->segment ( 1 ), 'admin' )) ? 'admin' : $this->setting ['tpl_dir'];
		$panneltype = 'hidefixed';
		$hidefooter = 'hidefooter';
		$seo_title = $seo_keywords = $this->setting ['site_name'] . '??????';
		include template ( 'tip' );
		exit ();
	}
	/* ???????????? */
	function checkable($url, $querystring = '') {
		try {
			$this->addsitelog ( $querystring );
		} catch ( Exception $e ) {
		}
		if (strpos ( $url, '?' ) !== FALSE) {
			
			$url = explode ( '?', $url ) [0];
		}
		$this->regular = $url;
		if (1 == $this->user ['groupid'])
			return true;
		
		$regulars = explode ( ',', 'api_user/registerapi,user/checkemail,api_article/newqlist,api_article/list,api_user/editpwdapi,api_user/loginoutapi,api_user/bindloginapi,api_user/loginapi,index/taobao,question/searchkey,pccaiji_catgory/addtopic,pccaiji_catgory/selectlist,pccaiji_catgory/list,topic/search,user/search,category/search,buy/buydetail,buy/default,download/default,user/regtip,rule/index,user/login,user/logout,user/code,index/help,js/view,' . $this->user ['regulars'] );
		return in_array ( $url, $regulars );
	}
	/* ?????????????????? */
	function addsitelog($guize, $miaoshu = '') {
		global $user;
		$uid = $user ['uid'];
		if ($uid > 0) {
			
			$username = $user ['username'];
			$miaoshu = '';
			$guizearray = explode ( ',', 'index/notfound,user/ajaxloadmessage,user/code,admin_setting/ajaxcaiji' );
			
			if ($uid > 0) {
				if (! in_array ( $guize, $guizearray )) {
					$data = array (
							'uid' => $uid,
							'username' => $username,
							'guize' => $guize,
							'miaoshu' => $miaoshu,
							'time' => time () 
					);
					$this->db->insert ( 'site_log', $data );
				}
			}
		}
	}
	/**
	 * ????????????
	 */
	const FORM_TOKEN_KEY = 'form_token_key';
	const INPUT_TOKEN_NAME = 'input_token_name';
	
	/**
	 * ????????????
	 *
	 * @return string
	 */
	public function gen_token() {
		$hash = md5 ( uniqid ( rand (), true ) );
		$token = sha1 ( $hash );
		return $token;
	}
	
	/**
	 * ??????session??????
	 */
	public function gen_session_token() {
		// ??????token
		$token = $this->gen_token ();
		// ??????session????????????token
		$this->destroy_stoken ();
		// ?????????token?????????session
		$this->session->set_userdata ( self::FORM_TOKEN_KEY, $token );
	}
	
	/**
	 * ???????????????????????????
	 *
	 * @return ??????
	 */
	public function gen_input() {
		$this->gen_session_token ();
		$token_input = "<input type=\"hidden\" name=\"" . self::INPUT_TOKEN_NAME . "\" value=\"" . $this->session->userdata ( self::FORM_TOKEN_KEY ) . "\" readonly=\"true\" /> ";
		return $token_input;
	}
	/**
	 * ??????????????????????????????????????????????????????????????????
	 * ???????????????helper??????
	 *
	 * @param $error_code ???????????????????????????????????????        	
	 * @param $error_level ????????????        	
	 * @param $error_message ??????????????????        	
	 */
	public function stop_doing($error_code = '', $error_level = '', $error_message = '') {
		$this->load->library ( 'slog' );
		// ????????????
		$error_url = $_SERVER ['HTTP_HOST'] . $_SERVER ['REQUEST_URI'];
		$this->slog->process_logs ( $error_url, $error_code, $error_level, $error_message );
		$rediret_url = 'http://' . $error_url;
		header ( "Content-type:text/html; charset=utf-8" );
		die ( "<script type=\"text/javascript\">alert(\"???????????????\\n \\n???????????????" . $error_code . "\\n???????????????" . $error_level . "\\n???????????????" . $error_message . "\"); window.navigate(\"$rediret_url\");</script>" );
	}
	/**
	 * ??????token?????????????????????????????????????????????????????????
	 *
	 * @param string $token_input
	 *        	???????????????token
	 */
	public function token_check($token_input) {
		// ??????session??????????????????token
		if ($this->is_stoken ()) {
			if ($token_input) {
				if ($token_input == $this->session->userdata ( self::FORM_TOKEN_KEY )) {
					$this->destroy_stoken ();
				} else {
					$this->destroy_stoken ();
					$this->stop_doing ( error_code ( 'd' ), error_level ( 'ce' ), error_message ( 'd_add' ) );
				}
			} else {
				$this->destroy_stoken ();
				$this->stop_doing ( error_code ( 'v' ), error_level ( 'ce' ), error_message ( 'v_null' ) );
			}
		} else {
			$this->destroy_stoken ();
			$this->stop_doing ( error_code ( 's' ), error_level ( 'e' ), error_message ( 's_check' ) );
		}
	}
	
	/**
	 * ??????token
	 *
	 * @return bool
	 */
	public function destroy_stoken() {
		$this->session->unset_userdata ( self::FORM_TOKEN_KEY );
		return true;
	}
	
	/**
	 * ??????token????????????
	 *
	 * @return bool
	 */
	public function is_stoken() {
		if ($this->session->userdata ( self::FORM_TOKEN_KEY ))
			return true;
		else
			return false;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Get the CI singleton
	 *
	 * @static
	 *
	 * @return object
	 */
	public static function &get_instance() {
		return self::$instance;
	}
}
