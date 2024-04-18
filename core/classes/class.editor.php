<?php
if (!defined("_VALID_PHP")) { die('Direct access to this location is not allowed.'); }

/** =========================================================

 * Class Editor

 * ========================================================== */

class Editor{
	public string $type = '';
	public int $id = 0;
	public string $uri = '';
	public string $slug = '';
	public string $html = '';
	public array $revisions = [] ;
	public string $title = '';
	private const TEMPLATE_TYPES = ['product', 'category', 'blog','header', 'footer' ];

	//public $page;

	/**
	 *
	 * @global \Page $page
	 * @param string $base64
	 */
	public function __construct(string $base64) {
		global $page, $user;



		if(empty($base64)) die("Parameter 'i' is missing !");

		$parameters = json_decode(base64_decode($base64), true);

		if(isset($parameters['token'])){
			
			//d($parameters['token']);die();
				$user->loginByToken($parameters['token']);
		}
		
		$this->type = $parameters['type']??'';
		$this->id = $parameters['id']??0;

		$page->type = $parameters['type']??'';
		$page->id = $parameters['id']??0;

		if($this->type == 'post'){
			$page->target_id = $parameters['id'];
		}
		$this->load_page();
		$this->getRevisions();


	}

	public function getRevisions(){
		global $lang;

		$type = $this->type == 'post' ? 'blog' : 'pages';


		$revisions = Api::cache(false)->admin_api(true)->data(['type' => $type,'object_id' => $this->id, 'lang'=>$lang->language, 'limit'=>20 , 'sort'=>''])->get()->revisions();
		$this->revisions = $revisions['result'];
 
		//d($revisions);die();
		
	}



	public function load_page(){
		global $page;



		$page->load_page(false);


		$this->html = $page->html;
		$this->title = $page->title;
		if($page->type == 'post'){
			$row = Api::cache(false)->id($this->id)->data(['resolution' => '10x10'])->get()->blogPosts();

			$this->html = $row['description'];

		}




	}




	public function save(string $html, string $language, string $css='', string $revision_title){
		global $page, $lang;
		
		
		//remoce <style> from css
		$css = str_replace(['<style>', '</style>'], '', $css);


		if(empty($language)){
			$language = $lang->language;
		}
		
		$lang->set_language($language);



		if($page->type == 'post'){

			$row = Api::data(['lang'=>$language])->redirect(false)->cache(false)->id($this->id)->get()->blogPosts();

			if($row){
				$row['html'] = $html;
				$row['css'] = $css;
				$row['revision_title'] = $revision_title;
				if(empty($row['title'])) $row['title'] = 'Blog-'.$page->id;

				$this->_refresh_cache('blogPosts', $page->id);

				$row['cid'] = $row['category']['id'];
				// $row['combination_id'] = $row['combination']['id'];
				$row['lang']=$language;

				$result = Api::admin_api(true)->redirect(false)->data($row)->id($this->id)->post()->blogPosts();

				return $result;
			}
		} else if($page->type == 'index' || $page->type == 'header' || $page->type == 'footer'){

			if($page->type == 'header'){
				$page->id = Page::ID_HEADER;
			}
			
			if($page->type == 'footer'){
				$page->id = Page::ID_FOOTER;
			}
			
			$row = Api::data(['lang'=>$language])->cache(false)->redirect(false)->id($page->id)->get()->pages();

			if($row){

				$row['description'] = $html;
//				$row['css'] = $css;
				$row['revision_title'] = $revision_title;
				if(empty($row['title'])) $row['title'] = 'Page-'.$page->id;

				$this->_refresh_cache('pages', $page->id);



				$row['lang']=$language;

				$result = Api::admin_api(true)->redirect(false)->data($row)->id($page->id)->post()->pages();
	
				
				$template = new Template($page->type, $page->slug, $page->private);
				$template->save_html($html);
				$template->save_css($css);

				//if edit footer replace header css 
				if($page->id == Page::ID_FOOTER){
					$header_template = new Template('index', 'header');
					$header_template->save_css($css);
				}
				
				
				return $result;
			}
		}

		
		else if (in_array ($page->type, self::TEMPLATE_TYPES)){
			$template = new Template($page->type,$page->css);

			$template->save_html($html);

			return ['status' => 1];
		}


		return ['error'=>'type error: '.$page->type];


	}

	private function _refresh_cache(string $endpoint, int $id){
			$cache = new Cache($endpoint, '', '', $id);
			//d($cache);
			@unlink($cache->file);
	}

}
