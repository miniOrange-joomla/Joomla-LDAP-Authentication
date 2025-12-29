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
class Mo_Ldap_Auth_Response{

	public $status;

	public $statusMessage;

	public $userDn;

	public $attributeList;

	public $profileAttributesList;

	public function __construct(){
		//Empty constructor
	}

}

?>