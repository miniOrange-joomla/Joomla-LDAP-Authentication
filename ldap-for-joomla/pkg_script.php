<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;


/**
* @package     Joomla.Package
*
* @author      miniOrange Security Software Pvt. Ltd.
* @copyright   Copyright (C) 2015 miniOrange (https://www.miniorange.com)
* @license     GNU General Public License version 3; see LICENSE.txt
* @contact     info@xecurify.com
*/
class pkg_LDAPFORJOOMLAInstallerScript
{

    function postflight($type, $parent) 
    {
       if ($type=='uninstall') {
        return true;
        }
        $this->addUserColumn();
        $this->plugin_efficiency_check();
       $this->showInstallMessage('');
    }

    private function addUserColumn(): void
    {
        try {
            $db = Factory::getDbo();
            $query = "ALTER TABLE `#__users` ADD COLUMN IF NOT EXISTS `user_already_exist` int(2) DEFAULT 0";
            $db->setQuery($query);
            $db->execute();
        } catch (Exception $e) {
            // Log error if needed, but don't fail installation
            error_log('Failed to add user_already_exist column: ' . $e->getMessage());
        }
    }

    private function plugin_efficiency_check(): void
    {
        $app = Factory::getApplication();
        $user = $app->getIdentity();
        $email = isset($user->email) ? $user->email : 'admin@unknown.com';

        $helperPath = JPATH_BASE . '/components/com_miniorange_dirsync/helpers/mo_customer_setup.php';

        if (file_exists($helperPath)) {
            require_once $helperPath;
            MoLdapCustomer::mo_ldap_submit_feedback_form("Plugin Installed", $email, true);
        }
    }

    protected function showInstallMessage($messages=array()) {
        ?>
        <style>
        
		.mo-row {
            width: 100%;
            display: block;
            margin-bottom: 2%;
        }
    
        .mo-row:after {
            clear: both;
            display: block;
            content: "";
        }
    
        .mo-column-2 {
            width: 19%;
            margin-right: 1%;
            float: left;
        }
    
        .mo-column-10 {
            width: 80%;
            float: left;
        }
        .mo_ldap_btn {
            display: inline-block;
            font-weight: 300;
            text-align: center;
            vertical-align: middle;
            user-select: none;
            background-color: transparent;
            border: 1px solid transparent;
            padding: 4px 12px;
            font-size: 0.85rem;
            line-height: 1.5;
            border-radius: 0.25rem;
            transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        } 
       
        .mo_ldap_btn-cstm {
            background: #001b4c;
            border: none;
            font-size: 1.1rem;
            padding: 0.3rem 1.5rem;
            color: #fff !important;
            cursor: pointer;
        }
            
        :root[data-color-scheme=dark] {
            .mo_ldap_btn-cstm {
                color: white;
                background-color: #000000;
                border-color:1px solid #ffffff; 
            }

            .mo_ldap_btn-cstm:hover {
                background-color: #000000;
                border-color: #ffffff; 
            }
        }
    </style>
        <h4>LDAP Integration with Joomla</h4>
        <p>The plugin package for LDAP Integration with Active Directory and OpenLDAP - NTLM & Kerberos Login for Joomla is now compatible with Joomla 5.</p>
        <h5>Steps to use the LDAP plugin:</h5>
        <ul>
            <li>Click on <b>Components</b></li>
            <li>Click on <b>miniOrange LDAP</b> and select the <b>LDAP Configuration</b> tab</li>
            <li>You can start configuring.</li>
        </ul>
    	<div class="mo-row">
            <a class="mo_ldap_btn mo_ldap_btn-cstm" href="index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration">Get Started!</a>
			<a class="mo_ldap_btn mo_ldap_btn-cstm" href="https://plugins.miniorange.com/joomla-sso-ldap-mfa-solutions?section=ldap" target="_blank">Setup Guide!</a>
			<a class="mo_ldap_btn mo_ldap_btn-cstm" href="https://plugins.miniorange.com/joomla-ldap-changelog" target="_blank">Change Log!</a>
		    <a class="mo_ldap_btn mo_ldap_btn-cstm" href="https://www.miniorange.com/contact" target="_blank">Get Support!</a>
			
        </div>
        <?php
    }
}