<?php
if (!defined("_VALID_PHP")) { die('Direct access to this location is not allowed.'); }

/** =========================================================
 * Class Template
 * ========================================================== */
class Template
{
		public const DIR_PATH = BASEPATH.'static/';
		public const DIR_PATH_CSS = BASEPATH.'static/css/';
		private string|false $file = false;
		private string|false $file_css = false;
		private string $page_type = '';
		private string $page_slug = '';
		private string $dir_file = '';
		private string $dir_css = '';
		
		
		public function __construct(string $type, string $slug='', bool $private = false) {
			global $lang;

			$this->page_type = $type;
			$this->page_slug = $slug;

			if($private && $this->page_type !=='footer' && $this->page_type !== 'header'){
					$this->dir_file = self::DIR_PATH."private/{$lang->language}";
					$this->file = "{$this->dir_file}/{$slug}";
			} else {
					$this->dir_file = self::DIR_PATH."pages/{$lang->language}";
					$this->file = "{$this->dir_file}/{$slug}.html";
			}
 

			$this->dir_css = self::DIR_PATH_CSS."{$lang->language}";
			
			$this->file_css = "{$this->dir_css}/{$slug}.css";
			


		}


		public function get_html(){
			$html = '';

			
			if(file_exists($this->file)){
				$html = file_get_contents($this->file);
			}


			
			return $html;
		}
		
		public function get_css(){
			$css = '';

			if(file_exists($this->file_css)){


				//d(file_get_contents($this->file));
				$css = file_get_contents($this->file_css);
			}

			return $css;
		}

		public function save_html(string $html){

			if($this->page_type != 'index' && $this->page_type != 'header' && $this->page_type != 'footer' && $this->page_type != 'post' && $this->page_type != 'product'){
				return false;
			}
			
			if($this->page_type == 'index'){
				$page = Api::cache(false)->data(['slug'=> $this->page_slug])->get()->pages();

				if(!isset($page['id']) || $page['id'] == 0){
					return false;
				}
			}
			
			if (!is_dir($this->dir_file)) {
				mkdir($this->dir_file, 0777, true);
			}

			$return = file_put_contents($this->file, $html);

			return $return;
		}
		
		public function save_css(string $css){

			if($this->page_type != 'index' && $this->page_type != 'header' && $this->page_type != 'footer' && $this->page_type != 'post' && $this->page_type != 'product'){
				return false;
			}
			
			if($this->page_type == 'index'){
				$page = Api::cache(false)->data(['slug'=> $this->page_slug])->get()->pages();

				if(!isset($page['id']) || $page['id'] == 0){
					return false;
				}
			}
			
			if (!is_dir($this->dir_file)) {
				mkdir($this->dir_file, 0777, true);
			}
			
			$css = str_replace(['<style>', '</style>'], '', $css);

			//change header css
			if($this->page_slug == 'header' || $this->page_slug == 'footer'){
				$css = str_replace(['<style>', '</style>', '{.','}.'], ['', '', '{.headerFooterCss .','}.headerFooterCss .'], $css);
			}
			$return = file_put_contents($this->file_css, $css);
			
			
			return $return;
		}
		
		public function set_pageType($type){
				
				$this->page_type = $type;
		}
		
		public function get_fileName(){
			return $this->file;
		}
		
		



}
