<?php
defined('_JEXEC') or die;
/**
 * @package     Joomla.Plugin
 * @subpackage  com_miniorange_dirsync
 *
 * @author      miniOrange Security Software Pvt. Ltd.
 * @copyright   Copyright (C) 2015 miniOrange (https://www.miniorange.com)
 * @license     GNU General Public License version 3; see LICENSE.txt
 * @contact     info@xecurify.com
 */
/**
 * Component install script — uninstall feedback before removal.
 */
class ComMiniorangeDirsyncInstallerScript
{
	public function preflight($type, $parent)
	{
		if ($type === 'uninstall')
		{
			$this->runFeedback($parent);
		}

		return true;
	}

	private function runFeedback($parent)
	{
		$systemPlugin = JPATH_PLUGINS . '/system/miniorangedirsync/miniorangedirsync.php';

		if (!is_file($systemPlugin))
		{
			return;
		}

		require_once $systemPlugin;

		$eid = (is_object($parent) && isset($parent->extension->extension_id))
			? (int) $parent->extension->extension_id : 0;

		PlgSystemMiniorangedirsync::triggerUninstallFeedback($eid);
	}
}
