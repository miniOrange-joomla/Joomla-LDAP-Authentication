<?php
defined('_JEXEC') or die;
/**
* @package     Joomla.Component
* @subpackage  com_miniorange_dirsync
*
* @author      miniOrange Security Software Pvt. Ltd.
* @copyright   Copyright (C) 2015 miniOrange (https://www.miniorange.com)
* @license     GNU General Public License version 3; see LICENSE.txt
* @contact     info@xecurify.com
* This class contains all the utility functions
*
* */


// No direct access to this file
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Uri\Uri;
/**
 * AccountSetup Model
 *
 * @since  0.0.1
 */
class miniorangedirsyncModelAccountSetup extends AdminModel
{

	/**
	 * Method to get the record form.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  mixed    A JForm object on success, false on failure
	 *
	 * @since   1.6
	 */


	public function getForm($data=array(), $loadData=true)
	{
		// Get the form.
		$form=$this->loadForm(
			'com_miniorange_dirsync.accountsetup',
			'accountsetup',
			array(
				'control'=> 'jform',
				'load_data'=> $loadData
			)
		);

		if (empty($form))
		{
			return false;
		}

		return $form;
	}
}