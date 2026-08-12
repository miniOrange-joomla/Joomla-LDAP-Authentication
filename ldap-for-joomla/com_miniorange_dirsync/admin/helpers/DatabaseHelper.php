<?php
/**
 * @package     Joomla.Component
 * @subpackage  com_miniorange_dirsync
 *
 * @author      miniOrange Security Software Pvt. Ltd.
 * @copyright   Copyright (C) 2015 miniOrange (https://www.miniorange.com)
 * @license     GNU General Public License version 3; see LICENSE.txt
 * @contact     info@xecurify.com
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

class MoDatabaseHelper
{
	public static function getDb()
	{
		if (method_exists(Factory::class, 'getContainer'))
		{
			try
			{
				return Factory::getContainer()->get(DatabaseInterface::class);
			}
			catch (\Throwable $e)
			{
				// Container not available on this version; fall back below.
			}
		}

		return Factory::getDbo();
	}
}
