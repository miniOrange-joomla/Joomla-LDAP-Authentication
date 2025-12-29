<?php
/**
* Script file of miniorange_ldap_authentication_plugin.
*
* The name of this class is dependent on the component being installed.
* The class name should have the component's name, directly followed by
* the text InstallerScript (ex:. com_helloWorldInstallerScript).
*
* This class will be called by Joomla!'s installer, if specified in your component's
* manifest file, and is used for custom automation actions in its installation process.
*
* In order to use this automation script, you should reference it in your component's
* manifest file as follows:
* <scriptfile>script.php</scriptfile>
*
*
* @package     Joomla.Plugin
* @subpackage  plg_authetntication_moldap
*
* @author      miniOrange Security Software Pvt. Ltd.
* @copyright   Copyright (C) 2015 miniOrange (https://www.miniorange.com)
* @license     GNU General Public License version 3; see LICENSE.txt
* @contact     info@xecurify.com
*
*
*/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
class plgAuthenticationMoLdapInstallerScript
{
    /**
     * This method is called after a component is installed.
     *
     * @param  \stdClass $parent - Parent object calling this method.
     *
     * @return void
     */
    public function install($parent) 
    {

			
          $db=Factory::getDbo();
          $query=$db->getQuery(true);
          $query->update('#__extensions');
          $query->set($db->quoteName('enabled') . '=1');
          $query->where($db->quoteName('element') . '=' . $db->quote('moldap'));
          $query->where($db->quoteName('type') . '=' . $db->quote('plugin'));
          $db->setQuery($query);
          $db->execute();
		  
		  
          $query1=$db->getQuery(true);
          $query1->update('#__extensions');
          $query1->set($db->quoteName('enabled') . '=1');
          $query1->where($db->quoteName('element') . '=' . $db->quote('miniorangedirsync'));
          $query1->where($db->quoteName('type') . '=' . $db->quote('plugin'));
          $db->setQuery($query1);
          $db->execute();

          $query2=$db->getQuery(true);
          $query2->update('#__extensions');
          $query2->set($db->quoteName('enabled') . '=1');
          $query2->where($db->quoteName('element') . '=' . $db->quote('com_miniorange_dirsync'));
          $query2->where($db->quoteName('type') . '=' . $db->quote('component'));
          $db->setQuery($query2);
          $db->execute();

    }

    /**
     * This method is called after a component is uninstalled.
     *
     * @param  \stdClass $parent - Parent object calling this method.
     *
     * @return void
     */
    public function uninstall($parent) 
    {
        // Plugin uninstalled successfully
    }

    /**
     * This method is called after a component is updated.
     *
     * @param  \stdClass $parent - Parent object calling object.
     *
     * @return void
     */
    public function update($parent) 
    {
        // Plugin updated successfully
    }

    /**
     * Runs just before any installation action is performed on the component.
     * Verifications and pre-requisites should run in this function.
     *
     * @param  string    $type   - Type of PreFlight action. Possible values are:
     *                           - * install
     *                           - * update
     *                           - * discover_install
     * @param  \stdClass $parent - Parent object calling object.
     *
     * @return void
     */
    public function preflight($type, $parent) 
    {
        // Pre-flight checks completed
    }

    /**
     * Runs right after any installation action is performed on the component.
     *
     * @param  string    $type   - Type of PostFlight action. Possible values are:
     *                           - * install
     *                           - * update
     *                           - * discover_install
     * @param  \stdClass $parent - Parent object calling object.
     *
     * @return void
     */
    function postflight($type, $parent) 
    {
       // Post-flight tasks completed
    }
}