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
defined('_JEXEC') or die('Restricted Access');
class Mo_Ldap_Auth_Response
{
	/**
	 * The authentication status.
	 *
	 * @var mixed
	 */
	public $status;

	/**
	 * The status message.
	 *
	 * @var string
	 */
	public $statusMessage;

	/**
	 * The distinguished name of the authenticated user.
	 *
	 * @var string
	 */
	public $userDn;

	/**
	 * The list of user attributes.
	 *
	 * @var array
	 */
	public $attributeList;

	/**
	 * The list of profile attributes.
	 *
	 * @var array
	 */
	public $profileAttributesList;

	public function __construct()
	{
		// Empty constructor
	}

}


