<?php
if (!defined("_VALID_PHP")) { die('Direct access to this location is not allowed.'); }

/** =========================================================
 * Class Page
 * ========================================================== */
class Page
{

	//public $lang = false;
	public string $type = 'index';
	public int $id = 0;
	public int $target_id = 0;
	public string $slug = '';
	public string $title = '';
	
	public string $url = '';
	public string $seo_title = '';
	public string $seo_description = '';
	public string|array $seo_tags = '';
	public string $seo_image = '';
		
	public bool $error404 = false;
	public bool $private = false;
	
	public string $html = '';
	public string $header = '';
	public string $footer = '';
	public string $css = '';
	public string $headCss = '';




	const ID_HEADER = 100;
	const ID_FOOTER = 101;

	function __construct(){
			global $core;

			$pathinfo = pathinfo($_SERVER['REQUEST_URI']);

			$tmp = explode('/', $pathinfo['dirname']);


			if(!isset($tmp[2])){
				$this->slug = $pathinfo['basename'];
			} else {
				$this->slug = $tmp[2];
			}

			if(str_starts_with($this->slug, "?")){
				$this->slug = '';
			}
			
			$this->slug = parse_url($this->slug, PHP_URL_PATH);

			if(empty($this->slug)){
				$this->id = 1;
			}

			$tmp2 = explode('-', $pathinfo['filename']);

			if(isset($tmp2[1])){
				$this->target_id = (int)$tmp2[0];
			}


			if($this->slug == 'post'){
				$this->type = 'post';
			} else if ($this->slug == 'product'){
				$this->type = 'product';
			}


		$header = new Template('header', 'header');
		$this->header = $header->get_html();
		$this->headCss = $header->get_css();

		$footer = new Template('footer', 'footer');
		$this->footer = $footer->get_html();

			
			

	}

	function load_page(bool $cache = true){
		global $lang, $core, $user;


			//blog
		if($this->type == 'post' || $this->type == 'index'	|| $this->type == 'product' || $this->type == 'category' || $this->type == 'blog'	){
			$cache = false;


			if($this->slug === $lang->language){
				$this->id = 1;
				$this->slug= 'homepage';
			}



			if($this->id){
				$core->id = $this->id;
				$row = Api::cache($cache)->id($this->id)->data(['resolution' => '10x10'])->get()->pages();
			} else {
				$row = Api::cache($cache)->data(['slug' => $this->slug, 'resolution' => '10x10'])->get()->pages();
			}

	
			if(!$row ){
				$this->error404 = true;
			}

			$this->tempCategory = $row['tempCategory'] ??'';
			$this->combination = $row['combination'] ??'';
			$this->url = $row['url']??'';
			$this->id = $row['id']??0;
			
			$this->title = $row['title']??'';
			
			$this->seo_title = $row['seo_title'] ?? '';
			$this->seo_description =  $row['seo_description'] ?? '';
			$this->seo_tags = $row['seo_tags'] ?? '';
			$this->seo_image =  $core->web['logo'];
			$this->private = $row['private'] ?? false;
			//for editor
			if(isset($row['slug']))	$this->slug = $row['slug'];
			
			$template = new Template($this->type, $this->slug, $this->private);
				
			

			if($this->private){
			
					if($user->uid){
						$user_info = Api::get()->membership_check();
						
						
						if(isset($user_info['status']) && $user_info['status'] ==1 ){
							
							$this->html = $template->get_html();
						
						}
					} else {
						redirect_to("/{$lang->language}/login");
					}
					
			} else {
					$this->html = $template->get_html();
			}
			
			$this->css = $template->get_css();
			
			
			
			if($this->type == 'post'){
					$this->id = 16;
					$target = Api::cache($cache)->id($this->target_id)->data(['resolution' => '10x10'])->get()->blogPosts();
					
					if($this->target_id > 0){
						$this->seo_title = !empty(trim($target['seo_title'])) ? $target['seo_title'] : $target['title'];
						$this->seo_description = !empty(trim($target['seo_description'])) ? $target['seo_description'] : $target['title']." ".$core->site_name;
						$this->seo_image = $target['images'][0]['url'] ?? $core->web['logo'];
						$this->seo_tags = $target['tags'] ?? $this->seo_title;
						$this->error404 = $target ? false : true;
					} else {
						$this->error404 = true;
					}
					
					
					//var_dump( $target['seo_title']);die();

			} else if($this->type == 'product'){
					$this->id = 13;
					$target = Api::cache($cache)->id($this->target_id)->data(['resolution' => '10x10'])->get()->products();
					if(!empty($target) && $this->target_id > 0){
						$this->seo_title = !empty(trim($target['seo_title'])) ? $target['seo_title'] : $target['title'];
						$this->seo_description = !empty(trim($target['seo_description'])) ? $target['seo_description'] : $target['title']." ".$core->site_name;
						$this->seo_image = $target['images'][0]['image'] ?? $core->web['logo'];
						$this->seo_tags = $target['seo_tags'] ?? $this->seo_title;
					}
					
					$this->error404 = $target ? false : true;
					if($this->target_id <= 0){
						$this->error404 = true;
					}
				
					
			}  else if($this->type == 'index' ){
				
				if($this->slug=='products'){
						$target = Api::cache($cache)->id($this->target_id)->data(['resolution' => '10x10'])->get()->categories();
						
						$this->seo_title = $target['title']??'';
						$this->seo_description = !empty(trim($target['description'])) ? $target['description'] : $this->seo_description;
						$this->seo_image = !empty($target['image_url']) ? $target['image_url'] : $core->web['logo'];
						$this->seo_tags = $this->seo_title;
						$this->error404 = $target ? false : true;
				}
				if($this->slug=='blog' ){
						$target = Api::cache($cache)->id($this->target_id)->data(['resolution' => '10x10'])->get()->blogCategories();
												
						$this->seo_title = $target['title']??'';
						$this->seo_tags = $this->seo_title;
						$this->error404 = $target ? false : true;
						
				}
			}

		} else  if($this->type == 'header'){
			$this->html = $this->header;
			
		} else if($this->type == 'footer'){
			$this->html = $this->footer;
		}

		if(empty($this->seo_title)){
			$this->seo_title = $this->title;
		}
		if(empty($this->seo_description)){
			$this->seo_description = $this->seo_title;
		}
		if(empty($this->seo_tags)){
			$this->seo_tags = $this->seo_title;
		}
		
	
		$this->title = str_replace('"', "'", $this->title);
		$this->seo_title = str_replace('"', "'", $this->seo_title);
		$this->seo_description = str_replace('"', "'", $this->seo_description);
		$this->seo_tags = $this->prepareTags($this->seo_tags);
		
		
		if($this->error404) redirect_to('/404');

		

	}






	public static function html_res_change(string $html, string $res):string{
			global $core;
	
		    $pattern = '/https:\/\/r2\.expozy\.com\/'.$core->site_name.'\/contbuilder\/(.*?)\/(.*?)\.webp/';
			$replacement = 'https://r2.expozy.com/'.$core->site_name.'/contbuilder/$1/'.$res.'/$2.webp';


			return preg_replace($pattern, $replacement, $html);
	}
	
	public static function downloadPages(){
			global $lang;
			
			
			$language = Api::cache(false)->get()->languages();
			$orig_i = get('i');
			$parameters = json_decode(base64_decode($orig_i), true);
			$parameters['type'] = 'index';
			
			
			foreach($language as $lng){
					
					$lang->language = $lng['lang'];
					
					$pages = Api::cache(false)->data(['lang'=>$lng['lang']])->get()->pages();
			
					foreach($pages['result'] as $page){
						
						
						$parameters['id'] = $page['id'];
						
						
						$new_parameters = base64_encode(json_encode($parameters));
						$editor = new Editor($new_parameters);
						$rev = $editor->revisions[0]['object_desc'] ?? '';
						
						
						
						$template = new Template('index', $page['slug']);
						
						if(file_exists($template->get_fileName()) === false){
							$template->save_html($rev);
						}

					}
			}
			
		
			
	}
	
	private function prepareTags(string|array $string):string{
		
		if(is_array($string)){
			return implode(", ", $string);
		}
		return str_replace(" ", ", ",  $string);
	}


}
