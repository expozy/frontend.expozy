<?php
if (!defined("_VALID_PHP")) { die('Direct access to this location is not allowed.'); }

/** =========================================================
 * Class GitOps
 * ========================================================== */
class GitOps
{
		public static function get_current_repo():string{
				$output = shell_exec("git remote get-url origin");
				$curent_repo = trim(basename($output));
				
				return $curent_repo;
		}
		
		public static function change_saas_key(string $key):void {
				$file = BASEPATH."core/saas_key.php";

				// get file content
				$content = file_get_contents($file);

				// replace key
				$newContent = preg_replace('/define\("SAAS_KEY",".*?"\);/', 'define("SAAS_KEY","' . $key . '");', $content, 1);

				// save new content
				file_put_contents($file, $newContent);
		}
}
?>
