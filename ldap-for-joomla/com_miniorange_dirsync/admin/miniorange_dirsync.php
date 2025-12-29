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
*/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
require_once JPATH_COMPONENT . '/helpers/mo_customer_setup.php';
require_once JPATH_COMPONENT . '/helpers/dirsync_ldap_config.php';
require_once JPATH_COMPONENT . '/helpers/ldap_auth_response.php';
require_once JPATH_COMPONENT . '/helpers/mo_ldap_utility.php';
require_once JPATH_COMPONENT . '/helpers/mo_ldap_constants.php';

// Access check.
$app = Factory::getApplication();
$user = $app->getIdentity();
if (!$user->authorise('core.manage', 'com_miniorange_dirsync'))
{
	throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'));
}

// Include dependancies
jimport('joomla.application.component.controller');

JLoader::registerPrefix('miniorange_dirsync', JPATH_COMPONENT_ADMINISTRATOR);

// Get an instance of the controller prefixed by Joomla
$controller=BaseController::getInstance('MiniorangeDirsync');
 
// Perform the Request task
$app = Factory::getApplication();

// Use getInput() if available (Joomla 4+), otherwise fall back to $app->input (Joomla 3)
$input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;

$controller->execute($input->get('task'));
 
// Redirect if set by the controller
$controller->redirect();