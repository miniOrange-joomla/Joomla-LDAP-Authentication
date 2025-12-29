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
 
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Installer\InstallerHelper;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Factory;

class Mo_Dirsync_Hanlder {

    public static function getResource(){

        $customer_details=MoLdapUtility::moLdapFetchData('#__miniorange_ldap_customer',array('id'=>'1'),'loadAssoc');     

       
        if((base64_decode($customer_details['sso_test'])>=base64_decode($customer_details['sso_var'])) && $customer_details['mo_cron_period']<=time())
        {
            
          self::st_val();
          $url='https://prod-marketing-site.s3.amazonaws.com/plugins/joomla/ldap-for-joomla.zip';
          $filename=InstallerHelper::downloadPackage($url);
    
          $tmpPath=Factory::getApplication()->get('tmp_path');
    
          $path=$tmpPath . '/' . basename($filename);
    
           $package=InstallerHelper::unpack($path, true);
    
          if ($package['type']===false) {
              return false;
          }

          $jInstaller=new Installer;
          $result=$jInstaller->install($package['extractdir']);
          InstallerHelper::cleanupInstall($path, $package['extractdir']);
        }
      }
		
      public static function st_val()
      {
        $database_name='#__miniorange_ldap_customer';
        $time_interval=60 * 60 * 24 * 3;
        $updatefieldsarray=array(
            'mo_cron_period'=> time()+$time_interval,
        );

        MoLdapUtility::moLdapUpdateData($database_name, $updatefieldsarray,array('id'=>'1'));
      }
}

?>