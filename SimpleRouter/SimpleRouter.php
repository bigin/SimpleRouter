<?php
declare(strict_types=1);

namespace Scriptor\Modules\SimpleRouter;

use Scriptor\Core\Module;
use Scriptor\Core\Scriptor;

/**
 * The route registrar class
 */
class SimpleRouter extends Module
{
	/**
	 * Module version 
	 * 
	 * Use ~~ echo Quill::VERSION; ~~~
	 * to get the current module version
	 */
	const VERSION = '1.5.0';

	private static $initialized;

	public function init()
	{
		parent::init();
		Scriptor::load(__DIR__.'/Route.php');
		self::$initialized = true;
	}

	public static function moduleInfo() : array
	{
		return [
			'name' => 'SimpleRouter',
            'position' => 1,
            'description' => 'This is a simple URL Router module for Scriptor CMS.<br>Note that during the installation process, the <i>_ext.php</i> '.
				'file is added or supplemented in your current theme directory. <a href="https://scriptor-cms.info/tutorials/module-tutorials/create-module/">More information</a>',
			'version' => self::VERSION,
			'author' => 'Bigin',
			'author_website' => 'https://ehret-studio.com'
		];
	}

	/**
	 * Installation method
	 *
	 * This method performs the installation and updates the code in the relevant files.
	 * It provides detailed notifications about which code sections were modified in which files.
	 *
	 * @return bool Returns true if the installation is successfully completed, otherwise false.
	 */
	public function install() : bool
	{
		if (!self::$initialized) $this->init();

		$filePath = IM_ROOTPATH.'site/themes/'.$this->config['theme_path'].'_ext.php';
		$newCode = "<?php\ndefined('IS_IM') or die('You cannot access this page directly');\n// <SIMPLEROUTER>\n\Scriptor\Core\Scriptor::".
			"load(IM_ROOTPATH.'site/modules/SimpleRouter/SimpleRouter.php');\n(new \Scriptor\Modules\SimpleRouter\SimpleRouter())->init();\n".
			"include '_routes.php';\nexit;\n// </SIMPLEROUTER>\n";

		if (!file_exists($filePath)) {
			file_put_contents($filePath, $newCode);
			$this->msgs[] = [
				'type' => 'success',
				'value' => 'The file <strong>'.$filePath.'</strong> has been created, and the custom code section has been added successfully.'
			];
			return true;
		} else {
			$diePattern = '/defined\s*\(\s*[\'"]IS_IM[\'"]\s*\)\s*OR\s*die\s*\(\s*[\'"]You cannot access this page directly[\'"]\s*\)\s*;/i';
			$existingContent = file_get_contents($filePath);
			$existingContent = preg_replace('/<\?php\s*/', '', $existingContent, 1);
			$existingContent = trim(preg_replace($diePattern, '', $existingContent));

			if (strpos($existingContent, '// <SIMPLEROUTER>') === false) {
				$newContent = $newCode . $existingContent;
				file_put_contents($filePath, $newContent);
				$this->msgs[] = [
					'type' => 'success',
					'value' => 'The custom code section has been successfully added to the file <strong>'.$filePath.'</strong>.'
				];

				return true;
			} else {
				$this->msgs[] = [
					'type' => 'error',
					'value' => 'The custom code section is already present in the <strong>'.$filePath.'</strong> file.'
				];
			}
		}

		return false;
	}

	/**
	 * Uninstallation method
	 *
	 * This method performs the uninstallation and removes the code from the relevant file.
	 * It provides a notification indicating whether the code was successfully removed or if the file does not exist.
	 *
	 * @return bool Returns true if the uninstallation is successfully completed, otherwise false.
	 */
	public function uninstall() : bool
	{
		if (!self::$initialized) $this->init();

		$filePath = IM_ROOTPATH.'site/themes/'.$this->config['theme_path'].'_ext.php';

		if (file_exists($filePath)) {
			$existingContent = file_get_contents($filePath);
			$newContent = preg_replace('/\/\/ <SIMPLEROUTER>.*?\/\/ <\/SIMPLEROUTER>/s', '', $existingContent);
			file_put_contents($filePath, $newContent);

			$this->msgs[] = [
				'type' => 'success',
				'value' => 'The custom code section has been successfully removed from the file <strong>'.$filePath.'</strong>.'
			];

			return true;
		} else {
			$this->msgs[] = [
				'type' => 'error',
				'value' => 'The file <strong>'.$filePath.'</strong> does not exist.'
			];
		}

		return false;
	}
}