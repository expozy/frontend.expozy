<?php

if (!defined("_VALID_PHP")) { die('Direct access to this location is not allowed.'); }
 

/** =========================================================
 * Class Api
 * ========================================================== */

class Api
 
{ 

	public $json;

	protected static $limit=0;
	protected static $page=0;
	public static $id=0;
	protected static $sort=0;
	protected static $data=array();
	protected static $as_array = true;
	protected static $method;
	protected static $admin_api = false;
	protected static $cache = true;
	protected static $redirect = true;

	//protected static $api_key = '';


	//public static function  init(){
	//    if(FrontCore::$LOCAL_CORE){
	///	    require_once(BASEPATH.'../core/api/front/loader.php');
	//    }
	//}

	
	
	public function __construct()
	{
	    
	    //self::_check_local();
	    //require_once(BASEPATH.'core/api/loader.php');
	}

	public static function get_id(){
	    return static::$id;
	}

	private static function _connect_core($method, $endpoint){
		
		
		$id = self::$id;
		$curent_request_cache = self::$cache;
		$result_from_cache = false;
		$redirect = self::$redirect;
		
		$data = self::_generate_data();
		
		$ch = curl_init();
		$headers = ['Content-Type: application/json',
					"authentication: basic ".SAAS_KEY];
		
		
		if(isset($_SESSION['token'])){
		    // For logged users
		    $headers[] = "authorization: bearer ".$_SESSION['token'];
		} else {
		    $headers[] = "authorization: session ". sha1(session_id());
		}
		
		//d($headers);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		
		$url = CORE_URL;
		if(substr($url, -1) !== '/'){
			$url .= '/';
		}
		$url .= "api/";
		if(isset($data['admin_api']) && $data['admin_api']){
			$url .= "admin/";
		}
		$url .= $endpoint;
		
		if($id != 0){
		    $url .= "/".$id;
		}
		
		
		
		if($method == "GET"){
		    
			
			if(isset($_SESSION['LANG_expozy']) && !isset($data['lang'])){
				$data['lang'] = $_SESSION['LANG_expozy'];
			}
			
			ksort($data);
		    $url .= "?".http_build_query($data);
			

		     
		} else{
			
			
			if(isset($data['lang'])){
				$url .= "&lang=".$data['lang'];
			}
			else if(isset($_SESSION['LANG_expozy'])){
				$url .= "&lang=".$_SESSION['LANG_expozy'];
			};
			
		    curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($data));
		}
		
		/***** Cache Module **********/
		//var_dump($url);
		$cache_result = false;
		
		$cache_obj = new Cache($endpoint, $url, $method, $id);
		
		if($curent_request_cache === true && in_array($endpoint, Cache::ENDPOINTS)){
				$cache_result = $cache_obj->load();
		}
		
		
		$http_code = 200;
		if($cache_result !== false){
			 $server_output = $cache_result;
			 $result_from_cache = true;
		} else {
		
			
			curl_setopt($ch, CURLOPT_URL,$url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));

			$server_output = curl_exec($ch);

			curl_close ($ch);
			
			$result_from_cache = false;
			$http_code = curl_getinfo($ch)['http_code'];
			
			
		}
		/***** END Cache Module ******/
		
		
		$return = json_decode($server_output, self::$as_array);
		self::$as_array = true;
		
		if($result_from_cache === false && $method == 'GET' && $http_code == 200 && (is_array($return) || is_object($return) )){
				$cache_obj->save($server_output);
		}
		
		if($return === NULL){
		    return $server_output;
		}
		
		//d($return);die();
		
		if(isset($return['redirect'])){
//		    var_dump($url);
//			var_dump($redirect);die();
			if($redirect){
				header('Location: ' . $return['redirect']);
				die();
			}
		    
			
		    //redirect_to($return['redirect']);
		  
		}
		
		
		
		return $return;  
	}
	
	
	public function output($array){
		//header('Access-Control-Allow-Origin: '.APPURL);
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, HEAD, OPTIONS');
		header('Access-Control-Allow-Headers: Authorization,Origin, X-Requested-With, Content-Type, Accept, Cache-Control, X-CSRF-TOKEN, X-XSRF-TOKEN');
		header('Content-Type: application/json');
		

		die (json_encode($array, JSON_PRETTY_PRINT));
		//();
	}
	
	
	
	public static function limit($limit):object{
	    
		static::$limit = $limit;
		return new static;
	}
	public static function page($page):object{
	    
		static::$page = $page;
		return new static;
	}
	
	public static function id($id):object{
	    
		static::$id = $id;
		
		return new static;
	}
	
	public static function admin_api(bool $admin):object{
	    
		static::$admin_api = $admin;
		
		return new static;
	}
	
	public static function data($data):object{
	    
		static::$data = $data;
		return new static;
	}
	
	public static function sort($order):object{
		static::$sort = $order;
		return new static;
	}	
	
	public static function cache(bool $cache):object{
		static::$cache = $cache;
		return new static;
	}
	
	public static function redirect(bool $redirect):object{
		static::$redirect = $redirect;
		return new static;
	}

	
	public function __call($endpoint, $arguments){
		return self::_connect_core(self::$method, $endpoint);
	}
	
	public static function as_object(){
	    self::$as_array = false;
	    
	    return new static;
	}

	public static function get(){
	    
		self::$method = 'GET';
		
		   return new static;
		
	}
		
	
	
	public static function post(){
	
		self::$method = 'POST';
		
		return new static;
				
	}
	
		
	public static function put(){
		self::$method = 'PUT';

		return new static;
		
	}
	
		
	public static function delete(){
		self::$method = 'DELETE';

		return new static;

	}
	
	private static function _generate_data(){
	
		$data = self::$data;
		$id = self::$id;
		
		if(self::$limit){
			$data['limit'] = self::$limit;
		}
		
		if(self::$page){
			$data['page'] = self::$page;
		}
		
		if(self::$sort){
			$data['sort'] = self::$sort;
		}
		if(self::$admin_api){
			$data['admin_api'] = self::$admin_api;
		}
		
		/** clear data **/
		self::$limit = 0;
		self::$page = 0;
		self::$id = 0;
		self::$sort = 0;
		self::$data = array();
		self::$cache = true;
		self::$redirect = true;
		self::$admin_api = false;
		
		$_GET['query_route'] = '';

		if($id){
			$_GET['query_id'] = $id;
		} else {
			unset($_GET['query_id']);
		}
		
		return $data;
	}
	
	
}