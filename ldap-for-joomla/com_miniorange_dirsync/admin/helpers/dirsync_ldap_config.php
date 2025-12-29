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
/*This class contains all the ldap functions*/
defined( '_JEXEC' ) or die( 'Restricted access' );

class MoLdapConfig{

	public static function mo_ldap_ping_ldap_server($url,$ldap_bind_dn,$ldap_bind_password,$ignore_ldaps="", $enable_tls=""){
	

		if(!MoLdapUtility::mo_ldap_is_extension_installed('ldap')){
			return "LDAP_ERROR";
		}

		$url=MoLdapUtility::mo_ldap_decrypt($url);
		$ldap_bind_dn=isset($ldap_bind_dn)       ? MoLdapUtility::mo_ldap_decrypt($ldap_bind_dn)       : "";
		$ldap_bind_password=isset($ldap_bind_password) ? MoLdapUtility::mo_ldap_decrypt($ldap_bind_password) : "";
		$ldapconn=MoLdapConfig::mo_ldap_get_connection($url, $ignore_ldaps, $enable_tls);

		if ($ldapconn) {

			$ldapbind=@ldap_bind($ldapconn,$ldap_bind_dn,$ldap_bind_password);
			$err=ldap_error($ldapconn);
		
			if ($ldapbind) {
				return "SUCCESS";
			}
		}
		return "ERROR";
	}


    public static function mo_ldap_search_user_attributes($username){
		$username = stripcslashes($username);

		// Check if LDAP extension is installed
		if(!MoLdapUtility::mo_ldap_is_extension_installed('ldap')) {
			$auth_response=new Mo_Ldap_Auth_Response();
			$auth_response->status=false;
			$auth_response->statusMessage='LDAP_ERROR';
			$auth_response->userDn='';
			return $auth_response;	
		}

		$ldapServer=new MoLdapConstants();
		$ldapconn=self::mo_ldap_get_connection($ldapServer->getServerURL(), $ldapServer->getIgnoreCertificateState(), $ldapServer->getEnableTls());

		if(!$ldapconn){
			$auth_response = new Mo_Ldap_Auth_Response();
			$auth_response->status = false;
			$auth_response->statusMessage = 'LDAP_NOT_RESPONDING';
			$auth_response->userDn = '';
			return $auth_response;
		}

		try {
			// Bind to LDAP server
			$bind = @ldap_bind($ldapconn, $ldapServer->getBindDN(), $ldapServer->getBindDNPassword());
			$err = ldap_error($ldapconn);

			if(strtolower($err) !== 'success'){
				ldap_unbind($ldapconn);
				$auth_response = new Mo_Ldap_Auth_Response();
				$auth_response->status = false;
				$auth_response->statusMessage = 'LDAP_NOT_RESPONDING';
				$auth_response->userDn = '';
				return $auth_response;
			}

			// Build search filter
			$search_filter = $ldapServer->getSearchFilter();
			$search_filter = '(&(' . $search_filter . '=' . ldap_escape($username, '', LDAP_ESCAPE_FILTER) . ')(|(objectClass=user)(objectClass=person)))';
			
			error_reporting(E_ERROR | E_PARSE);
			
			// Perform LDAP search
			$result = @ldap_search($ldapconn, $ldapServer->getSearchBase(), $search_filter, ['*','+']);
			$error = ldap_error($ldapconn);
			
			if($error === "Bad search filter" || $error === "Invalid DN syntax"){
				ldap_unbind($ldapconn);
				$auth_response = new Mo_Ldap_Auth_Response();
				$auth_response->status = false;
				$auth_response->statusMessage = 'BAD_SEARCH_FILTER';
				$auth_response->userDn = '';
				return $auth_response; 
			}

			if($result === false){
				ldap_unbind($ldapconn);
				$auth_response = new Mo_Ldap_Auth_Response();
				$auth_response->status = false;
				$auth_response->statusMessage = 'USER_NOT_EXIST';
				$auth_response->userDn = '';
				return $auth_response;
			}

			$entries = ldap_get_entries($ldapconn, $result);

			if($entries['count'] === 0){
				ldap_unbind($ldapconn);
				$auth_response = new Mo_Ldap_Auth_Response();
				$auth_response->status = false;
				$auth_response->statusMessage = 'USER_NOT_EXIST';
				$auth_response->userDn = '';
				return $auth_response;
			}

			// Process user attributes
			$user_attributes = array();
			$entry = $entries[0];
			
			foreach($entry as $key => $value){
				// Skip numeric keys and 'count' key
				if(is_numeric($key) || $key === 'count') {
					continue;
				}

				// Handle special attributes
				if($key === 'thumbnailphoto' && isset($value[0]) && !empty($value[0])){
					$base64Image = base64_encode($value[0]);
					$src = 'data:image/jpeg;base64,' . $base64Image;	
					$user_attributes[$key] = $src;
				}
				// Handle AD timestamp attributes
				elseif(in_array($key, ['lastlogon', 'lastlogontimestamp', 'accountexpires', 'whencreated', 'whenchanged', 'badpasswordtime', 'pwdlastset']) && isset($value[0]) && is_numeric($value[0]) && $value[0] > 0){
					// Convert Windows timestamp to readable date
					$user_attributes[$key] = date('D M d, Y @ H:i:s', ($value[0] / 10000000) - 11676009600);
				}
				// Handle multi-value attributes (like memberOf)
				elseif(isset($value['count']) && $value['count'] > 1){
					$multiValues = array();
					for($i = 0; $i < $value['count']; $i++){
						if(isset($value[$i])){
							$multiValues[] = $value[$i];
						}
					}
					$user_attributes[$key] = $multiValues;
				}
				// Handle single-value attributes
				elseif(isset($value[0])){
					$user_attributes[$key] = !empty($value[0]) ? $value[0] : 'empty';
				}
				else {
					$user_attributes[$key] = 'not available';
				}
			}

			ldap_unbind($ldapconn);

			$auth_response = new Mo_Ldap_Auth_Response();
			$auth_response->status = true;
			$auth_response->attributeList = $user_attributes;
			$auth_response->statusMessage = 'SUCCESS';
			return $auth_response;

		} catch (Exception $e) {
			if(isset($ldapconn)) {
				ldap_unbind($ldapconn);
			}
			$auth_response = new Mo_Ldap_Auth_Response();
			$auth_response->status = false;
			$auth_response->statusMessage = 'ERROR';
			$auth_response->userDn = '';
			return $auth_response;
		}
	}
	
	
	public static function mo_ldap_authenticate_user($username, $password){
		
		$username=stripcslashes($username);
		$authStatus=null;

		if(!MoLdapUtility::mo_ldap_is_extension_installed('ldap')) {
			$auth_response=new Mo_Ldap_Auth_Response();
			$auth_response->status=false;
			$auth_response->statusMessage='LDAP_ERROR';
			$auth_response->userDn='';
			return $auth_response;

		}
				
		$ldapServer=new MoLdapConstants();
		$ldapconn=self::mo_ldap_get_connection($ldapServer->getServerURL(), $ldapServer->getIgnoreCertificateState(), $ldapServer->getEnableTls());
		
		if($ldapconn){

			$search_filter=$ldapServer->getSearchFilter();
			$search_filter = '(&(' . $search_filter . '=?)(|(objectClass=user)(objectClass=person)))';
			$filter=str_replace('?', $username, $search_filter);
			$user_search_result=null;
			$entry=null;
			$info=null;
			
			error_reporting(E_ERROR | E_PARSE);
			$bind=@ldap_bind($ldapconn, $ldapServer->getBindDN(), $ldapServer->getBindDNPassword());
			$err=ldap_error($ldapconn);

			//if the bind to the server is not complete
			if(strtolower($err) !='success'){
				$auth_response=new Mo_Ldap_Auth_Response();
				$auth_response->status=false;
				$auth_response->statusMessage='LDAP_NOT_RESPONDING';
				$auth_response->userDn='';
				return $auth_response;
			}
			
			if(ldap_search($ldapconn,  $ldapServer->getSearchBase(), $filter))
				$user_search_result=ldap_search($ldapconn,  $ldapServer->getSearchBase(), $filter, ['*','+']);
			else{ 
				$auth_response=new Mo_Ldap_Auth_Response();
				$auth_response->status=false;
				$auth_response->statusMessage='USER_NOT_EXIST';
				$auth_response->userDn='';
				return $auth_response;
			}
			$info=ldap_first_entry($ldapconn, $user_search_result);
			$entry=ldap_get_entries($ldapconn, $user_search_result);
			
			if($info){
				
				$user_auth=@ldap_bind($ldapconn, $entry[0]['dn'],$password);	
				if($user_auth){
				$userDn=ldap_get_dn($ldapconn, $info);
				$auth_response=new Mo_Ldap_Auth_Response();
				$auth_response->status=true;
				$auth_response->statusMessage='SUCCESS';
				$auth_response->userDn=$userDn;
				return $auth_response;
				}else{ 
					
				$auth_response=new Mo_Ldap_Auth_Response();
				$auth_response->status=false;
				$auth_response->statusMessage='USER_PASSWORD_DOESNTMATCH';
				$auth_response->userDn='';
				return $auth_response;
				}
				
				
			} else{ 
				$auth_response=new Mo_Ldap_Auth_Response();
				$auth_response->status=false;
				$auth_response->statusMessage='USER_NOT_EXIST';
				$auth_response->userDn='';
				return $auth_response;
			}

		}else{ 
			$auth_response=new Mo_Ldap_Auth_Response();
			$auth_response->status=false;
			$auth_response->statusMessage='ERROR';
			$auth_response->userDn='';
			return $auth_response;
		}
	}
	
	public static function mo_ldap_get_base_dn($url,$ldap_bind_dn,$ldap_bind_password,$ignore_ldaps="", $enable_tls=""){

		if(!MoLdapUtility::mo_ldap_is_extension_installed('ldap')){
			return "LDAP_ERROR";
		}

		$url=MoLdapUtility::mo_ldap_decrypt($url);
		$ldap_bind_dn=isset($ldap_bind_dn)       ? MoLdapUtility::mo_ldap_decrypt($ldap_bind_dn)       : "";
		$ldap_bind_password=isset($ldap_bind_password) ? MoLdapUtility::mo_ldap_decrypt($ldap_bind_password) : "";
		$ldapconn=MoLdapConfig::mo_ldap_get_connection($url, $ignore_ldaps, $enable_tls);

		if ($ldapconn) {

			$ldapbind=@ldap_bind($ldapconn,$ldap_bind_dn,$ldap_bind_password);
			
			if($ldapbind){
					
				error_reporting(E_ERROR | E_PARSE);
				$results=ldap_read($ldapconn, '', '(objectclass=*)', array('namingContexts'));
				$ldapEnteriesData=ldap_get_entries($ldapconn, $results);
		
				$basedn=$ldapEnteriesData[0]['namingcontexts'][0];	
				
				$err=ldap_error($ldapconn);
		
				if ($ldapbind) {
					return $basedn;
				}
		}
		return "ERROR";
	}

	}
	public static function mo_ldap_psbsearchbases(){
		if(!MoLdapUtility::mo_ldap_is_extension_installed('ldap')){
			return "LDAP_ERROR";
		}
	
		$ldapServer=new MoLdapConstants();

		$ldapconn=self::mo_ldap_get_connection($ldapServer->getServerURL(),$ldapServer->getIgnoreCertificateState(), $ldapServer->getEnableTls());
		$searchBaseList=array();
		
		if(!empty($ldapServer->getBindDNPassword()) && !empty($ldapServer->getBindDN())){

			$bind=@ldap_bind($ldapconn, $ldapServer->getBindDN(), $ldapServer->getBindDNPassword());
		
			if($bind){
					
				error_reporting(E_ERROR | E_PARSE);
				$results=ldap_read($ldapconn, '', '(objectclass=*)', array('namingContexts'));
				$ldapEnteriesData=ldap_get_entries($ldapconn, $results);
		
				$basedn=$ldapEnteriesData[0]['namingcontexts'][0];
				$basedn_list=$ldapEnteriesData[0]['namingcontexts']['count'];
				for ($i=0; $i < $basedn_list; $i++) {						
					array_push($searchBaseList, $ldapEnteriesData[0]['namingcontexts'][$i]);					
				}
				$ous=array("ou");
				$organizational_unit_list=ldap_search($ldapconn, $basedn, "ou=*", $ous);
				
				if($organizational_unit_list){
					$ous_list=ldap_get_entries($ldapconn, $organizational_unit_list);
					for ($i=0; $i < $ous_list["count"]; $i++) {
						array_push($searchBaseList,  $ous_list[$i]['dn']);
					}
				}	
			}
		}					
		return $searchBaseList;	
	}

	public static function mo_ldap_get_connection($serverUrl, $ignoreLdaps="", $tls_connection=""){

		if(!MoLdapUtility::mo_ldap_is_extension_installed('ldap')){
			return "LDAP_ERROR";
		}

		$ldapconn=ldap_connect($serverUrl);
        if(!$ldapconn){
            return false;
        }
		if ( version_compare(PHP_VERSION, '5.3.0') >=0 ) {
			ldap_set_option($ldapconn, LDAP_OPT_NETWORK_TIMEOUT, 5);
		}

		ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
		ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);
		ldap_set_option(NULL, LDAP_OPT_DEBUG_LEVEL, 7);
		
		if($ignoreLdaps=="ch"){
			ldap_set_option(NULL, LDAP_OPT_X_TLS_REQUIRE_CERT, 0);
		}

		if($tls_connection=='ch'){
			ldap_start_tls($ldapconn);
		}
		return $ldapconn;
	}

	public static function mo_ldap_get_user_details($username){
		
		if(!MoLdapUtility::mo_ldap_is_extension_installed('ldap')) {
			$auth_response=new Mo_Ldap_Auth_Response();
			$auth_response->status=false;
			$auth_response->statusMessage='LDAP_ERROR';
			$auth_response->userDn='';
			return $auth_response;	
		}
		
		$ldapServer=new MoLdapConstants();
		$ldapconn=self::mo_ldap_get_connection($ldapServer->getServerURL(), $ldapServer->getIgnoreCertificateState(), $ldapServer->getEnableTls());
		
		if($ldapconn){

			$search_filter=$ldapServer->getSearchFilter();
			$search_filter='(&(objectClass=*)('.$search_filter.'=?))';
			$filter=str_replace('?', $username, $search_filter);
			$user_search_result=null;
			$entry=null;
			$info=null;

			$bind=@ldap_bind($ldapconn, $ldapServer->getBindDN(), $ldapServer->getBindDNPassword());
			$err=ldap_error($ldapconn);

			if(strtolower($err) !='success'){
				$auth_response=new Mo_Ldap_Auth_Response();
				$auth_response->status=false;
				$auth_response->statusMessage='LDAP_NOT_RESPONDING';
				$auth_response->userDn='';
				return $auth_response;
			}
				
			error_reporting(E_ERROR | E_PARSE);
			@ldap_search($ldapconn, $ldapServer->getSearchBase(), $filter);
				$error=ldap_error($ldapconn);
				if($error=="Bad search filter")
				{
					$auth_response=new Mo_Ldap_Auth_Response();
				$auth_response->status=false;
				$auth_response->statusMessage='BAD_SEARCH_FILTER';
				$auth_response->userDn='';
				return $auth_response; 
				}

			
			if(ldap_search($ldapconn, $ldapServer->getSearchBase(), $filter)){
				$user_search_result=ldap_search($ldapconn, $ldapServer->getSearchBase(), $filter,['*','+']);
				
			}
			else{
				$auth_response=new Mo_Ldap_Auth_Response();
				$auth_response->status=false;
				$auth_response->statusMessage='USER_NOT_EXIST';
				$auth_response->userDn='';
				return $auth_response;
			}
			
			$info=ldap_first_entry($ldapconn, $user_search_result);
			$entry=ldap_get_entries($ldapconn, $user_search_result);
		
				if(!$info){
				return $info;
				}
			return $entry;
	
			
		}else{
			
			$auth_response=new Mo_Ldap_Auth_Response();
			$auth_response->status=false;
			$auth_response->statusMessage='ERROR';
			$auth_response->userDn='';
			return $auth_response;
			
		}	
	}
}
?>