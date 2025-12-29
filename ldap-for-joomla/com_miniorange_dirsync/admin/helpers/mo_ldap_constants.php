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

/*This class contains all the ldap constants*/
defined( '_JEXEC' ) or die( 'Restricted access' );



class MoLdapConstants
{
    private $ldapconn="";
	private $bind="";
	private $serverUrl="";
	private $serviceAccountUsername="";
  	private $serviceAccountPassword="";
  	private $searchBase="";
  	private $searchFilter="";
	private $ignoreLdaps="";
    private $enableTls = "";
    private $serverType="";
    private $enableLdap="";
    private $username="";
    private $email="";
    private $name="";
    private $userProfileAttributes="";
    private $userFieldAttributes="";
    private $mappingValueDefault="";
    private $roleMappingKeyValue="";
    private $roleMappingGroupValue="";
    private $enableRolemapping=0;
    private $roleMappingCount=2;
    private $activeDirectoryAttributes="";
    private $testUsername="";
    private $testConfigDetails="";
    private int $enable_Loggers=0;

    public function __construct(){
        
        try {
            $result=MoLdapUtility::moLdapFetchData('#__miniorange_dirsync_config',array('id'=>'1'),'loadAssoc');
        } catch (Exception $e) {
            $result = array();
        }
        
        // dd($result);		
        $this->serverType=isset($result['mo_ldap_directory_server_type']) ? $result['mo_ldap_directory_server_type'] : "";
        $this->serverUrl=isset($result['ldap_server_url']) ? MoLdapUtility::mo_ldap_decrypt($result['ldap_server_url']) : "";
        $this->serviceAccountUsername=isset($result['service_account_dn']) ? MoLdapUtility::mo_ldap_decrypt($result['service_account_dn']) : "";
        $this->serviceAccountPassword=isset($result['service_account_password']) ? MoLdapUtility::mo_ldap_decrypt($result['service_account_password']) : "";
        $this->searchBase=isset($result['search_base']) ? MoLdapUtility::mo_ldap_decrypt($result['search_base']) : "";
        $this->searchFilter=isset($result['search_filter']) ? $result['search_filter'] : "";
        $this->ignoreLdaps=isset($result['enable_dirsync_scheduler']) ? $result['enable_dirsync_scheduler'] : "";
        $this->enableTls = isset($result['enable_tls']) ? $result['enable_tls'] : "";
        $this->enableLdap=isset($result['ldap_login']) ? $result['ldap_login'] : "";
        $this->enable_Loggers=isset($result['mo_ldap_enable_logger']) ? $result['mo_ldap_enable_logger'] : 0;
        $this->username=isset($result['username']) ? $result['username'] : "";
        $this->email=isset($result['email']) ? $result['email'] : "";
        $this->name=isset($result['name']) ? $result['name'] : "";
        $this->userProfileAttributes=isset($result['user_profile_attributes']) ? json_decode($result['user_profile_attributes'],true) : "";
        $this->userFieldAttributes=isset($result['user_field_attributes']) ? json_decode($result['user_field_attributes'],true) : "";
        $this->activeDirectoryAttributes = isset($result['ad_attribute_list']) && !empty($result['ad_attribute_list']) ? json_decode($result['ad_attribute_list'], true) : array("userprincipalname", "samaccountname", "mail", "sn", "cn", "givenname", "telephonenumber", "mobile", "description", "department", "company", "displayname");
        $this->testUsername = isset($result['ldap_test_username']) ? $result['ldap_test_username'] : "";
        $this->testConfigDetails = isset($result['test_config_details']) && !empty($result['test_config_details'])? json_decode($result['test_config_details'],true):"";
        
        try {
            $roleMapping=MoLdapUtility::moLdapFetchData('#__miniorange_ldap_role_mapping',array('id'=>'1'),'loadAssoc');
        } catch (Exception $e) {
            // Table doesn't exist yet (during installation) - use empty result
            $roleMapping = array();
        }
        
        $this->mappingValueDefault=isset($roleMapping['mapping_value_default']) ? $roleMapping['mapping_value_default']: '';
		$this->roleMappingKeyValue=isset($roleMapping['role_mapping_key_value'])? json_decode($roleMapping['role_mapping_key_value']) : '';
		$this->roleMappingGroupValue=isset($roleMapping['role_mapping_groupvalue']) ? json_decode($roleMapping['role_mapping_groupvalue'], true ): '';
        $this->enableRolemapping=isset($roleMapping['enable_ldap_role_mapping']) ? $roleMapping['enable_ldap_role_mapping'] : 0;
        $this->roleMappingCount=isset($roleMapping['role_mapping_count']) ? $roleMapping['role_mapping_count'] : 2;
    }

    /**
     * @return mixed
     */
    public function getServerType()
    {
        return $this->serverType;
    }


    /**
     * @return mixed
     */
    public function getServerURL()
    {
        return $this->serverUrl;
    }

    /**
     * @return mixed
     */
    public function getBindDN()
    {
        return $this->serviceAccountUsername;
    }

    /**
     * @return mixed
     */
    public function getBindDNPassword()
    {
        return $this->serviceAccountPassword;
    }

    /**
     * @return mixed
     */
    public function getSearchBase()
    {
        return $this->searchBase;
    }

    /**
     * @return mixed
     */
    public function getSearchFilter()
    {
        return $this->searchFilter;
    }

    /**
     * @return mixed
     */
    public function getIgnoreCertificateState()
    {
        return $this->ignoreLdaps;
    }

    /**
     * @return mixed
     */
    public function getEnableTls()
    {
        return $this->enableTls;
    }

    /**
     * @return mixed
     */
    public function getEnableLdap()
    {
        return $this->enableLdap;
    }

    /**
     * @return mixed
     */
    public function getUsernameAttribute()
    {
        return $this->username;
    }
    /**
     * @return mixed
     */
    public function getEmailAttribute()
    {
        return $this->email;
    }
    /**
     * @return mixed
     */
    public function getNameAttribute()
    {
        return $this->name;
    }
    /**
     * @return mixed
     */
    public function getProfileAttributes()
    {
        return $this->userProfileAttributes;
    }

    /**
     * @return mixed
     */
    public function getFieldAttributes()
    {
        return $this->userFieldAttributes;
    }

    /**
     * @return mixed
     */
    public function getActiveDirectoryAttributes()
    {
        return $this->activeDirectoryAttributes;
    }

    /**
     * @return mixed
     */
    public function getTestUsername()
    {
        return $this->testUsername;
    }

    /**
     * @return mixed
     */
    public function getMappingValueDefault()
    {
        return $this->mappingValueDefault;
    }

    /**
     * @return mixed
     */
    public function getRoleMappingKeyValue()
    {
        return $this->roleMappingKeyValue;
    }

    /**
     * @return mixed
     */
    public function getRoleMappingGroupValue()
    {
        return $this->roleMappingGroupValue;
    }
    
    /**
     * @return mixed
     */
    public function getEnableRoleMapping()
    {
        return $this->enableRolemapping;
    }

        /**
     * @return mixed
     */
    public function getRoleMappingCount()
    {
        return $this->roleMappingCount;
    }

    /**
     * @return mixed
     */
    public function setLdapConnObject($ldapConn)
    {
        $this->ldapconn=$ldapConn;
    }

    /**
     * @return mixed
     */
    public function getLdapConnObject()
    {
        return $this->ldapconn;
    }

    public function getTestConfigDetails(){

        $adAttribues = isset($this->testConfigDetails['Details']) && !empty($this->testConfigDetails['Details'])? json_decode($this->testConfigDetails['Details'],true) : json_decode('{"mail":"john.doe@miniorange.com","cn":"John","sn":"John Doe","userprincipalname":"john.doe@example.com","modifytimestamp":"20230703123021.579Z","dn":"cn=john,dc=example,dc=com", "department":"Accounts", "company": "miniOrange"}');
        return $adAttribues;
    }

    public function getTestConfigUsername(){
        $userName = !empty($this->testConfigDetails) && isset($this->testConfigDetails['Name']) ? strtoupper($this->testConfigDetails['Name']) : "JOHN DOE";
        return $userName;
    }

    public function getEnableLoggers(): int
    {
        return $this->enable_Loggers;
    }

}
?>