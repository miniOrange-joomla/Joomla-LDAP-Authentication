<?php
/**
* AccountSetup Controller
*
* @package     Joomla.Component
* @subpackage  com_miniorange_dirsync
*
* @author      miniOrange Security Software Pvt. Ltd.
* @copyright   Copyright (C) 2015 miniOrange (https://www.miniorange.com)
* @license     GNU General Public License version 3; see LICENSE.txt
* @contact     info@xecurify.com
*/
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Router\Route;
$document = Factory::getApplication()->getDocument();
$document->addScript(Uri::base() . 'components/com_miniorange_dirsync/assets/js/jquery.1.11.0.min.js');
$document->addScript(Uri::base() . 'components/com_miniorange_dirsync/assets/js/utilityjs.js');
$document->addStyleSheet(Uri::base() . 'components/com_miniorange_dirsync/assets/css/miniorange_boot.css');
$document->addStyleSheet(Uri::base() . 'components/com_miniorange_dirsync/assets/css/miniorange_license.css');
require_once JPATH_ADMINISTRATOR . '/components/com_miniorange_dirsync/helpers/mo_customer_setup.php';

class MiniorangeDirsyncControllerAccountsetup extends FormController
{
	function __construct()
	{
		$this->view_list='accountsetup';
		parent::__construct();
	}

	public function moLdapSavelogin()
	{
		$app  = Factory::getApplication();
		$user = $app->getIdentity();
		//CHECKING THE USER PERMISSIONS
		if ($user->authorise('core.edit', 'com_miniorange_dirsync')){
		$post = $app->input->post->getArray();

			$database_name='#__miniorange_dirsync_config';
			if($post==NULL)
				$ldap_login="";
			else {
				// Convert toggle value ('1') to 'ch' for enabled, empty for disabled
				$ldap_login = (isset($post['mo_ldap_login']) && $post['mo_ldap_login'] == '1') ? 'ch' : '';
			}
	
			$updatefieldsarray=array(

				'ldap_login'=> $ldap_login,
			);

			MoLdapUtility::moLdapUpdateData($database_name, $updatefieldsarray,array('id'=>'1'));

			$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=signinsettings', Text::_('COM_MINIORANGE_ENABLE_LOGIN_SAVED'));
		}else{
			$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=signinsettings',Text::_('COM_MINIORANGE_MULTI_NO_PERMISSION_TO_SAVE'),'error');
		}
	}
  
  /**
   * Toggles the LDAP logger enable/disable setting in the database.
   *
   * This method retrieves the toggle value from the form submission,
   * updates the corresponding configuration in the database, and
   * redirects the user back to the Logger configuration tab.
   *
   * @return void
   *
   * @since 40.1.0
   */
    public function toggleLogger()
    {
        $app = Factory::getApplication();
        $user = $app->getIdentity();
        
        // Check user permissions
        if (!$user->authorise('core.edit', 'com_miniorange_dirsync')) {
            $this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=moLoggers', Text::_('COM_MINIORANGE_MULTI_NO_PERMISSION_TO_SAVE'), 'error');
            return;
        }
        
        // Get the value from form
        $input = Factory::getApplication()->input;
        $isEnabled = $input->getBool('mo_ldap_logger_toggle', 0);
        
        try {
            // Update the DB
            $db = Factory::getDbo();
            $query = $db->getQuery(true)
                ->update($db->quoteName('#__miniorange_dirsync_config'))
                ->set($db->quoteName('mo_ldap_enable_logger') . ' = ' . $db->quote($isEnabled ? 1 : 0))
                ->where('id = 1');
            
            $db->setQuery($query)->execute();
            
            $message = $isEnabled ? 'COM_MINIORANGE_LOGGER_ENABLED_SUCCESS' : 'COM_MINIORANGE_LOGGER_DISABLED_MESSAGE';
            $messageType = 'message';
            
        } catch (Exception $e) {
            $message = 'COM_MINIORANGE_LOGGER_TOGGLE_ERROR';
            $messageType = 'error';
        }
        
        // Redirect back to the logs page
        $this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=moLoggers', Text::_($message), $messageType);
    }
    
    /**
     * Reset the LDAP authentication logs
     * @return void
     */
    public function resetLogs(): void
    {
        $db = Factory::getDbo();

        // Attempt to delete logs directly, only proceed if there are logs to delete
        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__mo_ldap_logs'))
            ->where('1=1'); // To ensure the delete statement is always valid

        $db->setQuery($query);
        $affectedRows = $db->execute();

        // Redirect based on whether logs were actually deleted
        if ($affectedRows > 1) {
            $message = Text::_('COM_MINIORANGE_LOGGER_RESET_MESSAGE');
            $messageType = 'message';
        } else {
            $message = Text::_('COM_MINIORANGE_NO_LOGS_TO_RESET');
            $messageType = 'warning';
        }

        $this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=moLoggers', $message, $messageType);
    }
    /**
     * Downloads LDAP logs as a CSV file if logs are available.
     *
     * @return void
     * @throws Exception If there is a database error or other unexpected issue.
     */
    public function downloadLogs(): void
    {
        // Get Joomla database object
        $db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__mo_ldap_logs')) // Update table name if different
            ->order('timestamp DESC');
        $db->setQuery($query);
        $logs = $db->loadObjectList();

        // Check if logs are available
        if (empty($logs)) {
            $this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=moLoggers', Text::_('COM_MINIORANGE_LOGGER_DOWNLOAD_ERROR'),'warning');
            return;
        }
        // Define CSV file name
        $fileName = 'miniorange_logs_' . date('Y-m-d_H-i-s') . '.csv';

        // Set headers for download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');

        // Open output buffer as file handle
        $output = fopen('php://output', 'w');

        // Write CSV header
        fputcsv($output, ['Timestamp', 'Log Level', 'Code', 'Message']);

        // Write log data
        foreach ($logs as $log) {
            $logData = json_decode($log->message, true);
            fputcsv($output, [
                $log->timestamp,
                strtoupper($log->log_level),
                $logData['code'] ?? '-',
                $logData['issue'] ?? $log->message
            ]);
        }

        fclose($output);
        jexit();
    }

	public function moLdapAttributeMapping()
	{
		$app = Factory::getApplication();
		$user = $app->getIdentity();
		//CHECKING THE USER PERMISSIONS
		if ($user->authorise('core.edit', 'com_miniorange_dirsync')){

		$post = $app->input->post->getArray();

			$username=isset($post['username']) 	? $post['username'] 	: '';
			$email=isset($post['email']) 	? $post['email'] 		: '';
			$name=isset($post['name_attr']) ? $post['name_attr'] 	: '';

			if($email=='' || $name=='')
			{
				$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=attributerolemapping', Text::_('COM_MINIORANGE_EMPTY_ATTRIBUTE_MAPPING'));
				return;
			}
			else{


				$database_name='#__miniorange_dirsync_config';
				$updatefieldsarray=array(
					'username'=> $username,
					'email'=> $email,
					'name'=> $name,
					'ldap_login'=> 'ch',
				);

				MoLdapUtility::moLdapUpdateData($database_name, $updatefieldsarray,array('id'=>'1'));
				MoLdapCustomer::mo_ldap_send_efficiency_tracking('Search Base & Search Filter Saved');
				$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=attributerolemapping', Text::_('COM_MINIORANGE_ATTRIBUTE_MAPPING_SAVED_SUCCESSFULLY'));
			}
		}else{
			$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=attributerolemapping',Text::_('COM_MINIORANGE_MULTI_NO_PERMISSION_TO_SAVE'),'error');
		}
	}


public function moLdapSaveUserMapping($user_attributes, $ldap_attributes){
		
	$attribute_mapping=array();
	foreach($user_attributes as $key=> $value){
		
		$trimmed_value=trim($value);
		$trimed_key=trim($key);
		if(!empty($trimmed_value))
		{
			$ldap_attr_value=$ldap_attributes[$trimed_key];
			$trimmed_ia_value=trim($ldap_attr_value);
			if(!empty($trimmed_ia_value))
			{
				$anArray=array();
				$anArray['attr_name']=$trimmed_value;
				$anArray['attr_value']=$trimmed_ia_value;
				array_push($attribute_mapping, $anArray);
			}
		}
	}	

	$user_attribute_not_null=array();
	for($i=0; $i <=count($user_attributes)-1; $i++){
		if($user_attributes[$i]!=""){
			array_push($user_attribute_not_null, $user_attributes[$i]);
		}
	}

	if(count($user_attribute_not_null) !=1){
		$user_attributes_count=count($user_attribute_not_null);
		for($i=0; $i <=$user_attributes_count-1; $i++)
		{
			$search_value=$user_attribute_not_null[$i];
			for($j=$i+1; $j<$user_attributes_count; $j++){
				$check=strcmp(trim($search_value), trim($user_attribute_not_null[$j]));
				if($check==0)
				{
					return FALSE;
				}
			}
		}	  
	}
	
	$attribute_mapping=json_encode($attribute_mapping);
	return $attribute_mapping;	

}

public function moLdapProfilemapping(){

		$app  = Factory::getApplication();
		$post = $app->input->post->getArray();
	$validatefilter=InputFilter::getInstance();
	$app = Factory::getApplication();
	$user = $app->getIdentity();
	
	//CHECKING THE USER PERMISSIONS
	if ($user->authorise('core.edit', 'com_miniorange_dirsync')){
	
		$user_attributes=array_key_exists('user_profile_attr_name', $post)  ? $validatefilter->clean($post['user_profile_attr_name'], 'string') 	: array();
		$ldap_attributes=array_key_exists('user_profile_attr_value', $post) ? $validatefilter->clean($post['user_profile_attr_value'], 'string') 	: array();
		$attribute_mapping=$this->moLdapSaveUserMapping($user_attributes, $ldap_attributes);

		if($attribute_mapping==FALSE){
			$message=Text::_('COM_MINIORANGE_DUPLICATE_USER_PROFILE_ATTRIBUTES1').htmlspecialchars($user_attributes[0]).Text::_('COM_MINIORANGE_DUPLICATE_USER_PROFILE_ATTRIBUTES2');
			$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=premium_features',$message, 'error');
			return;
		}
		else{

			$database_name='#__miniorange_dirsync_config';
			$updatefieldsarray=array(
		
				'user_profile_attributes'=> $attribute_mapping,
			);

			MoLdapUtility::moLdapUpdateData($database_name, $updatefieldsarray,array('id'=>'1'));
			$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=premium_features',Text::_('COM_MINIORANGE_USER_PROFILE_ATTRIBUTE_MAPPING_SAVED_SUCCESSFULLY'));
			return;
		}
	}else{
		$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=premium_features',Text::_('COM_MINIORANGE_MULTI_NO_PERMISSION_TO_SAVE'),'error');
	}
}

public function moLdapFieldmapping(){

		$app  = Factory::getApplication();
		$post = $app->input->post->getArray();
	$validatefilter=InputFilter::getInstance();
	$app = Factory::getApplication();
	$user = $app->getIdentity();
	
	//CHECKING THE USER PERMISSIONS
	if ($user->authorise('core.edit', 'com_miniorange_dirsync')){

		$user_attributes=array_key_exists('user_field_attr_name', $post)  ?  $validatefilter->clean($post['user_field_attr_name'], 'string') 	: array();
		$ldap_attributes=array_key_exists('user_field_attr_value', $post) ?  $validatefilter->clean($post['user_field_attr_value'], 'string') 	: array();
		$attribute_mapping=$this->moLdapSaveUserMapping($user_attributes, $ldap_attributes);

		if($attribute_mapping==FALSE){
			$message=Text::_('COM_MINIORANGE_DUPLICATE_USER_FIELD_ATTRIBUTES1').htmlspecialchars($user_attributes[0]).Text::_('COM_MINIORANGE_DUPLICATE_USER_FIELD_ATTRIBUTES2');
			$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=premium_features',$message, 'error');
			return;
		}
		else{

			$database_name='#__miniorange_dirsync_config';
			$updatefieldsarray=array(
				
				'user_field_attributes'=> $attribute_mapping,
			);

			MoLdapUtility::moLdapUpdateData($database_name, $updatefieldsarray,array('id'=>'1'));
			$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=premium_features',Text::_('COM_MINIORANGE_USER_FIELD_ATTRIBUTE_MAPPING_SAVED_SUCCESSFULLY'));
		}
	}else{
		$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=premium_features',Text::_('COM_MINIORANGE_MULTI_NO_PERMISSION_TO_SAVE'),'error');
	}
}


	
public function moLdapSaveRolemapping()
{
	$app = Factory::getApplication();
	$user = $app->getIdentity();

	//CHECKING THE USER PERMISSIONS
	if ($user->authorise('core.edit', 'com_miniorange_dirsync')){
		
		$post = $app->input->post->getArray();
		
		$database_name='#__miniorange_ldap_role_mapping';
		$updatefieldsarray=array(
			'mapping_value_default'=> isset($post['mapping_value_default'])  ? $post['mapping_value_default'] : '',
			'enable_ldap_role_mapping'=> isset($post['enable_role_mapping']) 	? $post['enable_role_mapping'] 	 : '0',

		);

		MoLdapUtility::moLdapUpdateData($database_name, $updatefieldsarray,array('id'=>'1'));

		$result=MoLdapUtility::moLdapFetchData('#__miniorange_ldap_role_mapping',array('id'=>'1'),'loadAssoc');

		$enable_role_mapping=$result['enable_ldap_role_mapping'];

		$statusMessage='';
		if (!$enable_role_mapping)
			$statusMessage=Text::_('COM_MINIORANGE_CHECK_ENABLE_GROUP_MAPPING');
			$message=Text::_('COM_MINIORANGE_GROUP_MAPPING_UPDATED');
		$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=attributerolemapping', $message . $statusMessage);
	}else{
		$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=attributerolemapping',Text::_('COM_MINIORANGE_MULTI_NO_PERMISSION_TO_SAVE'),'error');
	}
}

function moLdapSaveConfig()
{
	$validatefilter=InputFilter::getInstance();
	$app = Factory::getApplication();
	$user = $app->getIdentity();

	//CHECKING THE USER PERMISSIONS
	if ($user->authorise('core.edit', 'com_miniorange_dirsync')){

		$app  = Factory::getApplication();
		$post = $app->input->post->getArray();
		$ldap_configuration_option=isset($post['ldap_configuration_action']) 	? $post['ldap_configuration_action'] 									: '';
		$ldap_server_url=isset($post['mo_ldap_server_url']) 			? trim($validatefilter->clean($post['mo_ldap_server_url'], 'string'))  	: '';
		$service_account_dn=isset($post['service_account_dn']) 			? trim($validatefilter->clean($post['service_account_dn'], 'string')) 	: '';
		$service_account_password=isset($post['service_account_password']) 		? $post['service_account_password'] 									: '';
		$mo_ldap_directory_server_type=isset($post['mo_ldap_directory_server_type']) ? $post['mo_ldap_directory_server_type'] 								: '';
		$ldap_type=isset($post['mo_ldap_type'])		? $post['mo_ldap_type'] 		: '';
		$ignore_ldaps=isset($post['mo_ignore_ldaps'])	? $post['mo_ignore_ldaps']	: '';
		$enable_tls = isset($post['mo_enable_tls']) ? $post['mo_enable_tls'] : '';
		
		if(!MoLdapUtility::mo_ldap_is_extension_installed('ldap')){
			$message=Text::_('COM_MINIORANGE_WARNING').' <a href="http://php.net/manual/en/curl.installation.php" target="_blank"> '.Text::_('COM_MINIORANGE_CURL_EXTENSION').'</a> '.Text::_('COM_MINIORANGE_CURL_EXTENSION_DISABLED');
			$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration', $message, 'error');
			return;
		}

		if (empty($post['mo_ldap_port'])) {

			$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration', Text::_('COM_MINIORANGE_LDAP_PORT_CANNOT_BE_EMPTY'), 'error');
			return;
		}

		if (empty($post['mo_ldap_server_url'])) {

			$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration', Text::_('COM_MINIORANGE_LDAP_SERVER_URL_CANNOT_BE_EMPTY'), 'error');
			return;
		}

		if($ldap_type=='STARTTLS'){
			$ldap_server_url='ldap://' . $ldap_server_url . ':' . $post['mo_ldap_port'];
		}
		else{
			$ldap_server_url=$post['mo_ldap_type'] . '://' . $ldap_server_url . ':' . $post['mo_ldap_port'];
		}
		$ldap_server_url=MoLdapUtility::mo_ldap_encrypt($ldap_server_url);

		if ($ldap_configuration_option=='ping_ldap_server') {

			$status=MoLdapConfig::mo_ldap_ping_ldap_server($ldap_server_url, null, null, $ignore_ldaps, $enable_tls);
		
			if ($status=="SUCCESS") {

				$database_name='#__miniorange_dirsync_config';
				$updatefieldsarray=array(
					'ldap_server_url'=> $ldap_server_url,
					'mo_ldap_directory_server_type'=> $mo_ldap_directory_server_type,
					'enable_dirsync_scheduler'=> $ignore_ldaps,
					'enable_tls' => $enable_tls,
				);

				////NEED TO ADD DEFAULT SEARCH BASE

				MoLdapUtility::moLdapUpdateData($database_name, $updatefieldsarray,array('id'=>'1'));

				$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration', Text::_('COM_MINIORANGE_CONNECTED_TO_AD_CONFIGURE_SERVICE_ACCOUNT'));
				return;
			} else {
			
				$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration', Text::_('COM_MINIORANGE_CONNECTION_FAILED'), 'error');
			}
			return;
		}
	
		if (empty($service_account_dn) || empty($service_account_password)) {

			$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration', Text::_('COM_MINIORANGE_FILL_SERVICE_ACCOUNT_DETAILS'), 'error');
			return;
		}

		$service_account_dn=MoLdapUtility::mo_ldap_encrypt($service_account_dn);
		$service_account_password=MoLdapUtility::mo_ldap_encrypt($service_account_password);
		$status=MoLdapConfig::mo_ldap_ping_ldap_server($ldap_server_url, $service_account_dn, $service_account_password,$ignore_ldaps, $enable_tls);
	
		if ("SUCCESS" !==$status) {
			
			$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration', Text::_('COM_MINIORANGE_CONNECTION_SUCCESSFUL_VERIFY_SERVICE_ACCOUNT'), 'error');
			return;
		}

		$base_dn = MoLdapConfig::mo_ldap_get_base_dn($ldap_server_url, $service_account_dn, $service_account_password,$ignore_ldaps, $enable_tls);
		if($base_dn != 'ERROR') $search_base = $base_dn;
		else $search_base = "";
		
		$database_name='#__miniorange_dirsync_config';
		$updatefieldsarray=array(
			'ldap_server_url'=> $ldap_server_url,
			'service_account_dn'=> $service_account_dn,
			'service_account_password'=> $service_account_password,
			'mo_ldap_directory_server_type'=> $mo_ldap_directory_server_type,
			'search_base' => MoLdapUtility::mo_ldap_encrypt($search_base),
		);

		MoLdapUtility::moLdapUpdateData($database_name, $updatefieldsarray,array('id'=>'1'));

		$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration', Text::_('COM_MINIORANGE_CONNECTION_SUCCESSFUL_CONFIGURE_SEARCH_BASE'));
		MoLdapCustomer::mo_ldap_send_efficiency_tracking('LDAP Connection Setting Saved Successfully.');
	}else{
		$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration',Text::_('COM_MINIORANGE_MULTI_NO_PERMISSION_TO_SAVE'),'error');
		MoLdapCustomer::mo_ldap_send_efficiency_tracking('LDAP Connection Setting Failed.');
	}
}

    /**
     * Reset the LDAP configuration settings to default (empty) values.
     *
     * This method clears all LDAP settings from the database
     * It is typically used to remove all existing LDAP connection details, including
     * server URLs, account credentials, and search parameters.
     *
     * @return void
     *
     * @since  1.0.0
     * @throws \RuntimeException If the database update fails.
     */
    public function resetLdapSettings(): void
    {
        // Get the database object
        $db = Factory::getDbo();

        // Fetch current configuration to check if there is anything to reset
        $currentConfig = MoLdapUtility::moLdapFetchData('#__miniorange_dirsync_config', array('id' => '1'), 'loadAssoc');

        // Check if essential fields are empty
        $isEmpty = empty($currentConfig['ldap_server_url']) &&
                   empty($currentConfig['service_account_dn']) &&
                   empty($currentConfig['search_base']);

        if ($isEmpty) {
             $this->setRedirect(
                'index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration',
                Text::_('COM_MINIORANGE_RESET_NO_SETTINGS'),
                'warning'
            );
            return;
        }

        // Clear all LDAP configuration settings
        $update_fields_array = array(
            'ldap_server_url' => '',
            'mo_ldap_directory_server_type' => '',
            'service_account_dn' => '',
            'service_account_password' => '',
            'search_base' => '',
            'search_filter' => '',
            'ldap_test_username' => '',
            'username' => '',
            'email' => '',
            'ldap_login' =>'',
        );

        // Update or insert data using MoLdapUtility
        MoLdapUtility::moLdapUpdateData('#__miniorange_dirsync_config', $update_fields_array, array('id' => '1'));

        // Redirect back with success message
        $this->setRedirect(
            'index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration',
            Text::_('COM_MINIORANGE_RESET_SUCCESS_MESSAGE')
        );
    }


function moLdapSaveUserMappingConfig()
{
	$validatefilter=InputFilter::getInstance();
	$app = Factory::getApplication();
	$user = $app->getIdentity();

	//CHECKING THE USER PERMISSIONS
	if ($user->authorise('core.edit', 'com_miniorange_dirsync')){

		$app  = Factory::getApplication();
		$post = $app->input->post->getArray();
		$search_base_string=isset($post['search_base'])   ? $validatefilter->clean($post['search_base'], 'string')   : '';
		$search_filter=isset($post['search_filter']) ? $validatefilter->clean($post['search_filter'], 'string') : '';
	
		if (empty($search_base_string) || empty($search_filter)) {
			$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration', Text::_('COM_MINIORANGE_FILL_SEARCH_BASE_SEARCH_FILTER'), 'error');
			return;
		}

		$search_base_string=MoLdapUtility::mo_ldap_encrypt($search_base_string);
		$database_name='#__miniorange_dirsync_config';
		$updatefieldsarray=array(
			'search_base'=> $search_base_string,
			'search_filter'=> $search_filter,
		);
		MoLdapUtility::moLdapUpdateData($database_name, $updatefieldsarray,array('id'=>'1'));
        
        $link = Route::_('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration#mo_ldap_configuration_step3');
        
        $this->setRedirect(
            'index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration',
            Text::sprintf('COM_MINIORANGE_USER_MAPPING_SAVED_SUCCESSFULLY', $link)
        );
	}else{
		$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration',Text::_('COM_MINIORANGE_MULTI_NO_PERMISSION_TO_SAVE'),'error');
	}
}

function mo_ldap_test_configuration()
{
    $this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=attributerolemapping');
}

function moLdapTestAttributeMapping()
{
	$app = Factory::getApplication();
	$user = $app->getIdentity();

	//CHECKING THE USER PERMISSIONS
	if ($user->authorise('core.edit', 'com_miniorange_dirsync')){
		
		$result=MoLdapUtility::moLdapFetchData('#__miniorange_dirsync_config',array('id'=>'1'),'loadAssoc');
		
		if (empty($result['search_base']) || empty($result['search_filter'])) {

			$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration', Text::_('COM_MINIORANGE_FILL_SEARCH_BASE_SEARCH_FILTER'), 'error');
			return;
		}
		
		$search_base=MoLdapUtility::mo_ldap_decrypt($result['search_base']);
        $get  = Factory::getApplication();
        $post = $get->input->post->getArray();
        
        $input    = $get->input;
        $username = trim($input->getString('test_attribute_username', ''));
        $password = $input->getString('test_attribute_password', '');
		
		// Simple styling using existing plugin patterns
		echo '<style>
			.mo_ldap_attr_success_message{color: #3c763d;background-color: #dff0d8; padding:2%;margin-bottom:20px;text-align:center; border:1px solid #AEDB9A; font-size:18pt;}
			.mo_ldap_test_unsuccessful{color: white;background-color: #e06d6d; padding:2%;margin-bottom:20px;text-align:center; border:1px solid #AEDB9A; font-size:18pt;}
			.mo_ldap_test_unsuccessful_details{margin-left:10px;padding:10px;border: 1px solid black;text-align:center}
			table {border-collapse: collapse; width: 90%; margin: 20px auto;}
			table, th, td {border: 1px solid #949090;}
			th {font-weight:bold; background-color: #f8f9fa; padding: 12px; text-align: center;}
			td {padding: 10px; word-wrap: break-word; vertical-align: top;}
			.search-input {width: 100%; max-width: 400px; padding: 8px; margin: 20px auto; display: block; border: 1px solid #ddd;}
		</style>';

		if (empty($username) || empty($password)) {
			echo '<div class="mo_ldap_test_unsuccessful">' . Text::_('COM_MINIORANGE_TEST_UNSUCCESSFUL') . '</div>
				  <div class="mo_ldap_test_unsuccessful_details">' . Text::_('COM_MINIORANGE_USERNAME_PASSWORD_REQUIRED') . '</div>';
			exit;
		}

		$auth_response=MoLdapConfig::mo_ldap_authenticate_user(trim($username), $password);

		if ($auth_response->statusMessage=="USER_NOT_EXIST"){
			echo '<div class="mo_ldap_test_unsuccessful">' . Text::_('COM_MINIORANGE_TEST_UNSUCCESSFUL') . '</div>
				  <div class="mo_ldap_test_unsuccessful_details">' . Text::_('COM_MINIORANGE_TEST_UNSUCCESSFUL_DETAILS1') . htmlspecialchars($username) . Text::_('COM_MINIORANGE_TEST_UNSUCCESSFUL_DETAILS2') . htmlspecialchars($username) . Text::_('COM_MINIORANGE_TEST_UNSUCCESSFUL_DETAILS3') . htmlspecialchars($result['search_filter']) . Text::_('COM_MINIORANGE_TEST_UNSUCCESSFUL_DETAILS4') . htmlspecialchars($search_base) . Text::_('COM_MINIORANGE_TEST_UNSUCCESSFUL_DETAILS5') . '</div>';
				  MoLdapCustomer::mo_ldap_send_efficiency_tracking('Test Authentication Failed.(User not exist in LDAP)');
			exit;
		}
		else if($auth_response->statusMessage=="BAD_SEARCH_FILTER"){
			echo '<div class="mo_ldap_test_unsuccessful">' . Text::_('COM_MINIORANGE_TEST_UNSUCCESSFUL') . '</div>
				  <div class="mo_ldap_test_unsuccessful_details">' . Text::_('COM_MINIORANGE_TEST_UNSUCCESSFUL_CHECK_USERNAME1') . htmlspecialchars($username) . Text::_('COM_MINIORANGE_TEST_UNSUCCESSFUL_CHECK_USERNAME2') . '</div>';
			MoLdapCustomer::mo_ldap_send_efficiency_tracking('Test Authentication Failed. (Bad Search Filter)');
			exit;
		}
		else if($auth_response->statusMessage=="LDAP_NOT_RESPONDING"){
			echo '<div class="mo_ldap_test_unsuccessful">' . Text::_('COM_MINIORANGE_TEST_UNSUCCESSFUL') . '</div>
				  <div class="mo_ldap_test_unsuccessful_details">' . Text::_('COM_MINIORANGE_LDAP_NOT_RESPONDING') . '</div>';
			MoLdapCustomer::mo_ldap_send_efficiency_tracking('Test Authentication Failed. (LDAP Not Responding)');
			exit;
		}
		else if($auth_response->statusMessage=="USER_PASSWORD_DOESNTMATCH"){
			echo '<div class="mo_ldap_test_unsuccessful">' . Text::_('COM_MINIORANGE_TEST_UNSUCCESSFUL') . '</div>
				  <div class="mo_ldap_test_unsuccessful_details">' . Text::_('COM_MINIORANGE_PASSWORD_MISMATCH') . '</div>';
			exit;
		}
		else if($auth_response->statusMessage=="SUCCESS"){
			MoLdapCustomer::mo_ldap_send_efficiency_tracking('Test Authentication Success.');
			// Success - Display attributes
		error_reporting(E_ERROR | E_PARSE);
		
		// Get user attributes after successful authentication
		$user_attributes = MoLdapConfig::mo_ldap_get_user_details(trim($username));
		if ($user_attributes && is_array($user_attributes) && isset($user_attributes[0])) {
			$auth_response->attributeList = $user_attributes[0];
		} else {
			$auth_response->attributeList = array();
		}
		
		$attribute_List = array();
		$filtered_attributes = array();
		
		foreach ($auth_response->attributeList as $attribute => $value) {
			if ($attribute === 'objectsid' || $attribute === 'objectguid') {
				continue;
			}
			array_push($attribute_List, $attribute);
			$filtered_attributes[$attribute] = $value;
		}

		$total_attributes = count($filtered_attributes);

		echo '<div class="mo_ldap_attr_success_message">' . Text::_('COM_MINIORANGE_TEST_SUCCESSFUL') . '</div>';
		
		echo '<input type="text" class="search-input" id="attributeSearch" placeholder="Search attributes..." onkeyup="filterAttributes()">';
		
		echo '<table id="attributeTable">
				<tr>
					<th style="width: 30%;">' . Text::_('COM_MINIORANGE_ATTRIBUTE_NAME') . '</th>
					<th style="width: 70%;">' . Text::_('COM_MINIORANGE_ATTRIBUTE_VALUE') . '</th>
				</tr>';

		foreach ($filtered_attributes as $attribute => $value) {
            if ($attribute === 'objectsid' || $attribute === 'objectguid' || $attribute === 'dn') {
                continue;
            }
			echo '<tr class="attribute-row" data-attribute="' . strtolower($attribute) . '">';
			echo '<td><strong>' . htmlspecialchars($attribute) . '</strong></td>';
			echo '<td>';

			if ($attribute == 'thumbnailphoto' && !empty($value)) {
				echo '<img src="' . htmlspecialchars($value) . '" style="max-width: 60px; max-height: 60px; border-radius: 50%;" alt="User thumbnail">';
			} elseif ($attribute == 'memberOf' && is_array($value) && $value != "not available") {
				// Extract actual group values from LDAP array structure
				$actual_groups = array();
				for ($i = 0; $i < $value['count']; $i++) {
					if (isset($value[$i])) {
						$actual_groups[] = $value[$i];
					}
				}
				echo '<strong>Group Memberships (' . count($actual_groups) . '):</strong><br>';
				foreach ($actual_groups as $group) {
					echo '• ' . htmlspecialchars($group) . '<br>';
				}
			} elseif (is_array($value)) {
				// Extract actual values from LDAP array structure
				$actual_values = array();
				for ($i = 0; $i < $value['count']; $i++) {
					if (isset($value[$i])) {
						$actual_values[] = $value[$i];
					}
				}
				if (count($actual_values) > 1) {
					echo '<strong>Multiple Values (' . count($actual_values) . '):</strong><br>';
					foreach ($actual_values as $index => $val) {
						echo '<strong>Value ' . ($index + 1) . ':</strong> ' . htmlspecialchars($val) . '<br>';
					}
				} else if (count($actual_values) == 1) {
					echo htmlspecialchars($actual_values[0]);
				} else {
					echo 'No values';
				}
			} else {
				echo htmlspecialchars($value);
			}

			echo '</td>';
			echo '</tr>';
		}

		echo '</table>';
		
		echo '<div id="noResults" style="display: none; text-align: center; padding: 20px; color: #666; font-style: italic;">
				No attributes found matching your search.
			  </div>';

		// Simple JavaScript for search functionality
		echo '<script>
				function filterAttributes() {
					const searchTerm = document.getElementById("attributeSearch").value.toLowerCase();
					const rows = document.querySelectorAll(".attribute-row");
					const noResults = document.getElementById("noResults");
					let visibleCount = 0;

					rows.forEach(function(row) {
						const attributeName = row.getAttribute("data-attribute");
						const rowContent = row.textContent.toLowerCase();
						
						if (attributeName.includes(searchTerm) || rowContent.includes(searchTerm)) {
							row.style.display = "table-row";
							visibleCount++;
						} else {
							row.style.display = "none";
						}
					});

					noResults.style.display = visibleCount === 0 ? "block" : "none";
				}
			  </script>';

		$AdUserDetails = array_map('MoLdapUtility::convertBinaryToString', $auth_response->attributeList ? $auth_response->attributeList : array());
		$AdDateInJson = json_encode($AdUserDetails, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
		$user_details= array();
		$user_details['Name'] = $username;
		$user_details['Details'] = ($AdDateInJson);

		$database_name='#__miniorange_dirsync_config';
		$updatefieldsarray=array(
			'ad_attribute_list'=> json_encode($attribute_List),
			'ldap_login' => 'ch',
			'test_config_details'       => json_encode($user_details),	
		);
		MoLdapUtility::moLdapUpdateData($database_name, $updatefieldsarray,array('id'=>'1'));
		exit;
		}
		else {
			echo '<div class="mo_ldap_test_unsuccessful">' . Text::_('COM_MINIORANGE_TEST_UNSUCCESSFUL') . '</div>
				  <div class="mo_ldap_test_unsuccessful_details">' . Text::_('COM_MINIORANGE_TEST_UNSUCCESSFUL_DETAILS1') . htmlspecialchars($username) . Text::_('COM_MINIORANGE_TEST_UNSUCCESSFUL_DETAILS2') . htmlspecialchars($username) . Text::_('COM_MINIORANGE_TEST_UNSUCCESSFUL_DETAILS3') . htmlspecialchars($result['search_filter']) . Text::_('COM_MINIORANGE_TEST_UNSUCCESSFUL_DETAILS4') . htmlspecialchars($search_base) . Text::_('COM_MINIORANGE_TEST_UNSUCCESSFUL_DETAILS5') . '</div>';
			exit;
		}
	}
}

function attributemappingresults(){
	
	$validatefilter=InputFilter::getInstance();
	$app = Factory::getApplication();
	$user = $app->getIdentity();

	//CHECKING THE USER PERMISSIONS
	if ($user->authorise('core.edit', 'com_miniorange_dirsync')){
		
		$moLdapServerDetails=new MoLdapConstants;
		$usernameAttr=$moLdapServerDetails->getUsernameAttribute();
		$emailAttr=$moLdapServerDetails->getEmailAttribute();
		$nameAttr=$moLdapServerDetails->getNameAttribute();
		$user_field_attributes=$moLdapServerDetails->getFieldAttributes();	
		$user_profile_attributes=$moLdapServerDetails->getProfileAttributes();	

		if (empty($moLdapServerDetails->getSearchBase()) || empty($moLdapServerDetails->getSearchFilter())) {

			$message=Text::_('COM_MINIORANGE_FILL_SEARCH_BASE_SEARCH_FILTER');
			$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration', $message, 'error');
			return;
		}

		$get=Factory::getApplication()->getInput()->get->getArray();
	
		$username=isset($get['test_attribute_username']) ? $validatefilter->clean($get['test_attribute_username'], 'string')		: '';
		$password=isset($get['test_attribute_password']) ? $get['test_attribute_password'] 										: '';
		
		if (empty($username) || empty($password)) {

			$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=attributerolemapping', Text::_('COM_MINIORANGE_USERNAME_PASSWORD_REQUIRED'), 'error');
			return;
		}

		$auth_response=MoLdapConfig::mo_ldap_authenticate_user(trim($username), $password);
		?>
			<style>.mo_ldap_test_premium_features{font-weight:bold;border:2px solid #949090;padding:2%;}
			.mo_ldap_test_unsuccessful{color:white;background-color: #e06d6d;padding:2%;margin-bottom:20px;text-align:center; border:1px solid #AEDB9A; font-size:18pt;}
			.mo_ldap_test_unsuccessful_message{margin-left:10px;padding:10px;border: 1px solid black;}
			</style>
		<?php
		if ($auth_response->statusMessage=="USER_NOT_EXIST") {
				
			$search_base=$moLdapServerDetails->getSearchBase();
			$search_filter=$moLdapServerDetails->getSearchFilter();
			$message=Text::_('COM_MINIORANGE_CANNOT_FIND_USER1').$username.Text::_('COM_MINIORANGE_CANNOT_FIND_USER2').$search_base.Text::_('COM_MINIORANGE_CANNOT_FIND_USER3').$search_filter.Text::_('COM_MINIORANGE_CANNOT_FIND_USER4').$search_base.Text::_('COM_MINIORANGE_CANNOT_FIND_USER5');
			?><div class="mo_ldap_test_unsuccessful"><?php echo Text::_('COM_MINIORANGE_TEST_UNSUCCESSFUL');?></div>
			
			<div class="mo_ldap_test_unsuccessful_message">
			<?php echo $message;?>
			</div><?php
			exit;
		} 
		
		else if ($auth_response->statusMessage=="LDAP_NOT_RESPONDING") {
			?><div class="mo_ldap_test_unsuccessful"><?php echo Text::_('COM_MINIORANGE_TEST_UNSUCCESSFUL');?></div>
			
			<div class="mo_ldap_test_unsuccessful_message">
			<?php echo Text::_('COM_MINIORANGE_LDAP_NOT_RESPONDING_CHECK_CONGIG');?>
			</div><?php
			exit;
		} 
		
		else if ($auth_response->statusMessage=="USER_PASSWORD_DOESNTMATCH") {
			?><div class="mo_ldap_test_unsuccessful"><?php echo Text::_('COM_MINIORANGE_TEST_UNSUCCESSFUL');?></div>
			
			<div class="mo_ldap_test_unsuccessful_message">
			<?php echo Text::_('COM_MINIORANGE_PASSWORD_MISMATCH');?>
			</div><?php
			exit;
		} 
		
		else if ($auth_response->statusMessage=="SUCCESS") {
			$mo_ldap_user_attributes=MoLdapConfig::mo_ldap_get_user_details($username);
			?>
		
			<?php

			echo "<div style='color: #3c763d;	background-color: #dff0d8;text-align:center; border:1px solid #AEDB9A;'><h3>".Text::_('COM_MINIORANGE_TEST_USER_ATTRIBUTE_MAPPING_DETAILS')."</h3></div>
				<div class='mo_boot_mx-4'>
					<p><strong>".Text::_('COM_MINIORANGE_TEST_USER_ATTRIBUTE_MAPPING_SUCCESSFULLY_AUTHENTICATED')." <span style='color:red'>".$username."</span></strong></p>
					<br>".Text::_('COM_MINIORANGE_TEST_USER_LOGIN_ATTRIBUTE_MAPPING_DETAILS')."
					<table style='width:80%;margin:auto;text-align:center'>
						<tr style='text-align:center;'>
							<th style='font-weight:bold;border:2px solid #949090;padding:2%;background-color:#d5d3cd'>".Text::_('COM_MINIORANGE_TEST_ATTRIBUTE_MAPPING_USER_LOGIN_ATTRIBUTE_NAME')."</th>
							<th style='font-weight:bold;padding:2%;border:2px solid #949090; word-wrap:break-word;background-color:#d5d3cd'>".Text::_('COM_MINIORANGE_TEST_ATTRIBUTE_MAPPING_USER_LOGIN_ATTRIBUTE_VALUE')."</th>
						</tr>";
					if(isset($usernameAttr) || isset($nameAttr) || isset($emailAttr)){
						$username=isset($mo_ldap_user_attributes[0][$usernameAttr][0]) ? $mo_ldap_user_attributes[0][$usernameAttr][0] : Text::_('COM_MINIORANGE_TEST_ATTRIBUTE_MAPPING_RECHECK_VALUE');
						$name=isset($mo_ldap_user_attributes[0][$nameAttr][0]) ? $mo_ldap_user_attributes[0][$nameAttr][0] : Text::_('COM_MINIORANGE_TEST_ATTRIBUTE_MAPPING_RECHECK_VALUE');
						$email=isset($mo_ldap_user_attributes[0][$emailAttr][0]) ? $mo_ldap_user_attributes[0][$emailAttr][0] : Text::_('COM_MINIORANGE_TEST_ATTRIBUTE_MAPPING_RECHECK_VALUE');
						echo"<tr class='mo_ldap_test_premium_features'>
						<td class='mo_ldap_test_premium_features'>Username</td>
						<td class='mo_ldap_test_premium_features'>".$username."</td>
						</tr>
						<tr class='mo_ldap_test_premium_features'>
						<td class='mo_ldap_test_premium_features'>Name</td>
						<td class='mo_ldap_test_premium_features'>".$name."</td>
						</tr>
						<tr class='mo_ldap_test_premium_features'>
						<td class='mo_ldap_test_premium_features'>Email</td>
						<td class='mo_ldap_test_premium_features'>".$email."</td>
						</tr>
						</table><br>";
					}
				echo "<strong class='mo_boot_my-4' >".Text::_('COM_MINIORANGE_TEST_ATTRIBUTE_MAPPING_NOTE')."</strong>
				<br><br>
				<div class='mo_boot_my-4' style='color: #black;	background-color: #d8e7f0; text-align:center; border:1px solid #9aa0db;'>
					<h4>".Text::_('COM_MINIORANGE_TEST_ATTRIBUTE_MAPPING_USER_PROFILE_ATTRIBUTE')."</h4>
				</div>		
				<p>".Text::_('COM_MINIORANGE_TEST_ATTRIBUTE_MAPPING_USER_PROFILE_ATTRIBUTE_DETAILS')."</p>
				<table style='width:80%;margin:auto;text-align:center'>
					<tr style='text-align:center;'>
						<th style='font-weight:bold;border:2px solid #949090;padding:2%;background-color:#d5d3cd'>".Text::_('COM_MINIORANGE_TEST_ATTRIBUTE_MAPPING_USER_PROFILE_ATTRIBUTE_NAME')."</th>
						<th style='font-weight:bold;padding:2%;border:2px solid #949090; word-wrap:break-word;background-color:#d5d3cd'>".Text::_('COM_MINIORANGE_TEST_ATTRIBUTE_MAPPING_USER_PROFILE_ATTRIBUTE_VALUE')."</th>
					</tr>";
					if(isset($user_profile_attributes[0]['attr_name'] )){
						$profile_value1=isset($mo_ldap_user_attributes[0][$user_profile_attributes[0]['attr_value']][0]) ? $mo_ldap_user_attributes[0][$user_profile_attributes[0]['attr_value']][0] : Text::_('COM_MINIORANGE_TEST_ATTRIBUTE_MAPPING_RECHECK_VALUE');
						echo"<tr class='mo_ldap_test_premium_features'>
						<td class='mo_ldap_test_premium_features'>".$user_profile_attributes[0]['attr_name'] ."</td>
						<td class='mo_ldap_test_premium_features'>".$profile_value1."</td>
						</tr>";
						if(isset($user_profile_attributes[1]['attr_name'] )){
							$profile_value2=isset($mo_ldap_user_attributes[0][$user_profile_attributes[1]['attr_value']][0]) ? $mo_ldap_user_attributes[0][$user_profile_attributes[1]['attr_value']][0] : Text::_('COM_MINIORANGE_TEST_ATTRIBUTE_MAPPING_RECHECK_VALUE');
							echo"<tr class='mo_ldap_test_premium_features'>
							<td class='mo_ldap_test_premium_features'>".$user_profile_attributes[1]['attr_name'] ."</td>
							<td class='mo_ldap_test_premium_features'>".$profile_value2."</td>
							</tr>";
						}
					}
					else{
						echo"<tr class='mo_ldap_test_premium_features'>
							<td class='mo_ldap_test_premium_features'>".Text::_('COM_MINIORANGE_TEST_ATTRIBUTE_MAPPING_NOT_CONFIGURED')."</td>
							<td class='mo_ldap_test_premium_features'>".Text::_('COM_MINIORANGE_TEST_ATTRIBUTE_MAPPING_NOT_CONFIGURED')."</td>
							</tr>";
						}
					echo"
				</table><br>
				<div class='mo_boot_my-4' style='color: black;background-color: #d8e7f0; text-align:center; border:1px solid #9aa0db;'>
					<h4>".Text::_('COM_MINIORANGE_TEST_ATTRIBUTE_MAPPING_USER_FIELD_ATTRIBUTE')."</h4>
				</div>		
				<p>".Text::_('COM_MINIORANGE_TEST_ATTRIBUTE_MAPPING_USER_FIELD_ATTRIBUTE_DETAILS')."</p>
				<table style='width:80%;margin:auto;text-align:center'>
					<tr style='text-align:center;'>
						<th style='font-weight:bold;border:2px solid #949090;padding:2%;background-color:#d5d3cd'>".Text::_('COM_MINIORANGE_TEST_ATTRIBUTE_MAPPING_USER_FIELD_ATTRIBUTE_NAME')."</th>
						<th style='font-weight:bold;padding:2%;border:2px solid #949090; word-wrap:break-word;background-color:#d5d3cd'>".Text::_('COM_MINIORANGE_TEST_ATTRIBUTE_MAPPING_USER_FIELD_ATTRIBUTE_VALUE')."</th>
					</tr>";
				

					if(isset($user_field_attributes[0]['attr_name'] )){
						$field_value1=isset($mo_ldap_user_attributes[0][$user_field_attributes[0]['attr_value']][0]) ? $mo_ldap_user_attributes[0][$user_field_attributes[0]['attr_value']][0] : Text::_('COM_MINIORANGE_TEST_ATTRIBUTE_MAPPING_RECHECK_VALUE');
						echo"<tr class='mo_ldap_test_premium_features'>
						<td class='mo_ldap_test_premium_features'>".$user_field_attributes[0]['attr_name'] ."</td>
						<td class='mo_ldap_test_premium_features'>".$field_value1."</td>
						</tr>";
						if(isset($user_field_attributes[1]['attr_name'] )){
							$field_value2=isset($mo_ldap_user_attributes[0][$user_field_attributes[1]['attr_value']][0]) ?$mo_ldap_user_attributes[0][$user_field_attributes[1]['attr_value']][0]: Text::_('COM_MINIORANGE_TEST_ATTRIBUTE_MAPPING_RECHECK_VALUE');
							echo"<tr class='mo_ldap_test_premium_features'>
							<td class='mo_ldap_test_premium_features'>".$user_field_attributes[1]['attr_name'] ."</td>
							<td class='mo_ldap_test_premium_features'>".$field_value2."</td>
							</tr>";
						}
					}
					else{
						echo"<tr class='mo_ldap_test_premium_features'>
							<td class='mo_ldap_test_premium_features'>".Text::_('COM_MINIORANGE_TEST_ATTRIBUTE_MAPPING_NOT_CONFIGURED')."</td>
							<td class='mo_ldap_test_premium_features'>".Text::_('COM_MINIORANGE_TEST_ATTRIBUTE_MAPPING_NOT_CONFIGURED')."</td>
							</tr>";
					}
				echo"</table>
				<br><br>";

			$mo_ldap_default_role=$moLdapServerDetails->getMappingValueDefault();
			$mo_ldap_role_mapping_key_value=$moLdapServerDetails->getRoleMappingKeyValue();
			$mo_ldap_role_mapping_groupvalue=$moLdapServerDetails->getRoleMappingGroupValue();


			$mo_ldap_user_group_defined='group_not_found';
			$group_list=array();
			$mo_ldap_user_group_value=array();
			$mo_ldap_user_belongs_to_group=array();
		

			$i=1;
			if(gettype($mo_ldap_role_mapping_key_value)=='object'){
				foreach ($mo_ldap_role_mapping_key_value as $keys) {
					if (!empty($mo_ldap_user_attributes[0]['memberof']))
						if (in_array($keys, $mo_ldap_user_attributes[0]['memberof'])) {
							$mo_ldap_user_group_defined='group_found';
							array_push($mo_ldap_user_group_value, $mo_ldap_role_mapping_groupvalue[$i]);
					}
					$i++;		
				}
			}	
			
			if($mo_ldap_user_group_defined=='group_not_found'){
				array_push($mo_ldap_user_group_value,$mo_ldap_default_role);
			}

			for($j=0;$j<count($mo_ldap_user_group_value); $j++){
			switch($mo_ldap_user_group_value[$j]){
				case "2": $mo_ldap_user_belongs_to_group[$j]="Registered";
						array_push($group_list, $mo_ldap_user_belongs_to_group[$j]);
						break;
				case "3": $mo_ldap_user_belongs_to_group[$j]="Author";
						array_push($group_list, $mo_ldap_user_belongs_to_group[$j]);
						break;
				case "4": $mo_ldap_user_belongs_to_group[$j]="Editor";
						array_push($group_list, $mo_ldap_user_belongs_to_group[$j]);
						break;
				case "5": $mo_ldap_user_belongs_to_group[$j]="Publisher";
						array_push($group_list, $mo_ldap_user_belongs_to_group[$j]);
						break;
				case "6": $mo_ldap_user_belongs_to_group[$j]="Manager";
						array_push($group_list, $mo_ldap_user_belongs_to_group[$j]);
						break;
				case "7": $mo_ldap_user_belongs_to_group[$j]="Administrator";
						array_push($group_list, $mo_ldap_user_belongs_to_group[$j]);
						break;
			}}
			echo "<div style='color: black;background-color: #d8e7f0; text-align:center; border:1px solid #9aa0db;'><h3>".Text::_('COM_MINIORANGE_TEST_GROUP_MAPPING_USER_GROUP_MAPPING_DETAILS')."</h3></div>
				<div class='mo_boot_mx-4'>
					<p><strong>".Text::_('COM_MINIORANGE_TEST_GROUP_MAPPING_SUCCESSFULLY_AUTHENTICATED')." <span style='color:red'>".$username."</span></strong></p>
					<br>".Text::_('COM_MINIORANGE_TEST_GROUP_MAPPING_DETAILS')."
				

				<table style='width:80%;margin:auto;text-align:center'>
					<tr style='text-align:center;'>
						<th style='font-weight:bold;border:2px solid #949090;padding:2%;background-color:#d5d3cd'>".Text::_('COM_MINIORANGE_TEST_GROUP_MAPPING_JOOMLA_SITE_ROLES')."</th>
					</tr>";
					if($group_list){
					for($i=0;$i<count($group_list);$i++){
					
						echo"<tr class='mo_ldap_test_premium_features'>
						<td class='mo_ldap_test_premium_features'>".$group_list[$i]."</td>
						</tr>";
					}}
					else{
						echo"<tr class='mo_ldap_test_premium_features'>
						<td class='mo_ldap_test_premium_features'>".Text::_('COM_MINIORANGE_TEST_GROUP_MAPPING_NOT_CONFIGURED')."</td>
						</tr>";
					}
				echo"</table></div>
			</div>";
			exit;
		}
		else {
			?><div class="mo_ldap_test_unsuccessful"><?php echo Text::_('COM_MINIORANGE_TEST_UNSUCCESSFUL');?></div>
			
			<div class="mo_ldap_test_unsuccessful_message">
			<?php echo Text::_('COM_MINIORANGE_LDAP_NOT_RESPONDING');?>
			</div><?php
			exit;
		}	

	}	
}

function testConfigurations()
{

	$validatefilter=InputFilter::getInstance();
	$app = Factory::getApplication();
	$user = $app->getIdentity();

	//CHECKING THE USER PERMISSIONS
	if ($user->authorise('core.edit', 'com_miniorange_dirsync')){

		$result=MoLdapUtility::moLdapFetchData('#__miniorange_dirsync_config',array('id'=>'1'),'loadAssoc');
		if (empty($result['search_base']) || empty($result['search_filter'])) {

			$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration', Text::_('COM_MINIORANGE_FILL_SEARCH_BASE_SEARCH_FILTER'), 'error');
			return;
		}

		$app  = Factory::getApplication();
		$post = $app->input->post->getArray();
		$username=isset($post['test_username']) ? $validatefilter->clean($post['test_username'], 'string') : '';
		$password=isset($post['test_password']) ? $post['test_password'] 									 : '';

        if (empty($username)) {
            if (!empty($result['ldap_test_username'])) {
                $username = $validatefilter->clean($result['ldap_test_username'], 'string');
            }
        }
	
		if (empty($username) || empty($password)) {

			$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration', Text::_('COM_MINIORANGE_USERNAME_PASSWORD_REQUIRED'), 'error');
			return;
		}

		//SAVE USERNAME
		$database_name='#__miniorange_dirsync_config';
		$updatefieldsarray=array(
			'ldap_test_username'=> $username,
		);
		MoLdapUtility::moLdapUpdateData($database_name, $updatefieldsarray,array('id'=>'1'));
		
		$auth_response=MoLdapConfig::mo_ldap_authenticate_user(trim($username), $password);

		if ($auth_response->statusMessage=="SUCCESS") {
            $link = Route::_('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=attributerolemapping');
            $this->setRedirect(
                'index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration',
                Text::sprintf('COM_MINIORANGE_SUCCESSFUL_CONNECTED', $link)
            );
        }
		
		else if ($auth_response->statusMessage=="USER_NOT_EXIST") {

			$search_base=MoLdapUtility::mo_ldap_decrypt($result['search_base']);
			$search_filter=$result['search_filter'];
			$message=Text::_('COM_MINIORANGE_CANNOT_FIND_USER1').htmlspecialchars($username).Text::_('COM_MINIORANGE_CANNOT_FIND_USER2').htmlspecialchars($search_base).Text::_('COM_MINIORANGE_CANNOT_FIND_USER3').htmlspecialchars($search_filter).Text::_('COM_MINIORANGE_CANNOT_FIND_USER4').htmlspecialchars($search_base).Text::_('COM_MINIORANGE_CANNOT_FIND_USER5');
			$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration', $message, 'error');
		} 
		
		else if ($auth_response->statusMessage=="LDAP_NOT_RESPONDING") {

			$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration', Text::_('COM_MINIORANGE_LDAP_NOT_RESPONDING_CHECK_CONFIG'), 'error');
		} 
		
		else if ($auth_response->statusMessage=="USER_PASSWORD_DOESNTMATCH") {

			$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration', Text::_('COM_MINIORANGE_PASSWORD_MISMATCH'), 'error');
		} 
		
		else {

			$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration', Text::_('COM_MINIORANGE_LDAP_NOT_RESPONDING'), 'error');
		}
	}else{
		$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration',Text::_('COM_MINIORANGE_MULTI_NO_PERMISSION_TO_SAVE'),'error');
	}
}

function moLdappsbsearchbases()
{
	$app = Factory::getApplication();
	$user = $app->getIdentity();

	//CHECKING THE USER PERMISSIONS
	if ($user->authorise('core.edit', 'com_miniorange_dirsync')){

		$result=MoLdapUtility::moLdapFetchData('#__miniorange_dirsync_config',array('id'=>'1'),'loadAssoc');
		$previous_search_bases=isset($result['search_base']) 	? MoLdapUtility::mo_ldap_decrypt($result['search_base']) 	 : "" ;
		$server_name=isset($result['ldap_server_url']) ? MoLdapUtility::mo_ldap_decrypt($result['ldap_server_url']) : "";

		if (empty($server_name)) {

			?><div style="color: white;	background-color: #e06d6d; padding:2%;margin-bottom:20px;text-align:center; border:1px solid #AEDB9A; font-size:18pt;"><?php echo Text::_('COM_MINIORANGE_TEST_UNSUCCESSFUL');?></div>
			
			<div style="margin-left:10px;padding:10px;border: 1px solid black;">
			<?php echo Text::_('COM_MINIORANGE_ERROR_RETRIEVING_SEARCH_BASE');?>
			</div><?php
			exit;
		}
		$data=MoLdapConfig::mo_ldap_psbsearchbases();
      ?>
      <?php
      if ($data) {
        echo '<script>
			jQuery(document).ready(function($) {
				$(".sidebar-wrapper").hide();
				$(".header").hide();
			});
			</script>';
        echo '<div class="alert alert-success text-center mb-3">';
        echo Text::_('COM_MINIORANGE_LIST_OF_SEARCH_BASES');
        echo '</div>';
        
        echo '<span><strong class="text-warning-emphasis">';
        echo Text::_('COM_MINIORANGE_SELECT_SEARCH_BASE');
        echo '</strong></span><br><br>';
        
        echo '<div>';
        ?>
          <form name="sbase" method="post" action="<?php echo Route::_('index.php?option=com_miniorange_dirsync&view=accountsetup&task=accountsetup.moLdapUpdatesearchbase'); ?>" id="updatesearchbase_form" class="form-horizontal">

              <input type="hidden" id="search_base_list_id" class="search_base_list_id" value='<?=json_encode($data);?>'>
              <div class="form-group d-flex align-items-center mb-3">
                  <label for="limit" class="mb-0 me-2"><?php echo Text::_('COM_MINIORANGE_DIRSYNC_SELECT_SEARCH_BASES_PER_PAGE'); ?></label>
                  <select class="form-select me-3" name="limit" id="limit" onchange="updateSearchBaseLimit()" style="width: 15%">
                      <?php 
                      $current_limit = $app->input->post->getInt('limit', 10);
                      ?>
                      <option value="10" <?php echo ($current_limit == 10) ? 'selected' : ''; ?>>10</option>
                      <option value="25" <?php echo ($current_limit == 25) ? 'selected' : ''; ?>>25</option>
                      <option value="50" <?php echo ($current_limit == 50) ? 'selected' : ''; ?>>50</option>
                      <option value="100" <?php echo ($current_limit == 100) ? 'selected' : ''; ?>>100</option>
                  </select>

                  <label for="search" class="mb-0 me-2"><?php echo Text::_('COM_MINIORANGE_DIRSYNC_SEARCH'); ?></label>
                  <input type="text" id="search" onkeyup="filterSearchBases()" placeholder="Search for search bases..." class="form-control" style="width:40% ;">
              </div>


              <div id="search_base_results">
                <?php
                $limit = $app->input->post->getInt('limit', 10);
                $total = count($data);
                $total_pages = ceil($total / $limit);
                $current_page = $app->input->get('page', 1, 'int');
                $start_index = ($current_page - 1) * $limit;
                
                $html_output='';
                if(in_array($previous_search_bases,$data)) {
                  if ($previous_search_bases !== false) {
                      // Remove the previous search base from the data array
                      $data = array_values(array_diff($data, [$previous_search_bases]));
                    $html_output .= "<div class='inputGroup list-group-item border rounded p-2 mb-2'>
                            <input type='radio' name='select_ldap_search_bases' id='select_ldap_search_previous' class='form-check-input'value='{$previous_search_bases}' checked required><label for='select_ldap_search_previous' class='form-check-label ms-2'>" . htmlspecialchars($previous_search_bases) . "</label><br>
                          </div>";
                    unset($previous_search_bases);
                  }
                }
                for ($i = $start_index; $i < min($start_index + $limit, count($data)); $i++) {
                  $html_output .= "<div class='inputGroup list-group-item border rounded p-2 mb-2'>
                            <input type='radio' name='select_ldap_search_bases' id='select_ldap_search_{$i}' class='form-check-input' value='{$data[$i]}' required><label for='select_ldap_search_{$i}' class='form-check-label ms-2'>" . htmlspecialchars($data[$i]) ."</label><br>
                          </div>";
                }
                echo $html_output;
                
                ?>
              </div>
            <?php
            $base_url = Route::_('index.php?option=com_miniorange_dirsync&view=accountsetup&task=accountsetup.moLdappsbsearchbases');
            
            echo '<div class="mo_boot_pagination" >';
            
            if ($current_page > 1) {
              echo '<a href="' . $base_url . '&page=' . ($current_page - 1) . '" class="btn btn-secondary" style="margin-right: 5px;">
        <span class="fas fa-arrow-left" aria-hidden="true"></span> ' . Text::_('COM_MINIORANGE_DIRSYNC_PREVIOUS') . '</a>';
            }
            
            for ($page = 1; $page <= $total_pages; $page++) {
              $activeClass = ($page == $current_page) ? 'btn btn-primary' : 'btn btn-secondary';
              $style = ($page == $current_page) ? 'margin: 0 5px; padding: 6px 12px;' : 'margin: 0 5px;';
              $pageLink = ($page == $current_page)
                ? "<span class=\"$activeClass\" style=\"$style\">$page</span>"
                : "<a href=\"$base_url&page=$page\" class=\"$activeClass\" style=\"$style\">$page</a>";
              echo $pageLink;
            }
            
            if ($current_page < $total_pages) {
              echo '<a href="' . $base_url . '&page=' . ($current_page + 1) . '" class="btn btn-secondary" style="margin-left: 5px;">' . Text::_('COM_MINIORANGE_DIRSYNC_NEXT') . '<span class="fas fa-arrow-right" aria-hidden="true"></span></a>';
            }
            echo '</div>';
            ?>

              <div style="margin:3%;display:block;text-align:center;">
                  <input type="submit" id="submitbase" value="<?php echo Text::_('COM_MINIORANGE_SUBMIT'); ?>" name="submitbase"
                         class="btn btn-success" onclick="mo_ldap_submit_search_base()">
                  <input type="button" id="searchbase" class="button-cancel btn btn-danger" value="<?php echo Text::_('COM_MINIORANGE_CLOSE'); ?>" onclick="self.close();"
                         style="cursor: pointer;">
              </div>

          </form>

          </div>
        <?php
        
		 }
		}
}
	
function moLdapUpdatesearchbase()
{
	$app = Factory::getApplication();
	$user = $app->getIdentity();

	//CHECKING THE USER PERMISSIONS
	if ($user->authorise('core.edit', 'com_miniorange_dirsync')){

		$app  = Factory::getApplication();
		$post = $app->input->post->getArray();

		$search_base_string=$post['select_ldap_search_bases'];
		$search_base_string=MoLdapUtility::mo_ldap_encrypt($search_base_string);

		$database_name='#__miniorange_dirsync_config';
		$updatefieldsarray=array(
			'search_base'=> $search_base_string,

		);

		MoLdapUtility::moLdapUpdateData($database_name, $updatefieldsarray,array('id'=>'1'));
		$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration', Text::_('COM_MINIORANGE_SAVED_SEARCH_BASE'));
	}else{
		$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration',Text::_('COM_MINIORANGE_MULTI_NO_PERMISSION_TO_SAVE'),'error');
	}
}
	

	function moLdapContactUs()
	{
		$app  = Factory::getApplication();
		$post = $app->input->post->getArray();
		$query_email=isset($post['mo_ldap_query_email'])? $post['mo_ldap_query_email'] : '';
		$query=isset($post['mo_ldap_query']) ? $post['mo_ldap_query'] : '';
		$query_type=isset($post['mo_ldap_setup_call_issue'])		? $post['mo_ldap_setup_call_issue']		: '';
		$query_withconfig=isset($post['mo_ldap_query_withconfig']) ? $post['mo_ldap_query_withconfig'] : '';
		$attributes=MoLdapUtility::moLdapFetchData('#__miniorange_dirsync_config',array('id'=>'1'),'loadAssoc');
		if ($query_withconfig !=1) {

			$attributes['search_filter']='';
			$attributes['username']='';
			$attributes['email']='';
		}
	
		if (MoLdapUtility::mo_ldap_check_empty_or_null($query_email) || MoLdapUtility::mo_ldap_check_empty_or_null($query)) {
			$message=Text::_('COM_MINIORANGE_QUERY_WITH_EMAIL');
			$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=mo_ldap_trial_demo', $message, 'error');
			return;
		} else {
			$query=$post['mo_ldap_query'];
			$email=$post['mo_ldap_query_email'];
			$phone=$post['mo_ldap_query_phone'];
			$contact_us=new MoLdapCustomer();
			$submited=json_decode($contact_us->mo_ldap_submit_contact_us($email, $phone, $query, $attributes, $query_type),true);
            if(json_last_error()==JSON_ERROR_NONE) {
                if(is_array($submited) && array_key_exists('status', $submited) && $submited['status']=='ERROR'){
                    $this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=mo_ldap_trial_demo', $submited['message'],'error');
                }else{
                    if ( $submited==false ) {
						$message=Text::_('COM_MINIORANGE_QUERY_NOT_SUBMITTED');
                        $this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=mo_ldap_trial_demo', $message,'error');
                    } else {
						$message=Text::_('COM_MINIORANGE_QUERY_SENT');
                        $this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=mo_ldap_trial_demo', $message);
                    }
                }
            }else{

				$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=mo_ldap_trial_demo', $submitted, 'error');
			}
		}
	}

	function moLdapExport(){

		$ldap_server_details=MoLdapUtility::moLdapFetchData('#__miniorange_dirsync_config',array('id'=>'1'),'loadAssoc');
		$ldap_server_url=$ldap_server_details['ldap_server_url'];
		$username=$ldap_server_details['username'];
		if($ldap_server_url=='' && $username=='')
		{
			$message=Text::_('COM_MINIORANGE_FILL_ATTRIBUTE_MAPPING_SERVER_URL');
			$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration', $message, 'error');
			return;
		}

		foreach($ldap_server_details as $key=> $value)
		{
			if($key=='ldap_server_url' || $key=='service_account_dn' || $key=='service_account_password' || $key=='search_base')
			{
				$mo_ldap_decrypted_value=MoLdapUtility::mo_ldap_decrypt($value);
				$ldap_server_details[$key]=$mo_ldap_decrypted_value;
			}
		}

		$ldap_group_mapping=MoLdapUtility::moLdapFetchData('#__miniorange_ldap_role_mapping',array('id'=>'1'),'loadAssoc');
		$ntlm_configuration=MoLdapUtility::moLdapFetchData('#__miniorange_ntlm',array('id'=>'1'),'loadAssoc')	;
		$plugin_configuration=array();
		array_unshift($ldap_server_details, 'miniorange_dirsync_config');
		array_unshift($ldap_group_mapping, 'miniorange_ldap_role_mapping');
		array_unshift($ntlm_configuration, 'miniorange_ntlm');
		array_push($plugin_configuration, $ldap_server_details, $ldap_group_mapping, $ntlm_configuration);
		
		$filecontentd=json_encode($plugin_configuration, JSON_PRETTY_PRINT);
		
		header('Content-Disposition: attachment; filename=ldap-server.json'); 
		header('Content-Type: application/json'); 
		echo $filecontentd;
	
		
		$message=Text::_('COM_MINIORANGE_EXPORT_SUCCESSFUL');
		$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration', $message);
		exit;
	}	
	
    function mo_ldap_requestfordemo()
    {
		$app  = Factory::getApplication();
		$post = $app->input->post->getArray();
		
        if ((!isset($post['email'])) || (!isset($post['plan'])) || (!isset($post['description']))) {
            $this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=mo_ldap_trial_demo');
            return;
        }
        $email=$post['email'];
        $plan=$post['plan'];
		$add_on=$post['add_on'];
        $description=trim($post['description']);
        $demo='Demo';
	
        if (!isset($plan) || empty($description)) {
            $this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=mo_ldap_trial_demo', Text::_('COM_MINIORANGE_FILL_PLAN_DETAILS_FOR_DEMO'), 'error');
            return;
        }

        $customer=new MoLdapCustomer();
        $response=json_decode($customer->mo_ldap_request_for_demo($email, $plan, $demo, $description, $add_on));

        if ($response->status !='ERROR'){
            $this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=mo_ldap_trial_demo', Text::_('COM_MINIORANGE_DEMO_REQUEST_RECIEVED_SUCCESSFULLY'));

		 } else {
            $this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=mo_ldap_trial_demo', Text::_('COM_MINIORANGE_CONTACT_US_AGAIN'), 'error');
            return;
        }

    }

	function callContactUs(){
		
		$app  = Factory::getApplication();
		$post = $app->input->post->getArray();
	if (count($post)==0) {
		$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=mo_ldap_trial_demo');
		return;
	}
	
	$query_email=isset($post['mo_ldap_setup_call_email']) 		? $post['mo_ldap_setup_call_email']		: '';
	$query=isset($post['mo_ldap_setup_call_issue'])		? $post['mo_ldap_setup_call_issue']		: '';
	$description=isset($post['mo_ldap_setup_call_desc'])		? $post['mo_ldap_setup_call_desc']		: '';
	$callDate=isset($post['mo_ldap_setup_call_date'])		? $post['mo_ldap_setup_call_date']		: '';
	$timeZone=isset($post['mo_ldap_setup_call_timezone'])	? $post['mo_ldap_setup_call_timezone']	: '';
	$query_withconfig=isset($post['mo_ldap_query_withconfig']) 		? $post['mo_ldap_query_withconfig'] 	: '';
	$attributes=MoLdapUtility::moLdapFetchData('#__miniorange_dirsync_config',array('id'=>'1'),'loadAssoc');
	if ($query_withconfig !=1) {

		$attributes['search_filter']='';
		$attributes['username']='';
		$attributes['email']='';
	}
	if (MoLdapUtility::mo_ldap_check_empty_or_null($timeZone) || MoLdapUtility::mo_ldap_check_empty_or_null($callDate) || MoLdapUtility::mo_ldap_check_empty_or_null($query_email) ||  MoLdapUtility::mo_ldap_check_empty_or_null($description)) {
		$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=mo_ldap_trial_demo', Text::_('COM_MINIORANGE_SUPPORT_FILL_ALL_FIELDS'), 'error');
		return;
	} else {
		$contact_us=new MoLdapCustomer();
		$submited=json_decode($contact_us->mo_ldap_request_for_setupCall($query_email, $query, $description, $callDate, $timeZone, $attributes), true);
		if (json_last_error()==JSON_ERROR_NONE) {
			if (is_array($submited) && array_key_exists('status', $submited) && $submited['status']=='ERROR') {
				$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=mo_ldap_trial_demo', $submited['message'], 'error');
			} else {
				if ($submited==false) {
					$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=mo_ldap_trial_demo', Text::_('COM_MINIORANGE_QUERY_NOT_SUBMITTED'), 'error');
				} else {
					$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=mo_ldap_trial_demo', Text::_('COM_MINIORANGE_QUERY_SENT'));
				}
			}
		}

	}
	}
	
	public function exportConfiguration()
    {
        // Define single or multiple table names here
        $tableNames = [
            '#__miniorange_ldap_customer',
            '#__miniorange_dirsync_config',
			'#__miniorange_ntlm',
			'#__miniorange_ldap_role_mapping',
        ];

        JLoader::register('MoLdapUtility', JPATH_COMPONENT . '/helpers/mo_ldap_utility.php');

        MoLDAPUtility::exportData($tableNames);
    }
}