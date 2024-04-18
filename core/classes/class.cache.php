<?php
if (!defined("_VALID_PHP")) { die('Direct access to this location is not allowed.'); }

/** =========================================================
 * Class Cache
 * ========================================================== */
class Cache{
	const PATH = BASEPATH."cache/";
	const EXPIRE_HOUR = 1; //in hours
	const ENDPOINTS = ['blogPosts', 'pages', 'settings', 'settings_web', 'languages', 'category'];
	
	public $file;
	public $expire;
	public $result;
	public $api_url;
	public $api_method;
	public $endpoint;

	public function __construct(string $endpoint, string $url, string $method, $id=0) {
			global $lang;
			
			if($id==0){
				$tmp = explode('/', $endpoint);
				if(isset($tmp[1])){
					$endpoint = $tmp[0];
					$id=$tmp[1];
				}
			}
			$this->endpoint = $endpoint;
			
			$this->file = self::PATH."{$this->endpoint}_{$id}";
			
			if(isset($lang)){
				$this->file .= "_{$lang->language}";
			}
			
			$this->file .= ".json";
			
			//$this->file = self::PATH.str_replace( [CORE_URL,'/', '?'], ['', '_', '_'], $url).".json";
			$this->api_url = $url;
			$this->api_method = $method;
	}

	public function save($response):bool{
			global $core;
			
			if($this->api_method !== 'GET') return false;
			if(!in_array($this->endpoint, self::ENDPOINTS)) return false;
			if(!isset($core->devMode) || $core->devMode==1) return false;
			
			$this->expire = time() + self::EXPIRE_HOUR*60*60;

			$data = ['expire' => $this->expire,	'response' => $response, 'url' => $this->api_url];

			if(file_put_contents($this->file, json_encode($data))){
				return true;
			}
			return false;
	}
	
	public function load(){
			global $core;
			
			if($this->api_method !== 'GET') return FALSE;
			if(file_exists($this->file) === false) return FALSE;
			if(isset($core->devMode) && $core->devMode) return false;
			
			$file = file_get_contents($this->file);
			
			//if($file === FALSE) return FALSE;
			
			$response = json_decode($file, true);
			
			if($response['expire'] > time()){
				return $response['response'];
			} else {
				unlink($this->file);
			}
			
			if($this->api_url != $response['url']){
				return false;
			}
			
			return false;
	}
}