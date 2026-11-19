<?php
defined('_JEXEC') or die;
/**
 * @package     Joomla.Plugin
 * @subpackage  plg_system_miniorangedirsync
 *
 * @author      miniOrange Security Software Pvt. Ltd.
 * @copyright   Copyright (C) 2015 miniOrange (https://www.miniorange.com)
 * @license     GNU General Public License version 3; see LICENSE.txt
 * @contact     info@xecurify.com
 */

class PlgSystemMiniorangedirsyncInstallerScript
{
	public function preflight($type, $parent)
	{
		if ($type === 'uninstall')
		{
			require_once __DIR__ . '/miniorangedirsync.php';

			$eid = (is_object($parent) && isset($parent->extension->extension_id))
				? (int) $parent->extension->extension_id : 0;

			PlgSystemMiniorangedirsync::triggerUninstallFeedback($eid);
		}

		return true;
	}
}
