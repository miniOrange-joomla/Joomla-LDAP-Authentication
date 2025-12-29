<?php
defined('_JEXEC') or die;

/**
* @package     Joomla.Plugin
* @subpackage  plg_authetntication_moldap
*
* @author      miniOrange Security Software Pvt. Ltd.
* @copyright   Copyright (C) 2015 miniOrange (https://www.miniorange.com)
* @license     GNU General Public License version 3; see LICENSE.txt
* @contact     info@xecurify.com
*/
 
 defined('_JEXEC') or die('Restricted access');
 use Joomla\CMS\Plugin\CMSPlugin;
 use Joomla\CMS\Factory;
 use Joomla\CMS\User\UserHelper;
 use Joomla\CMS\Authentication\Authentication;
 use Joomla\CMS\Filter\InputFilter;
 use Joomla\CMS\Language\Text;
// Add language file in this file
$lang = Factory::getLanguage();
$lang->load('plg_authentication_moldap', JPATH_ADMINISTRATOR);

require_once JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_miniorange_dirsync'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'mo_ldap_utility.php';
 require_once JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_miniorange_dirsync'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'dirsync_ldap_config.php';
 require_once JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_miniorange_dirsync'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'dirsync_handler.php';
 require_once JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_miniorange_dirsync'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'mo_ldap_constants.php';
require_once JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_miniorange_dirsync'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'MoLdapLogger.php';
 if (defined('_JEXEC')) {
	 
	 
	/**
	 * This block of code is not used.
	 */
	class plgauthenticationmoldap extends CMSPlugin
	{
		/**
		 * This method should handle any authentication and report back to the subject
		 * 
		 *
		 * @access    public
		 * @param     array     $credentials    Array holding the user credentials ('username' and 'password')
		 * @param     array     $options        Array of extra options
		 * @param     object    $response       Authentication response object
		 * @return    boolean
		 */
		 
		
	function onUserAuthenticate(array $credentials, array $options, object &$response )
	{
		
		$app=Factory::getApplication('site');
		$uname=trim($credentials['username']);	
		$upasswd=isset($credentials['password'])? $credentials['password']: '';

		if($upasswd=='' || $upasswd==' '){
					
			$response->status=Authentication::STATUS_FAILURE;
			$app->enqueueMessage('Kindly please enter the password.', 'warning');
			return;
		}

		$ldapServerConfig=new MoLdapConstants;
		$user_auth='false';
		
		if($ldapServerConfig->getEnableLdap()=='ch'){
            $current_time = date('Y-m-d H:i:s');
            $ip_address = $_SERVER['REMOTE_ADDR'];
			$validatefilter=InputFilter::getInstance();
			$username=$validatefilter->clean($uname, 'username');
			$password=$upasswd; //NOT VALIDATING PASSWORDS AS THEY MIGHT CONTAIN SPECIAL CHARACTERS

			$serverName=$ldapServerConfig->getServerURL();
            $ldapHostname = filter_var($serverName, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $ldapServerConfig->setLdapConnObject(self::getConnection($ldapHostname));

			if($ldapServerConfig->getLdapConnObject()){

				$search_filter=$ldapServerConfig->getSearchFilter();
				$filterName=ldap_escape($username, "", LDAP_ESCAPE_FILTER);
				$search_filter = '(&(' . $search_filter . '=?)(|(objectClass=user)(objectClass=person)))';
				$filter=str_replace('?', $filterName, $search_filter);


				$bind=@ldap_bind($ldapServerConfig->getLdapConnObject(), $ldapServerConfig->getBindDN(), $ldapServerConfig->getBindDNPassword());
				if (!$bind) {
                    $app->enqueueMessage('<strong>MOLDAP A01:</strong> ' . Text::_('PLG_AUTHENTICATION_LDAP_CONTACT_ADMIN'), 'warning');
                    MoLdapLogger::addLog('LDAP bind failed.','ERROR');
				}
				$err=ldap_error($ldapServerConfig->getLdapConnObject());
										
				$user_auth="";
				$error_number=ldap_errno($ldapServerConfig->getLdapConnObject());
				$errormessage=MoLdapUtility::mo_ldap_error_type($error_number);

				if($errormessage!='COM_MINIORANGE_SUCCESSFUL_CONNECTION')
					return $errormessage;

				if(strtolower($err)=='success'){
					$userSearchResult = ldap_search($ldapServerConfig->getLdapConnObject(), $ldapServerConfig->getSearchBase(), $filter, ['*', '+']);

					if($userSearchResult) {
						$info=ldap_first_entry($ldapServerConfig->getLdapConnObject(), $userSearchResult);
						$entry=ldap_get_entries($ldapServerConfig->getLdapConnObject(), $userSearchResult);
							
						if($info)
							$user_auth=@ldap_bind($ldapServerConfig->getLdapConnObject(), $entry[0]['dn'],$upasswd);
								
						if($user_auth=='true'){
							$response->type='Ldap';
							$response->error_message='';
						
							//IF ATTRIBUTE MAPPING IS CONFIGURED
							$response->username=self::moLdapAttributeMapping($entry,$ldapServerConfig->getUsernameAttribute(),'string');
							$response->email=self::moLdapAttributeMapping($entry,$ldapServerConfig->getEmailAttribute(),'email');
							$response->fullname=self::moLdapAttributeMapping($entry,$ldapServerConfig->getNameAttribute(),'string');
					
							if(empty($response->email)){
									
								$app->enqueueMessage('<strong>MOLDAP A02:</strong> ' . Text::_('PLG_AUTHENTICATION_LDAP_CONTACT_ADMIN'), 'warning');
								MoLdapLogger::addLog('User email not retrieved.','CRITICAL');
								return;
							}

							if(!filter_var($response->email, FILTER_VALIDATE_EMAIL)){
								$app->enqueueMessage('<strong>MOLDAP A03:</strong> ' . Text::_('PLG_AUTHENTICATION_LDAP_CONTACT_ADMIN'), 'warning');
								MoLdapLogger::addLog('User email attribute that you have contacted is incorrect.','CRITICAL');
								return;
							}
							
							if(empty($response->username)){
								$app->enqueueMessage('<strong>MOLDAP A04:</strong> ' . Text::_('PLG_AUTHENTICATION_LDAP_CONTACT_ADMIN'), 'warning');
								MoLdapLogger::addLog('Username not retrieved. Not getting user\'s username.','WARNING');
								return;
							}

							if(empty($response->fullname)){
								$app->enqueueMessage('<strong>MOLDAP A05:</strong> ' . Text::_('PLG_AUTHENTICATION_LDAP_CONTACT_ADMIN'), 'warning');
								MoLdapLogger::addLog('User\'s name not retrieved. Not getting user\'s name.','WARNING');
								return;
							}
                            
                            if (UserHelper::getUserId($response->username) == 0) {
                                $app->enqueueMessage(
                                    '<strong>MOLDAP A07:</strong> ' . Text::_('PLG_AUTHENTICATION_LDAP_CONTACT_ADMIN'),
                                    'warning'
                                );
                                MoLdapLogger::addLog(
                                    "User {$response->username} does not exist in your Joomla site. Auto user-creation is not allowed in free version upgrade now or Contact joomlasupport@xecurify.com.",
                                    'WARNING', 'MOLDAP A07'
                                );
                                $response->status = Authentication::STATUS_FAILURE;
                                return;
                            }
                            
							//CHECK FOR SAME EMAIL DIFFERENT USERNAME
							$userEmailExists=MoLdapUtility::moLdapFetchData('#__users',array('email'=> $response->email),'loadAssoc',array('email', 'username'),);
					
							if(!empty($userEmailExists) && $userEmailExists['username'] !=$response->username){
							
								$app->enqueueMessage('<strong>MOLDAP A06:</strong> ' . Text::_('PLG_AUTHENTICATION_LDAP_CONTACT_ADMIN'), 'warning');
								MoLdapLogger::addLog('Username and Email mismatch.','CRITICAL');
								return;
							}

							$userUsernameExists=MoLdapUtility::moLdapFetchData('#__users',array('username'=> $response->email),'loadAssoc',array('email', 'username'));
							if(!empty($userUsernameExists) && $userUsernameExists['email'] !=$response->email){
						
								$app->enqueueMessage('<strong>MOLDAP A06:</strong> ' . Text::_('PLG_AUTHENTICATION_LDAP_CONTACT_ADMIN'), 'warning');
								MoLdapLogger::addLog('Username and Email mismatch.','CRITICAL');

								return;
							}
                            $login_attempt_data = MoLdapUtility::moLdapFetchData(
                                '#__mo_ldap_login_attempts',
                                array('ip_address' => $ip_address, 'username' => $username)
                            );
                            if ($login_attempt_data) {
                                $user_blocked_until = $login_attempt_data['blocked_until'];
                                $user_login_attempts = $login_attempt_data['user_login_attempts'];
                                if ($user_login_attempts>=5 && $user_blocked_until && strtotime($current_time) < strtotime($user_blocked_until)) {
                                    // User is blocked, display error message
                                    $app->enqueueMessage('<strong>Error:</strong> ' . Text::_('PLG_AUTH_LDAP_LOGIN_BLOCKED_MESSAGE'), 'error');
                                    $response->status = Authentication::STATUS_FAILURE;
                                    return;
                                }
                            }
                            MoLdapUtility::moLdapUpdateData(
                                '#__mo_ldap_login_attempts',
                                array('user_login_attempts' => 0, 'blocked_until' => date('Y-m-d H:i:s', strtotime('+1 year'))),
                                array('ip_address' => $ip_address, 'username' => $username)
                            );
							$response->status=Authentication::STATUS_SUCCESS;
							MoLdapLogger::addLog('Authentication successful for the user '.$uname);
							return;
						}
                        else if($info && !$user_auth){
                            $app->enqueueMessage('<strong>MOLDAP A08:</strong> ' . Text::_('PLG_AUTHENTICATION_LDAP_CONTACT_ADMIN'), 'warning');
                            MoLdapLogger::addLog('LDAP Authentication failed for the user- '.$uname,'ERROR','MOLDAP A08');
                        }
                  }
                  else{
                      MoLdapLogger::addLog('LDAP search failed.','ERROR');
                  }
                }
                            return;
						}
                        else if (!$user_auth) {
                            $login_attempt_data = MoLdapUtility::moLdapFetchData(
                                '#__mo_ldap_login_attempts',
                                array('ip_address' => $ip_address, 'username' => $username)
                            );

                            if ($login_attempt_data) {
                                $user_login_attempts = $login_attempt_data['user_login_attempts'];
                                $user_blocked_until = $login_attempt_data['blocked_until'];
                                $time_difference = abs((strtotime($current_time) - strtotime($user_blocked_until)) / 60);

                                // Check if user is still blocked
                                if ($user_login_attempts>=5 && $user_blocked_until && $time_difference < 30) {
                                    $app->enqueueMessage('<strong>Error:</strong> ' . Text::_('PLG_AUTH_LDAP_LOGIN_BLOCKED_MESSAGE'), 'error');
                                    $response->status = Authentication::STATUS_FAILURE;
                                    return;
                                }

                                // Reset attempts if block expired
                                if ($current_time >= $user_blocked_until) {
                                    $user_login_attempts = 0;
                                    $user_blocked_until = null;
                                }

                                $user_login_attempts++;

                                // Block user after 5 failed attempts
                                if ($user_login_attempts >= 5) {
                                    $user_blocked_until = date('Y-m-d H:i:s', strtotime('+30 minutes'));
                                    $app->enqueueMessage('<strong>Error:</strong> ' . Text::_('PLG_AUTH_LDAP_LOGIN_BLOCKED_MESSAGE'), 'error');
                                }

                                // Ensure `blocked_until` is not empty
                                if (!$user_blocked_until) {
                                    $user_blocked_until = date('Y-m-d H:i:s', strtotime('+1 year'));
                                }

                                // Update failed attempts
                                $update_fields = array(
                                    'user_login_attempts' => $user_login_attempts,
                                    'last_attempt_time' => $current_time,
                                    'blocked_until' => $user_blocked_until
                                );

                                MoLdapUtility::moLdapUpdateData(
                                    '#__mo_ldap_login_attempts',
                                    $update_fields,
                                    array('ip_address' => $ip_address, 'username' => $username)
                                );

                            } else {
                                // First failed attempt, insert new row
                                $insert_fields = array(
                                    'ip_address' => $ip_address,
                                    'username' => $username,
                                    'user_login_attempts' => 1,
                                    'last_attempt_time' => $current_time,
                                    'blocked_until' => null
                                );

                                MoLdapUtility::moLdapInsertData('#__mo_ldap_login_attempts', $insert_fields);
                            }
                        }
		}else{
		    $response->status=Authentication::STATUS_FAILURE;
			MoLdapLogger::addLog('You have not enabled LDAP Login.','NOTICE');
		}
	}


	public static function getConnection($serverName){

		$ldapconn=@ldap_connect($serverName);
        if (!$ldapconn) {
            MoLdapLogger::addLog('LDAP connection failed.','ERROR');
            return false;
		}
		if ( version_compare(PHP_VERSION, '5.3.0') >=0 ) {
			ldap_set_option($ldapconn, LDAP_OPT_NETWORK_TIMEOUT, 5);
		}

		ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
		ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);
		$ldapServerConfig=new MoLdapConstants;
		$ignoreLdaps=$ldapServerConfig->getIgnoreCertificateState();
		$enableTls = $ldapServerConfig->getEnableTls();

		if($ignoreLdaps=='ch')
			ldap_set_option(NULL, LDAP_OPT_X_TLS_REQUIRE_CERT, 0);
		
		if($enableTls=='ch')
			ldap_start_tls($ldapconn);

		return $ldapconn;
	}

	public static function moLdapAttributeMapping($entry, $attributeMap,$attributeType){
		
		$validatefilter=InputFilter::getInstance();
		$userAttribute="";
        if(isset($attributeMap)){					 
			if(!empty($entry[0][$attributeMap][0]))
			  	$userAttribute=$validatefilter->clean($entry[0][$attributeMap][0], $attributeType);  
			else if(!empty($entry[0]['userprincipalname'][0]))
			    $userAttribute=$validatefilter->clean($entry[0]['userprincipalname'][0], $attributeType);
			else if(!empty($entry[0]['mail'][0]))
			    $userAttribute=$validatefilter->clean($entry[0]['mail'][0], $attributeType);
		}else{
		    if(!empty($entry[0]['userprincipalname'][0]))
			    $userAttribute=$validatefilter->clean($entry[0]['userprincipalname'][0], $attributeType);
			else if(!empty($entry[0]['mail'][0]))
			    $userAttribute=$validatefilter->clean($entry[0]['mail'][0], $attributeType);
		}   
		return $userAttribute;

	}

	public static function onUserAfterLogin(){
			$app  = Factory::getApplication();
			$post = $app->input->post->getArray();
		
		if(isset($post['username'])){
			
		//DB for gettting User Profile Attributes
		$ldapServerConfig=new MoLdapConstants;

			if($ldapServerConfig->getEnableLdap()=='ch'){

				$validatefilter=InputFilter::getInstance();
				$uname=$validatefilter->clean($post['username'], 'username');
				$app = Factory::getApplication();
				$users = $app->getIdentity();	
					
				$serverName=$ldapServerConfig->getServerURL();
				$ldapHostname=filter_var($ldapServerConfig->getServerURL(), FILTER_VALIDATE_URL);
				$ldapconn=self::getConnection($ldapHostname);
					
				if($ldapconn){

					$search_filter=$ldapServerConfig->getSearchFilter();
					$search_filter = '(&(' . $search_filter . '=?)(|(objectClass=user)(objectClass=person)))';
					$filterName=ldap_escape($uname, "", LDAP_ESCAPE_FILTER);
					$filter=str_replace('?', $filterName, $search_filter);

					$bind=@ldap_bind($ldapconn, $ldapServerConfig->getBindDN(),  $ldapServerConfig->getBindDNPassword());
					if (!$bind) {
                        MoLdapLogger::addLog('LDAP bind failed.','ERROR');
                        return;
					}
					$err=ldap_error($ldapconn);
					$extended_error=ldap_error($ldapconn);
					$error_number=ldap_errno($ldapconn);
					$errormessage=MoLdapUtility::mo_ldap_error_type($error_number);

					if($errormessage!='COM_MINIORANGE_SUCCESSFUL_CONNECTION')
					{
						MoLdapLogger::addLog('LDAP bind failed: ' . $errormessage,'ERROR');
						return $errormessage;
					}

					if(ldap_search($ldapconn, $ldapServerConfig->getSearchBase(), $filter)){
						$userSearchResult=ldap_search($ldapconn, $ldapServerConfig->getSearchBase(), $filter,['*','+']);	
						$info=ldap_first_entry($ldapconn, $userSearchResult);
						$entry=ldap_get_entries($ldapconn, $userSearchResult);						
										
						if($info){
						   	$uname=self::moLdapAttributeMapping($entry,$ldapServerConfig->getUsernameAttribute(),'string');
							$uemail=self::moLdapAttributeMapping($entry,$ldapServerConfig->getEmailAttribute(),'email');
							$jname=self::moLdapAttributeMapping($entry,$ldapServerConfig->getNameAttribute(),'string');

							// Group Mapping
							if($ldapServerConfig->getEnableRoleMapping()){
								if(isset($users->groups[8]))
									$flag=0;
								else
									$flag=1;
						
								if($flag){
									if (MoLdapUtility::updateUserAlreadyExist($users->id)) {
									    UserHelper::addUserToGroup($users->id, $ldapServerConfig->getMappingValueDefault());
									    $userGroupName = MoLdapUtility::getUserGroupName($ldapServerConfig->getMappingValueDefault());
									    MoLdapLogger::addLog('User '.$jname.' assigned default role '.$userGroupName);
									}
								}
							}
						}
					}
                    else {
                        MoLdapLogger::addLog('LDAP search failed.','ERROR');
                    }
				}
                else {
                    MoLdapLogger::addLog('LDAP connection failed.','ERROR');
                }
			}
		  }
	}
 }
}
?>