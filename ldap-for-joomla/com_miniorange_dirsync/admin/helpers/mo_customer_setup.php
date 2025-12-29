<?php
/**
*
* This library is miniOrange Authentication Service.
* Contains Request Calls to Customer service.
*
* @author      miniOrange Security Software Pvt. Ltd.
* @copyright   Copyright (C) 2015 miniOrange (https://www.miniorange.com)
* @license     GNU General Public License version 3; see LICENSE.txt
* @contact     info@xecurify.com
**/

defined( '_JEXEC' ) or die( 'Restricted access' );
use Joomla\CMS\Factory;
use Joomla\CMS\Version;

require_once JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_miniorange_dirsync'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'mo_ldap_utility.php';
class MoLdapCustomer{
	
	public $email;

	public $phone;
	public $customerKey;
	public $transactionId;

	/*
	** Initial values are hardcoded to support the miniOrange framework to generate OTP for email.
	** We need the default value for creating the OTP the first time,
	** As we don't have the Default keys available before registering the user to our server.
	** This default values are only required for sending an One Time Passcode at the user provided email address.
	*/
	
	//auth
	private $defaultCustomerKey="16555";
	private $defaultApiKey="fFd2XcvTGDemZvbw1bcUesNJWEqKbbUq";
	
	
	function mo_ldap_get_customer_key($email,$password) {
		if(!MoLdapUtility::mo_ldap_is_curl_installed()) {
			return json_encode(array("apiKey"=>'CURL_ERROR','token'=>'<a href="http://php.net/manual/en/curl.installation.php">PHP cURL extension</a> is not installed or disabled.'));
		}
		
		$hostname=MoLdapUtility::mo_ldap_get_hostname();
		$url=$hostname. "/moas/rest/customer/key";
		$fields=array(
			'email'=> $email,
			'password'=> $password
		);
		$field_string=json_encode($fields);
		$http_header_array=array( 'Content-Type: application/json', 'charset: UTF-8', 'Authorization: Basic' );
		return self::mo_post_curl($url,$field_string,$http_header_array);
	}

    public static function submit_uninstall_feedback_form($email, $phone, $query,$cause)
    {
            // Check if cURL is installed
    		if(!MoLdapUtility::mo_ldap_is_curl_installed()) {
                return json_encode(array("status"=>'ERROR','message'=>'<a href="http://php.net/manual/en/curl.installation.php">PHP CURL extension</a> is not installed or disabled.'));
    		}
            $url='https://login.xecurify.com/moas/api/notify/send';
            $customerKey="16555";
    		$apiKey="fFd2XcvTGDemZvbw1bcUesNJWEqKbbUq";

            $currentTimeInMillis=round(microtime(true) * 1000);
            $stringToHash=$customerKey .  number_format($currentTimeInMillis, 0, '', '') . $apiKey;
            $hashValue=hash("sha512", $stringToHash);
            $customerKeyHeader="Customer-Key: " . $customerKey;
            $timestampHeader="Timestamp: " .  number_format($currentTimeInMillis, 0, '', '');
            $authorizationHeader="Authorization: " . $hashValue;

            $fromEmail = $email;
            $phpVersion = phpversion();
            $dVar=new JConfig();
            $check_email = $dVar->mailfrom;
            $jCmsVersion =  MoLdapUtility::getJoomlaCmsVersion();
            $moPluginVersion =  MoLdapUtility::mo_ldap_get_plugin_version();
            $os_version    = MoLdapUtility::mo_ldap_get_operating_system();
            $pluginName    = 'LDAP Free Plugin';
            $admin_email   = !empty($email)?$email:$check_email;

            $query1 = '['.$pluginName.' | Plugin '.$moPluginVersion.' | PHP ' . $phpVersion.' | Joomla ' . $jCmsVersion.' | OS ' . $os_version.'] ';

            $ccEmail = 'joomlasupport@xecurify.com';
            $bccEmail = 'joomlasupport@xecurify.com';
            $content = '<div>Hello, <br><br>'
                    . '<strong>Company: </strong><a href="' . $_SERVER['SERVER_NAME'] . '" target="_blank">' . $_SERVER['SERVER_NAME'] . '</a><br><br>'
                    . '<strong>Phone Number: </strong>' . $phone . '<br><br>'
                    . '<strong>Admin Email: </strong><a href="mailto:' .$admin_email . '" target="_blank">' . $admin_email . '</a><br><br>'
                    . '<strong>Feedback: </strong>' . $query . '<br><br>'
                    . '<strong>Additional Details: </strong>' . $cause . '<br><br>'
                    . '<strong>System Information: </strong>' . $query1
                    . '</div>';

            $subject = "miniOrange Joomla LDAP Free Plugin Feedback";

            $fields = array(
                'customerKey' => $customerKey,
                'sendEmail' => true,
                'email' => array(
                    'customerKey' 	=> $customerKey,
                    'fromEmail' 	=> $fromEmail,
                    'bccEmail' 		=> $bccEmail,
                    'fromName' 		=> 'miniOrange',
                    'toEmail' 		=> $ccEmail,
                    'toName' 		=> $bccEmail,
                    'subject' 		=> $subject,
                    'content' 		=> $content
                ),
            );
            $field_string = json_encode($fields);
            $http_header_array=array( 'Content-Type: application/json',$customerKeyHeader, $timestampHeader, $authorizationHeader);

            return self::mo_post_curl($url, $field_string, $http_header_array);
        }

	public static function mo_ldap_submit_feedback_form($query, $email, $isDownloadTracking = false)
	{
		// Check if cURL is installed
		if(!MoLdapUtility::mo_ldap_is_curl_installed()) {
            return json_encode(array("status"=>'ERROR','message'=>'<a href="http://php.net/manual/en/curl.installation.php">PHP CURL extension</a> is not installed or disabled.'));
		}
        $url='https://login.xecurify.com/moas/api/notify/send';       
        $customerKey="16555";
		$apiKey="fFd2XcvTGDemZvbw1bcUesNJWEqKbbUq";

        $currentTimeInMillis=round(microtime(true) * 1000);
        $stringToHash=$customerKey .  number_format($currentTimeInMillis, 0, '', '') . $apiKey;
        $hashValue=hash("sha512", $stringToHash);
        $customerKeyHeader="Customer-Key: " . $customerKey;
        $timestampHeader="Timestamp: " .  number_format($currentTimeInMillis, 0, '', '');
        $authorizationHeader="Authorization: " . $hashValue;
        $fromEmail=$email;
        $toEmails = 'joomlasupport@xecurify.com';
        $toNames = 'joomlasupport@xecurify.com';
        $app = Factory::getApplication();
        $currentUserEmail = $app->getIdentity();
        $adminEmail=$currentUserEmail->email;
        $phpVersion = phpversion();
        $jVersion = new Version;
        $jCmsVersion = $jVersion->getShortVersion();
        $moPluginVersion = MoLdapUtility::mo_ldap_get_plugin_version();
        $moSystemOS = MoLdapUtility::mo_ldap_get_operating_system();

        $pluginInfo = '[MiniOrange Joomla LDAP Free | ' . $phpVersion . ' | ' . $jCmsVersion . ' | ' . $moPluginVersion . ' | ' . $moSystemOS . '] ';
        if ($isDownloadTracking) {
            $toEmails = 'nutan.barad@xecurify.com';
            $toNames = 'harshvardhan.soni@xecurify.com';
            $subject = "Installation of Joomla LDAP[Free]";

            $company = $_SERVER['SERVER_NAME'];
            $content = 'Plugin is installed by this email: <strong>' . $email . '</strong><br><br>' . 'Company: <a href="' . $company . '" target="_blank">' . $company . '</a><br><br>' . '<strong>Plugin Info:</strong> ' . $pluginInfo;
        } else {
            $subject = "Feedback for miniOrange Joomla LDAP Plugin";
            $pluginName = "MiniOrange Joomla LDAP [Free]";
            $feedbackReason = $query; // Store the feedback reason before using $query variable
            
            $db = Factory::getDbo();
            
            $configuration_summary = "<br><br><strong>Configuration Summary:</strong><br>";
            try {
                // Fetch configuration
                $dbQuery = $db->getQuery(true)->select('*')->from($db->quoteName('#__miniorange_dirsync_config'))->where($db->quoteName('id') . ' = 1');
                $db->setQuery($dbQuery);
                $config = $db->loadAssoc();

                if ($config) {
                    if (!empty($config['ldap_server_url']) && !empty($config['service_account_dn']) && !empty($config['service_account_password'])) {
                        $configuration_summary .= "✔ Step 1: LDAP Server Configuration completed.<br>";
                    }
                    if (!empty($config['search_base']) && !empty($config['search_filter'])) {
                        $configuration_summary .= "✔ Step 2: Search Base & Filter set.<br>";
                    }
                    if (!empty($config['ldap_test_username'])) {
                        $configuration_summary .= "✔ Step 3: Test Username provided.<br>";
                    }
                    if (!empty($config['username']) && !empty($config['email'])) {
                        $configuration_summary .= "✔ Step 4: Attribute Mapping configured.<br>";
                    }
                } else {
                    $configuration_summary .= "No configuration found in database.<br>";
                }
            } catch (Exception $e) {
                $configuration_summary .= "Error accessing configuration: " . $e->getMessage() . "<br>";
            }

            $content = '<div >Hello, <br><br>
            Company: <a href="'. $_SERVER['SERVER_NAME'] . '" target="_blank">' . $_SERVER['SERVER_NAME'] . '</a><br><br>
            <strong>Admin Email:</strong> <a href="mailto:' . $adminEmail . '" target="_blank">' . $adminEmail . '</a><br><br>
            <b>Plugin Uninstalled: ' . $pluginName . '</b><br><br>
            <b>Reason: ' . $feedbackReason . '</b><br><br>
            <strong>Feedback Email:</strong> ' . $email . '<br><br>
            <strong>Plugin Info:</strong> ' . $pluginInfo . $configuration_summary . '</div>';
        }
        $fields=array(
            'customerKey'=> $customerKey,
            'sendEmail'=> true,
            'email'=> array(
                'customerKey'=> $customerKey,
                'fromEmail'=> $fromEmail,
                'fromName'=> 'miniOrange',
                'toEmail'=> $toEmails,
                'bccEmail'=> $toNames,
                'subject'=> $subject,
                'content'=> $content
            ),
        );
        
		$field_string=json_encode($fields);
		$http_header_array=array( 'Content-Type: application/json',$customerKeyHeader, $timestampHeader, $authorizationHeader);
		
		// Log the request details for debugging

		$response = self::mo_post_curl($url,$field_string,$http_header_array);
        return $response;
	}
	

	function mo_ldap_submit_contact_us( $q_email, $q_phone, $query, $attributes , $query_type) {
		
		if(!MoLdapUtility::mo_ldap_is_curl_installed()) {
            return json_encode(array("status"=>'ERROR','message'=>'<a href="http://php.net/manual/en/curl.installation.php">PHP CURL extension</a> is not installed or disabled.'));
		}
		$url='https://login.xecurify.com/moas/api/notify/send';
        $ch=curl_init($url);
        $customerKey="16555";
        $apiKey="fFd2XcvTGDemZvbw1bcUesNJWEqKbbUq";
	
        $currentTimeInMillis=round(microtime(true) * 1000);
        $stringToHash=$customerKey . number_format($currentTimeInMillis, 0, '', '') . $apiKey;
        $hashValue=hash("sha512", $stringToHash);
        $customerKeyHeader="Customer-Key: " . $customerKey;
        $timestampHeader="Timestamp: " . number_format($currentTimeInMillis, 0, '', '');
        $authorizationHeader="Authorization: " . $hashValue;
        $fromEmail=$q_email;
		$phpVersion=phpversion();
		$jVersion=new Version;
		$jCmsVersion=$jVersion->getShortVersion();
		$moPluginVersion=MoLdapUtility::mo_ldap_get_plugin_version();
		$moSystemOS=MoLdapUtility::mo_ldap_get_operating_system();
        $subject="Query for MiniOrange Joomla LDAP Free - ".$fromEmail;
		$query=$query.'<br><strong>Configuration: </strong><br> <strong>Search filter:</strong>  '.$attributes['search_filter'].'<br> <strong>Username: </strong> '. $attributes['username'].' <br> <strong>Email: </strong>'.$attributes['email'];
	
		$app = Factory::getApplication();
		$currentUserEmail = $app->getIdentity();
        $adminEmail=$currentUserEmail->email;
        $pluginInfo='['.$moPluginVersion.' | PHP ' . $phpVersion.' | System OS '.$moSystemOS.' ] ';
		$query='[MiniOrange Joomla LDAP Free | '.$phpVersion. ' | '.$jCmsVersion.' | '.$moPluginVersion.' | ' . $moSystemOS.'] ' . $query;
        $content='<div >Hello, <br><br>
					<strong>Company</strong> :<a href="'.$_SERVER['SERVER_NAME'].'" target="_blank" >'.$_SERVER['SERVER_NAME'].'</a><br><br>
					<strong>Phone Number</strong> :'.$q_phone.'<br><br>
					<strong>Admin Email : </strong><a href="mailto:'.$adminEmail.'" target="_blank">'.$adminEmail.'</a><br><br>
					<b>Email :<a href="mailto:'.$fromEmail.'" target="_blank">'.$fromEmail.'</a></b><br><br>
					<b>Query Type: </b>'.$query_type.'<br><br>
                    <b>Query</b>: '.$query. '</b></div>';

        $fields=array(
            'customerKey'=> $customerKey,
            'sendEmail'=> true,
            'email'=> array(
                'customerKey'=> $customerKey,
                'fromEmail'=> $fromEmail,
                'fromName'=> 'miniOrange',
                'toEmail'=> 'joomlasupport@xecurify.com',
                'toName'=> 'joomlasupport@xecurify.com',
                'subject'=> $subject,
                'content'=> $content
            ),
        );
        $field_string=json_encode($fields);
		$http_header_array=array( 'Content-Type: application/json', $customerKeyHeader, $timestampHeader, $authorizationHeader);
		return self::mo_post_curl($url,$field_string,$http_header_array);
		
	}
	

	function mo_ldap_request_for_demo($email, $plan,$demo,$description='',$add_on="")
    {
        $url='https://login.xecurify.com/moas/api/notify/send';
        $ch=curl_init($url);
        $customerKey="16555";
        $apiKey="fFd2XcvTGDemZvbw1bcUesNJWEqKbbUq";

        $currentTimeInMillis=round(microtime(true) * 1000);
        $stringToHash=$customerKey . number_format($currentTimeInMillis, 0, '', '') . $apiKey;
        $hashValue=hash("sha512", $stringToHash);
        $customerKeyHeader="Customer-Key: " . $customerKey;
        $timestampHeader="Timestamp: " . number_format($currentTimeInMillis, 0, '', '');
        $authorizationHeader="Authorization: " . $hashValue;
        $fromEmail=$email;
        $subject='MiniOrange Joomla LDAP Request for '.$demo;

        $phpVersion=phpversion();
		$jVersion=new Version;
		$jCmsVersion=$jVersion->getShortVersion();
		$moPluginVersion=MoLdapUtility::mo_ldap_get_plugin_version();
		$moSystemOS=MoLdapUtility::mo_ldap_get_operating_system();

		$app = Factory::getApplication();
		$currentUserEmail = $app->getIdentity();
        $adminEmail=$currentUserEmail->email;
        $pluginInfo='['.$moPluginVersion.' | PHP ' . $phpVersion.' | System OS '.$moSystemOS.' ] ';

        $content='<div >Hello, <br>
                        <br><strong>Company :</strong><a href="' . $_SERVER['SERVER_NAME'] . '" target="_blank" >' . $_SERVER['SERVER_NAME'] . '</a><br><br>
						<strong>Admin Email :</strong><a href="mailto:'.$adminEmail.'"target="_blank">'.$adminEmail.'</a><br><br>
                        <strong>Email :</strong><a href="mailto:' . $fromEmail . '" target="_blank">' . $fromEmail . '</a><br><br>
                        <strong>Plugin Info: </strong>'.$pluginInfo.'<br><br>
                        <strong>'.$demo. ':</strong> ' . $plan . '<br><br>
						<strong>Add on :</strong>'.$add_on.'<br><br>
                        <strong>Description: </strong>' . $description . '</div>';

        $fields=array(
            'customerKey'=> $customerKey,
            'sendEmail'=> true,
            'email'=> array(
                'customerKey'=> $customerKey,
                'fromEmail'=> $fromEmail,
                'fromName'=> 'miniOrange',
                'toEmail'=> 'joomlasupport@xecurify.com',
                'toName'=> 'joomlasupport@xecurify.com',
                'subject'=> $subject,
                'content'=> $content
            ),
        );
        $field_string=json_encode($fields);
		$http_header_array=array( 'Content-Type: application/json', $customerKeyHeader, $timestampHeader, $authorizationHeader);
		return self::mo_post_curl($url,$field_string,$http_header_array);
    }

	public static function mo_post_curl($url, $fields, $http_header_array){
		$ch=curl_init($url);
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt( $ch, CURLOPT_ENCODING, "" );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		
		curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, $http_header_array );
		curl_setopt( $ch, CURLOPT_POST, true);
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $fields);
		
		$proxy_server=MoLdapUtility::moLdapFetchData('#__miniorange_dirsync_config',array('id'=>'1'),'loadAssoc');
		$proxy_server_url=isset($proxy_server['proxy_server_url'])? $proxy_server['proxy_server_url'] : '';
		$proxy_server_port=isset($proxy_server['proxy_server_port']) ? $proxy_server['proxy_server_port']: '';
		$proxy_username=isset($proxy_server['proxy_username']) ? $proxy_server['proxy_username'] : '';
		$proxy_password=isset($proxy_server['proxy_password']) ? $proxy_server['proxy_password']: '';
		$proxy_check=isset($proxy_server['proxy_set']) ? $proxy_server['proxy_set']: '';
		
		if($proxy_check=="yes")
		{
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1); 
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			curl_setopt($ch, CURLOPT_PROXY, $proxy_server_url);
			curl_setopt($ch, CURLOPT_PROXYPORT, $proxy_server_port);  
			curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_username.':'.$proxy_password);  
		}

		$content=curl_exec( $ch );
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      
		 if(curl_errno($ch)){
            $error = array('status' => 'ERROR', 'message'=> curl_error($ch));
		   return json_encode($error);
		}
		
		curl_close($ch);

		return $content;
	}
	
	function mo_ldap_request_for_setupCall($email, $query, $description, $callDate, $timeZone, $attributes)
    {
        $url='https://login.xecurify.com/moas/api/notify/send';
        $customerKey="16555";
        $apiKey="fFd2XcvTGDemZvbw1bcUesNJWEqKbbUq";

        $currentTimeInMillis=round(microtime(true) * 1000);
        $stringToHash=$customerKey . number_format($currentTimeInMillis, 0, '', '') . $apiKey;
        $hashValue=hash("sha512", $stringToHash);
        $customerKeyHeader="Customer-Key: " . $customerKey;
        $timestampHeader="Timestamp: " . number_format($currentTimeInMillis, 0, '', '');
        $authorizationHeader="Authorization: " . $hashValue;
        $fromEmail=$email;

        $subject="MiniOrange Joomla LDAP Free - Screen Share/Call Request";
        $phpVersion=phpversion();
		
		$jVersion=new Version;
		$jCmsVersion=$jVersion->getShortVersion();
        $moPluginVersion=MoLdapUtility::mo_ldap_get_plugin_version();
		$moSystemOS=MoLdapUtility::mo_ldap_get_operating_system();

		$app = Factory::getApplication();
		$currentUserEmail = $app->getIdentity();
        $adminEmail=$currentUserEmail->email;

        $pluginInfo='[ LDAP FREE '.$moPluginVersion.' | PHP ' . $phpVersion.' | System OS '.$moSystemOS.' ] ';
		$query=$query.'<br><strong>Configuration: </strong><br> <strong>Search filter:</strong>  '.$attributes['search_filter'].'<br> <strong>Username: </strong> '. $attributes['username'].' <br> <strong>Email: </strong>'.$attributes['email'];
        $content='<div>Hello, <br><br>
                        <strong>Company :</strong><a href="' . $_SERVER['SERVER_NAME'] . '" target="_blank" >' . $_SERVER['SERVER_NAME'] . '</a><br><br>
                        <strong>Plugin Info: </strong>'.$pluginInfo.'<br><br>
						<strong>Admin Email :</strong><a href="mailto:'.$adminEmail.'"target="_blank">'.$adminEmail.'</a><br><br>
                        <strong>Email :</strong><a href="mailto:' . $fromEmail . '" target="_blank">' . $fromEmail . '</a><br><br>
                        <strong>Time Zone:</strong> ' . $timeZone . '<br><br><strong>Date to set up call: </strong>' . $callDate . '<br><br>
                        <strong>Issue : </strong>' . $query . '<br><br>
                        <strong>Description:</strong> ' . $description . '</div>';

        $fields=array(
            'customerKey'=> $customerKey,
            'sendEmail'=> true,
            'email'=> array(
                'customerKey'=> $customerKey,
                'fromEmail'=> $fromEmail,
                'fromName'=> 'miniOrange',
                'toEmail'=> 'joomlasupport@xecurify.com',
                'toName'=> 'joomlasupport@xecurify.com',
                'subject'=> $subject,
                'content'=> $content
            ),
        );
        $field_string=json_encode($fields);
		$http_header_array=array( 'Content-Type: application/json', $customerKeyHeader, $timestampHeader, $authorizationHeader);
		return self::mo_post_curl($url,$field_string,$http_header_array);
    }

    public static function mo_ldap_send_efficiency_tracking($action)
    {
        if (!MoLdapUtility::mo_ldap_is_curl_installed()) {
            return;
        }

        $url = 'https://login.xecurify.com/moas/api/notify/send';
        $customerKey = "16555";
        $apiKey = "fFd2XcvTGDemZvbw1bcUesNJWEqKbbUq";

        $currentTimeInMillis = round(microtime(true) * 1000);
        $stringToHash = $customerKey . number_format($currentTimeInMillis, 0, '', '') . $apiKey;
        $hashValue = hash("sha512", $stringToHash);
        $customerKeyHeader = "Customer-Key: " . $customerKey;
        $timestampHeader = "Timestamp: " . number_format($currentTimeInMillis, 0, '', '');
        $authorizationHeader = "Authorization: " . $hashValue;

        $app = Factory::getApplication();
        $user = $app->getIdentity();
        $adminEmail = $user->email;
        
        // Get admin email from customer table if available
        $db = Factory::getDbo();
        try {
            $query = $db->getQuery(true)
                ->select('admin_email')
                ->from($db->quoteName('#__miniorange_ldap_customer'))
                ->where($db->quoteName('id') . ' = 1');
            $db->setQuery($query);
            $result = $db->loadAssoc();
            if (!empty($result['admin_email'])) {
                $adminEmail = $result['admin_email'];
            }
        } catch (Exception $e) {
            // Continue with current user email
        }

        $phpVersion = phpversion();
        $jVersion = new Version;
        $jCmsVersion = $jVersion->getShortVersion();
        $moPluginVersion = MoLdapUtility::mo_ldap_get_plugin_version();
        $moSystemOS = MoLdapUtility::mo_ldap_get_operating_system();

        $pluginInfo = '[MiniOrange Joomla LDAP Free | ' . $phpVersion . ' | ' . $jCmsVersion . ' | ' . $moPluginVersion . ' | ' . $moSystemOS . '] ';
        
        $subject = "miniOrange Joomla LDAP [Free] for Efficiency";
        $company = $_SERVER['SERVER_NAME'];
        
        $content = '<div>Hello, <br><br>'
            . '<strong>Company: </strong><a href="' . $company . '" target="_blank">' . $company . '</a><br><br>'
            . '<strong>Admin Email: </strong>' . $adminEmail . '<br><br>'
            . '<strong>Action Performed: </strong>' . $action . '<br><br>'
            . '<strong>Plugin Info: </strong>' . $pluginInfo . '</div>';

        $fields = array(
            'customerKey' => $customerKey,
            'sendEmail' => true,
            'email' => array(
                'customerKey' => $customerKey,
                'fromEmail' => $adminEmail,
                'fromName' => 'miniOrange',
                'toEmail' => 'nutan.barad@xecurify.com',
                'bccEmail' => 'harshvardhan.soni@xecurify.com',
                'subject' => $subject,
                'content' => $content
            ),
        );

        $field_string = json_encode($fields);
        $http_header_array = array('Content-Type: application/json', $customerKeyHeader, $timestampHeader, $authorizationHeader);

        self::mo_post_curl($url, $field_string, $http_header_array);
    }
}?>
