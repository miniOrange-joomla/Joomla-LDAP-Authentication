<?php
/**
* @package     Joomla.Component
* @subpackage  com_miniorange_dirsync
*
* @author      miniOrange Security Software Pvt. Ltd.
* @copyright   Copyright (C) 2015 miniOrange (https://www.miniorange.com)
* @license     GNU General Public License version 3; see LICENSE.txt
* @contact     info@xecurify.com
*
*This class contains all the utility functions
*
**/
defined( '_JEXEC' ) or die( 'Restricted access' );
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
class MoLdapUtility{

	public static function mo_ldap_is_customer_registered() {
		
		$result=self::moLdapFetchData('#__miniorange_ldap_customer',array('id'=> '1'),'loadAssoc');

		$email=$result['email'];
		$customerKey=$result['customer_key'];
		$status=$result['registration_status'];
		if($email && $customerKey && is_numeric( trim($customerKey)) && $status=='SUCCESS'){
			return 1;
		} else{
			return 0;
		}
	}
	
	public static function mo_ldap_check_empty_or_null( $value ) {
		if( ! isset( $value ) || empty( $value ) ) {
			return true;
		}
		return false;
	}
	
	public static function mo_ldap_is_curl_installed() {
		if  (in_array  ('curl', get_loaded_extensions())) {
			return 1;
		} else 
			return 0;
	}
	
	public static function mo_ldap_get_hostname(){
		return 'https://login.xecurify.com';
	}	
	
	public static function mo_ldap_get_plugin_version()
	{
		$db=Factory::getDbo();
		$dbQuery=$db->getQuery(true)
		->select('manifest_cache')
		->from($db->quoteName('#__extensions'))
		->where($db->quoteName('element') . "=" . $db->quote('com_miniorange_dirsync'));
		$db->setQuery($dbQuery);
		$manifest=json_decode($db->loadResult());

		return($manifest->version);
	}
	
	public static function getJoomlaCmsVersion()
	{
		try {
			// Use the official Joomla CMS Version class (recommended method)
			$version = new \Joomla\CMS\Version();
			return $version->getShortVersion();
		} catch (Exception $e) {
			// Fallback: try to get from JVERSION constant (Joomla 3.x compatibility)
			if (defined('JVERSION')) {
				return JVERSION;
			}
			
			// Final fallback
			return 'Unknown';
		}
	}
		
	public static function mo_ldap_encrypt($str) {
		if(!self::mo_ldap_is_extension_installed('openssl')) {
			return;
		}
		
		$key=99189 ;
		return base64_encode(openssl_encrypt($str, 'aes-128-ecb', $key, OPENSSL_RAW_DATA));	
	}
		
	public static function mo_ldap_decrypt($value)
	{
		if(!self::mo_ldap_is_extension_installed('openssl')) {
			return;
		}
		$key=99189 ;
		$string=rtrim( openssl_decrypt(base64_decode($value), 'aes-128-ecb', $key, OPENSSL_RAW_DATA), "\0");
		return trim($string,"\0..\32");
	}
		
	
	public static function  mo_ldap_is_extension_installed($extension_name){
		if  (in_array  ($extension_name, get_loaded_extensions()))
			return 1;
		else
			return 0;
	}

	public static function mo_ldap_error_type($error_number){

        if(-1==$error_number){
            $message=Text::_('COM_MINIORANGE_ENTER_VALID_URL');
           return $message;
        }
		if(0==$error_number){
			$message=Text::_('COM_MINIORANGE_SUCCESSFUL_CONNECTION');
			return $message;
		}
           
       if(2==$error_number){
           $message=Text::_('COM_MINIORANGE_INVALID_RESPONSE');
           return $message;
       }

       if((3==$error_number) || (10301==$error_number)){
           $message=Text::_('COM_MINIORANGE_TIMELIMIT_EXCEEDED');
           return $message;
       }

       if(4==$error_number){
           $message=Text::_('COM_MINIORANGE_SIZELIMIT_EXCEEDED');
           return $message;
       }

       if(7==$error_number){
           $message=Text::_('COM_MINIORANGE_AUTHENTICATION_NOTSUPPORTED');
           return $message;
       }

       if(11==$error_number){
           $message=Text::_('COM_MINIORANGE_SERVERLIMIT_EXCEEDED');
           return $message;
       }

       if(49==$error_number){
           $message=Text::_('COM_MINIORANGE_ENTER_VALID_CREDENTIALS');
           return $message;
       }

       if(51==$error_number){
           $message=Text::_('COM_MINIORANGE_LDAP_SERVER_BUSY');
           return $message;
       }

       if(52==$error_number){
           $message=Text::_('COM_MINIORANGE_CANNOT_CONNECT_TO_SERVER');
           return $message;
       }

       if(10302==$error_number){
           $message=Text::_('COM_MINIORANGE_SERVER_REFUSED_CONNECTION');
           return $message;
       }
       if(10500==$error_number){
           $message=Text::_('COM_MINIORANGE_CHECK_SEARCH_FILTER');
           return $message;
       }	
       if(10305==$error_number){
           $message=Text::_('COM_MINIORANGE_CHECK_HOSTNAME');
           return $message;
       
        }	
       if(13==$error_number){
        $message=Text::_('COM_MINIORANGE_SECURE_SESSION');
        return $message;
        }	
        if(34==$error_number){
            $message=Text::_('COM_MINIORANGE_CHECK_DN_SYNTAX');
            return $message;
        }	

		$message=Text::_('COM_MINIORANGE_CONNECTION_FAILED');
        return $message;

    }
    public static function getUserGroupName($groupId)
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->select($db->quoteName('title'))
            ->from($db->quoteName('#__usergroups'))
            ->where($db->quoteName('id') . ' = ' . (int) $groupId);

        $db->setQuery($query);
        return $db->loadResult() ?: 'Unknown Group';
    }

    /**
     * Update user_already_exist column to 1 if it's currently 0
     *
     * @param int $userId The user's ID
     * @return bool True if updated, false otherwise
     */
    public static function updateUserAlreadyExist($userId): bool
    {
        // Get the database object
        $db = Factory::getDbo();

        // Query to get the current value of 'user_already_exist' for the user
        $query = $db->getQuery(true);
        $query->select($db->quoteName('user_already_exist'))
            ->from($db->quoteName('#__users'))
            ->where($db->quoteName('id') . ' = ' . (int) $userId);

        $db->setQuery($query);
        $currentValue = (int) $db->loadResult();

        // If the current value is 0, update it to 1
        if ($currentValue === 0) {
            $query = $db->getQuery(true);
            $query->update($db->quoteName('#__users'))
                ->set($db->quoteName('user_already_exist') . ' = 1')
                ->where($db->quoteName('id') . ' = ' . (int) $userId);

            $db->setQuery($query);
            return $db->execute(); // Return whether the execution was successful
        }

        return false; // No update needed if current value is not 0
    }
	
	public static function mo_ldap_get_joomla_groups(){

		$db=Factory::getDbo();
		$db->setQuery($db->getQuery(true)
			->select('*')
			->from("#__usergroups")
		);
		return $db->loadRowList();
	}

	public static function moLdapUpdateData($tableName,$tableFields,$tableConditions){
		
		$db=Factory::getDbo();
		$query=$db->getQuery(true);
		$sanFields=array();
		foreach ($tableFields as $key=>$value){
			array_push($sanFields,$db->quoteName($key) . '=' . $db->quote($value));
		}

		$sanConditions=array();
		foreach ($tableConditions as $key=>$value){
			array_push($sanConditions,$db->quoteName($key) . '=' . $db->quote($value));
		}
		$query->update($db->quoteName($tableName))->set($sanFields)->where($sanConditions);
		$db->setQuery($query);
		$db->execute();

	}

	public static function moLdapFetchData($tableName,$condition=TRUE,$method='loadAssoc',$columns='*'){

		$db=Factory::getDbo();
		$query=$db->getQuery(true);
		$columns=is_array($columns)?$db->quoteName($columns):$columns;
		$query->select($columns);
		$query->from($db->quoteName($tableName));
        if($condition!==TRUE)
        {
            foreach ($condition as $key=>$value)
                $query->where($db->quoteName($key) . "=" . $db->quote($value));
        }

		$db->setQuery($query);
		if ($method=='loadColumn')
			return $db->loadColumn();
		else if($method=='loadObjectList')
			return $db->loadObjectList();
        else if($method=='loadObject')
            return $db->loadObject();
		else if($method=='loadResult')
			return $db->loadResult();
		else if($method=='loadRow')
			return $db->loadRow();
        else if($method=='loadRowList')
            return $db->loadRowList();
        else if($method=='loadAssocList')
            return $db->loadAssocList();
		else
			return $db->loadAssoc();
	}
	
	public static function mo_ldap_get_operating_system()
	{
	
		if (isset($_SERVER)) {
			$user_agent=$_SERVER['HTTP_USER_AGENT'];
		} else {
			global $HTTP_SERVER_VARS;
			if (isset($HTTP_SERVER_VARS)) {
				$user_agent=$HTTP_SERVER_VARS['HTTP_USER_AGENT'];
			} else {
				global $HTTP_USER_AGENT;
				$user_agent=$HTTP_USER_AGENT;
			}
		}
	
		$os_array=[
			'windows nt 10'=> 'Windows 10',
			'windows nt 6.3'=> 'Windows 8.1',
			'windows nt 6.2'=> 'Windows 8',
			'windows nt 6.1|windows nt 7.0'=> 'Windows 7',
			'windows nt 6.0'=> 'Windows Vista',
			'windows nt 5.2'=> 'Windows Server 2003/XP x64',
			'windows nt 5.1'=> 'Windows XP',
			'windows xp'=> 'Windows XP',
			'windows nt 5.0|windows nt5.1|windows 2000'=> 'Windows 2000',
			'windows me'=> 'Windows ME',
			'windows nt 4.0|winnt4.0'=> 'Windows NT',
			'windows ce'=> 'Windows CE',
			'windows 98|win98'=> 'Windows 98',
			'windows 95|win95'=> 'Windows 95',
			'win16'=> 'Windows 3.11',
			'mac os x 10.1[^0-9]'=> 'Mac OS X Puma',
			'macintosh|mac os x'=> 'Mac OS X',
			'mac_powerpc'=> 'Mac OS 9',
			'linux'=> 'Linux',
			'ubuntu'=> 'Linux - Ubuntu',
			'iphone'=> 'iPhone',
			'ipod'=> 'iPod',
			'ipad'=> 'iPad',
			'android'=> 'Android',
			'blackberry'=> 'BlackBerry',
			'webos'=> 'Mobile',
	
			'(media center pc).([0-9]{1,2}\.[0-9]{1,2})'=> 'Windows Media Center',
			'(win)([0-9]{1,2}\.[0-9x]{1,2})'=> 'Windows',
			'(win)([0-9]{2})'=> 'Windows',
			'(windows)([0-9x]{2})'=> 'Windows',
			'Win 9x 4.90'=> 'Windows ME',
			'(windows)([0-9]{1,2}\.[0-9]{1,2})'=> 'Windows',
			'win32'=> 'Windows',
			'(java)([0-9]{1,2}\.[0-9]{1,2}\.[0-9]{1,2})'=> 'Java',
			'(Solaris)([0-9]{1,2}\.[0-9x]{1,2}){0,1}'=> 'Solaris',
			'dos x86'=> 'DOS',
			'Mac OS X'=> 'Mac OS X',
			'Mac_PowerPC'=> 'Macintosh PowerPC',
			'(mac|Macintosh)'=> 'Mac OS',
			'(sunos)([0-9]{1,2}\.[0-9]{1,2}){0,1}'=> 'SunOS',
			'(beos)([0-9]{1,2}\.[0-9]{1,2}){0,1}'=> 'BeOS',
			'(risc os)([0-9]{1,2}\.[0-9]{1,2})'=> 'RISC OS',
			'unix'=> 'Unix',
			'os/2'=> 'OS/2',
			'freebsd'=> 'FreeBSD',
			'openbsd'=> 'OpenBSD',
			'netbsd'=> 'NetBSD',
			'irix'=> 'IRIX',
			'plan9'=> 'Plan9',
			'osf'=> 'OSF',
			'aix'=> 'AIX',
			'GNU Hurd'=> 'GNU Hurd',
			'(fedora)'=> 'Linux - Fedora',
			'(kubuntu)'=> 'Linux - Kubuntu',
			'(ubuntu)'=> 'Linux - Ubuntu',
			'(debian)'=> 'Linux - Debian',
			'(CentOS)'=> 'Linux - CentOS',
			'(Mandriva).([0-9]{1,3}(\.[0-9]{1,3})?(\.[0-9]{1,3})?)'=> 'Linux - Mandriva',
			'(SUSE).([0-9]{1,3}(\.[0-9]{1,3})?(\.[0-9]{1,3})?)'=> 'Linux - SUSE',
			'(Dropline)'=> 'Linux - Slackware (Dropline GNOME)',
			'(ASPLinux)'=> 'Linux - ASPLinux',
			'(Red Hat)'=> 'Linux - Red Hat',
			'(linux)'=> 'Linux',
			'(amigaos)([0-9]{1,2}\.[0-9]{1,2})'=> 'AmigaOS',
			'amiga-aweb'=> 'AmigaOS',
			'amiga'=> 'Amiga',
			'AvantGo'=> 'PalmOS',
			'[0-9]{1,2}\.[0-9]{1,2}\.[0-9]{1,3})'=> 'Linux',
			'(webtv)/([0-9]{1,2}\.[0-9]{1,2})'=> 'WebTV',
			'Dreamcast'=> 'Dreamcast OS',
			'GetRight'=> 'Windows',
			'go!zilla'=> 'Windows',
			'gozilla'=> 'Windows',
			'gulliver'=> 'Windows',
			'ia archiver'=> 'Windows',
			'NetPositive'=> 'Windows',
			'mass downloader'=> 'Windows',
			'microsoft'=> 'Windows',
			'offline explorer'=> 'Windows',
			'teleport'=> 'Windows',
			'web downloader'=> 'Windows',
			'webcapture'=> 'Windows',
			'webcollage'=> 'Windows',
			'webcopier'=> 'Windows',
			'webstripper'=> 'Windows',
			'webzip'=> 'Windows',
			'wget'=> 'Windows',
			'Java'=> 'Unknown',
			'flashget'=> 'Windows',
			'MS FrontPage'=> 'Windows',
			'(msproxy)/([0-9]{1,2}.[0-9]{1,2})'=> 'Windows',
			'(msie)([0-9]{1,2}.[0-9]{1,2})'=> 'Windows',
			'libwww-perl'=> 'Unix',
			'UP.Browser'=> 'Windows CE',
			'NetAnts'=> 'Windows',
		];
	
		$arch_regex='/\b(x86_64|x86-64|Win64|WOW64|x64|ia64|amd64|ppc64|sparc64|IRIX64)\b/ix';
		$arch=preg_match($arch_regex, $user_agent) ? '64' : '32';
	
		foreach ($os_array as $regex=> $value) {
			if (preg_match('{\b(' . $regex . ')\b}i', $user_agent)) {
				return $value . ' x' . $arch;
			}
		}
	
		return 'Unknown';
	}

	
	public static function convertBinaryToString($input) {
		if (is_string($input) && !mb_detect_encoding($input, 'utf-8', true)) {
			// Convert binary to base64
			return base64_encode($input);
		}
		return $input;
	}

    public static function exportData($tableNames)
    {
        $db = Factory::getDbo();
        $jsonData = [];

        if (empty($tableNames)) {
            $jsonData['error'] = 'No table names provided.';
        } else {
            foreach ($tableNames as $tableName) {
                $query = $db->getQuery(true);
                $query->select('*')
                      ->from($db->quoteName($tableName));

                $db->setQuery($query);
                try {
                    $data = $db->loadObjectList();
                    
                    if (empty($data)) {
                        $jsonData[$tableName] = ['message' => 'This table is empty.'];
                    } else {
                        $jsonData[$tableName] = $data;
                    }
                } catch (Exception $e) {
                    $jsonData[$tableName] = ['error' => $e->getMessage()];
                }
            }
        }

        // Generate filename with timestamp
        $filename = 'ldap_configuration_' . date('Y-m-d_H-i-s') . '.json';
        
        header('Content-disposition: attachment; filename=' . $filename);
        header('Content-type: application/json');
        header('Content-Length: ' . strlen(json_encode($jsonData, JSON_PRETTY_PRINT)));
        
        // Flush output to start download immediately
        if (ob_get_level()) {
            ob_end_flush();
        }
        
        echo json_encode($jsonData, JSON_PRETTY_PRINT);
        
        // Don't close application immediately - let the download complete naturally
        // This allows JavaScript to detect the download and reset the button state
    }

	public static function mo_ldap_get_details(String $tablename){
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('*');
		$query->from($db->quoteName($tablename));
		$query->where($db->quoteName('id')." = 1");
 
		$db->setQuery($query);
		$customer_details = $db->loadAssoc();
		return $customer_details;
	}
}
?>