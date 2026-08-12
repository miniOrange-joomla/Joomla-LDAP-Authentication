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
// This class contains all the ldap functions

defined('_JEXEC') or die('Restricted access');

class MoLdapConfig
{

	public static function moLdapPingLdapServer($url,$ldapBindDn,$ldapBindPassword,$ignoreLdaps="", $enableTls="")
	{

		if (!MoLdapUtility::moLdapIsExtensionInstalled('ldap'))
		{
			return "LDAP_ERROR";
		}

		$url = MoLdapUtility::moLdapDecrypt($url);
		$ldapBindDn = isset($ldapBindDn) ? MoLdapUtility::moLdapDecrypt($ldapBindDn) : "";
		$ldapBindPassword = isset($ldapBindPassword) ? MoLdapUtility::moLdapDecrypt($ldapBindPassword) : "";
		$ldapconn = self::moLdapGetConnection($url, $ignoreLdaps, $enableTls);

		if ($ldapconn)
		{
			$ldapbind = @ldap_bind($ldapconn, $ldapBindDn, $ldapBindPassword);
			$err = ldap_error($ldapconn);

			if ($ldapbind)
			{
				return "SUCCESS";
			}
		}

		return "ERROR";
	}


	public static function moLdapSearchUserAttributes($username)
	{
		$username = stripcslashes($username);

		// Check if LDAP extension is installed
		if (!MoLdapUtility::moLdapIsExtensionInstalled('ldap'))
		{
			$authResponse = new Mo_Ldap_Auth_Response;
			$authResponse->status = false;
			$authResponse->statusMessage = 'LDAP_ERROR';
			$authResponse->userDn = '';

			return $authResponse;
		}

		$ldapServer = new MoLdapConstants;
		$ldapconn = self::moLdapGetConnection($ldapServer->getServerURL(), $ldapServer->getIgnoreCertificateState(), $ldapServer->getEnableTls());

		if (!$ldapconn)
		{
			$authResponse = new Mo_Ldap_Auth_Response;
			$authResponse->status = false;
			$authResponse->statusMessage = 'LDAP_NOT_RESPONDING';
			$authResponse->userDn = '';

			return $authResponse;
		}

		try
		{
			// Bind to LDAP server
			$bind = @ldap_bind($ldapconn, $ldapServer->getBindDN(), $ldapServer->getBindDNPassword());
			$err = ldap_error($ldapconn);

			if (strtolower($err) !== 'success')
			{
				ldap_unbind($ldapconn);
				$authResponse = new Mo_Ldap_Auth_Response;
				$authResponse->status = false;
				$authResponse->statusMessage = 'LDAP_NOT_RESPONDING';
				$authResponse->userDn = '';

				return $authResponse;
			}

			// Build search filter
			$searchFilter = $ldapServer->getSearchFilter();
			$searchFilter = '(&(' . $searchFilter . '=' . ldap_escape($username, '', LDAP_ESCAPE_FILTER) . ')(|(objectClass=user)(objectClass=person)))';

			error_reporting(E_ERROR | E_PARSE);

			// Perform LDAP search
			$result = @ldap_search($ldapconn, $ldapServer->getSearchBase(), $searchFilter, array('*','+'));
			$error = ldap_error($ldapconn);

			if ($error === "Bad search filter" || $error === "Invalid DN syntax")
			{
				ldap_unbind($ldapconn);
				$authResponse = new Mo_Ldap_Auth_Response;
				$authResponse->status = false;
				$authResponse->statusMessage = 'BAD_SEARCH_FILTER';
				$authResponse->userDn = '';

				return $authResponse;
			}

			if ($result === false)
			{
				ldap_unbind($ldapconn);
				$authResponse = new Mo_Ldap_Auth_Response;
				$authResponse->status = false;
				$authResponse->statusMessage = 'USER_NOT_EXIST';
				$authResponse->userDn = '';

				return $authResponse;
			}

			$entries = ldap_get_entries($ldapconn, $result);

			if ($entries['count'] === 0)
			{
				ldap_unbind($ldapconn);
				$authResponse = new Mo_Ldap_Auth_Response;
				$authResponse->status = false;
				$authResponse->statusMessage = 'USER_NOT_EXIST';
				$authResponse->userDn = '';

				return $authResponse;
			}

			// Process user attributes
			$userAttributes = array();
			$entry = $entries[0];

			foreach ($entry as $key => $value)
			{
				// Skip numeric keys and 'count' key
				if (is_numeric($key) || $key === 'count')
				{
					continue;
				}

				// Handle special attributes
				if ($key === 'thumbnailphoto' && isset($value[0]) && !empty($value[0]))
				{
					$base64Image = base64_encode($value[0]);
					$src = 'data:image/jpeg;base64,' . $base64Image;
					$userAttributes[$key] = $src;
				}
				// Handle AD timestamp attributes
				elseif (in_array($key, array('lastlogon', 'lastlogontimestamp', 'accountexpires', 'whencreated', 'whenchanged', 'badpasswordtime', 'pwdlastset')) && isset($value[0]) && is_numeric($value[0]) && $value[0] > 0)
				{
					// Convert Windows timestamp to readable date
					$userAttributes[$key] = date('D M d, Y @ H:i:s', ($value[0] / 10000000) - 11676009600);
				}
				// Handle multi-value attributes (like memberOf)
				elseif (isset($value['count']) && $value['count'] > 1)
				{
					$multiValues = array();

					for ($i = 0; $i < $value['count']; $i++)
					{
						if (isset($value[$i]))
						{
							$multiValues[] = $value[$i];
						}
					}

					$userAttributes[$key] = $multiValues;
				}
				// Handle single-value attributes
				elseif (isset($value[0]))
				{
					$userAttributes[$key] = !empty($value[0]) ? $value[0] : 'empty';
				}
				else
				{
					$userAttributes[$key] = 'not available';
				}
			}

			ldap_unbind($ldapconn);

			$authResponse = new Mo_Ldap_Auth_Response;
			$authResponse->status = true;
			$authResponse->attributeList = $userAttributes;
			$authResponse->statusMessage = 'SUCCESS';

			return $authResponse;
		}
		catch (Exception $e)
		{
			if (isset($ldapconn))
			{
				ldap_unbind($ldapconn);
			}

			$authResponse = new Mo_Ldap_Auth_Response;
			$authResponse->status = false;
			$authResponse->statusMessage = 'ERROR';
			$authResponse->userDn = '';

			return $authResponse;
		}
	}


	public static function moLdapAuthenticateUser($username, $password)
	{

		$username = stripcslashes($username);
		$authStatus = null;

		if (!MoLdapUtility::moLdapIsExtensionInstalled('ldap'))
		{
			$authResponse = new Mo_Ldap_Auth_Response;
			$authResponse->status = false;
			$authResponse->statusMessage = 'LDAP_ERROR';
			$authResponse->userDn = '';

			return $authResponse;
		}

		$ldapServer = new MoLdapConstants;
		$ldapconn = self::moLdapGetConnection($ldapServer->getServerURL(), $ldapServer->getIgnoreCertificateState(), $ldapServer->getEnableTls());

		if ($ldapconn)
		{
			$searchFilter = $ldapServer->getSearchFilter();
			$searchFilter = '(&(' . $searchFilter . '=?)(|(objectClass=user)(objectClass=person)))';
			$filter = str_replace('?', $username, $searchFilter);
			$userSearchResult = null;
			$entry = null;
			$info = null;

			error_reporting(E_ERROR | E_PARSE);
			$bind = @ldap_bind($ldapconn, $ldapServer->getBindDN(), $ldapServer->getBindDNPassword());
			$err = ldap_error($ldapconn);

			// If the bind to the server is not complete
			if (strtolower($err) != 'success')
			{
				$authResponse = new Mo_Ldap_Auth_Response;
				$authResponse->status = false;
				$authResponse->statusMessage = 'LDAP_NOT_RESPONDING';
				$authResponse->userDn = '';

				return $authResponse;
			}

			if (ldap_search($ldapconn,  $ldapServer->getSearchBase(), $filter))
			{
				$userSearchResult = ldap_search($ldapconn,  $ldapServer->getSearchBase(), $filter, array('*','+'));
			}
			else
			{
				$authResponse = new Mo_Ldap_Auth_Response;
				$authResponse->status = false;
				$authResponse->statusMessage = 'USER_NOT_EXIST';
				$authResponse->userDn = '';

				return $authResponse;
			}

			$info = ldap_first_entry($ldapconn, $userSearchResult);
			$entry = ldap_get_entries($ldapconn, $userSearchResult);

			if ($info)
			{
				$userAuth = @ldap_bind($ldapconn, $entry[0]['dn'], $password);

				if ($userAuth)
				{
					$userDn = ldap_get_dn($ldapconn, $info);
					$authResponse = new Mo_Ldap_Auth_Response;
					$authResponse->status = true;
					$authResponse->statusMessage = 'SUCCESS';
					$authResponse->userDn = $userDn;

					return $authResponse;
				}
				else
				{
					$authResponse = new Mo_Ldap_Auth_Response;
					$authResponse->status = false;
					$authResponse->statusMessage = 'USER_PASSWORD_DOESNTMATCH';
					$authResponse->userDn = '';

					return $authResponse;
				}
			}
			else
			{
				$authResponse = new Mo_Ldap_Auth_Response;
				$authResponse->status = false;
				$authResponse->statusMessage = 'USER_NOT_EXIST';
				$authResponse->userDn = '';

				return $authResponse;
			}
		}
		else
		{
			$authResponse = new Mo_Ldap_Auth_Response;
			$authResponse->status = false;
			$authResponse->statusMessage = 'ERROR';
			$authResponse->userDn = '';

			return $authResponse;
		}
	}

	public static function moLdapGetBaseDn($url,$ldapBindDn,$ldapBindPassword,$ignoreLdaps="", $enableTls="")
	{

		if (!MoLdapUtility::moLdapIsExtensionInstalled('ldap'))
		{
			return "LDAP_ERROR";
		}

		$url = MoLdapUtility::moLdapDecrypt($url);
		$ldapBindDn = isset($ldapBindDn) ? MoLdapUtility::moLdapDecrypt($ldapBindDn) : "";
		$ldapBindPassword = isset($ldapBindPassword) ? MoLdapUtility::moLdapDecrypt($ldapBindPassword) : "";
		$ldapconn = self::moLdapGetConnection($url, $ignoreLdaps, $enableTls);

		if ($ldapconn)
		{
			$ldapbind = @ldap_bind($ldapconn, $ldapBindDn, $ldapBindPassword);

			if ($ldapbind)
			{
				error_reporting(E_ERROR | E_PARSE);
				$results = ldap_read($ldapconn, '', '(objectclass=*)', array('namingContexts'));
				$ldapEnteriesData = ldap_get_entries($ldapconn, $results);

				$basedn = $ldapEnteriesData[0]['namingcontexts'][0];

				$err = ldap_error($ldapconn);

				if ($ldapbind)
				{
					return $basedn;
				}
			}

			return "ERROR";
		}

	}
	public static function moLdapPsbSearchBases()
	{
		if (!MoLdapUtility::moLdapIsExtensionInstalled('ldap'))
		{
			return "LDAP_ERROR";
		}

		$ldapServer = new MoLdapConstants;

		$ldapconn = self::moLdapGetConnection($ldapServer->getServerURL(), $ldapServer->getIgnoreCertificateState(), $ldapServer->getEnableTls());
		$searchBaseList = array();

		if (!empty($ldapServer->getBindDNPassword()) && !empty($ldapServer->getBindDN()))
		{
			$bind = @ldap_bind($ldapconn, $ldapServer->getBindDN(), $ldapServer->getBindDNPassword());

			if ($bind)
			{
				error_reporting(E_ERROR | E_PARSE);
				$results = ldap_read($ldapconn, '', '(objectclass=*)', array('namingContexts'));
				$ldapEnteriesData = ldap_get_entries($ldapconn, $results);

				$basedn = $ldapEnteriesData[0]['namingcontexts'][0];
				$basednList = $ldapEnteriesData[0]['namingcontexts']['count'];

				for ($i = 0; $i < $basednList; $i++)
				{
					array_push($searchBaseList, $ldapEnteriesData[0]['namingcontexts'][$i]);
				}

				$ous = array("ou");
				$organizationalUnitList = ldap_search($ldapconn, $basedn, "ou=*", $ous);

				if ($organizationalUnitList)
				{
					$ousList = ldap_get_entries($ldapconn, $organizationalUnitList);

					for ($i = 0; $i < $ousList["count"]; $i++)
					{
						array_push($searchBaseList,  $ousList[$i]['dn']);
					}
				}
			}
		}

		return $searchBaseList;
	}

	public static function moLdapGetConnection($serverUrl, $ignoreLdaps="", $tlsConnection="")
	{

		if (!MoLdapUtility::moLdapIsExtensionInstalled('ldap'))
		{
			return "LDAP_ERROR";
		}

		$ldapconn = ldap_connect($serverUrl);

		if (!$ldapconn)
		{
			return false;
		}

		if (version_compare(PHP_VERSION, '5.3.0') >= 0)
		{
			ldap_set_option($ldapconn, LDAP_OPT_NETWORK_TIMEOUT, 5);
		}

		ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
		ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);
		ldap_set_option(null, LDAP_OPT_DEBUG_LEVEL, 7);

		if ($ignoreLdaps == "ch")
		{
			ldap_set_option(null, LDAP_OPT_X_TLS_REQUIRE_CERT, 0);
		}

		if ($tlsConnection == 'ch')
		{
			ldap_start_tls($ldapconn);
		}

		return $ldapconn;
	}

	public static function moLdapGetUserDetails($username)
	{

		if (!MoLdapUtility::moLdapIsExtensionInstalled('ldap'))
		{
			$authResponse = new Mo_Ldap_Auth_Response;
			$authResponse->status = false;
			$authResponse->statusMessage = 'LDAP_ERROR';
			$authResponse->userDn = '';

			return $authResponse;
		}

		$ldapServer = new MoLdapConstants;
		$ldapconn = self::moLdapGetConnection($ldapServer->getServerURL(), $ldapServer->getIgnoreCertificateState(), $ldapServer->getEnableTls());

		if ($ldapconn)
		{
			$searchFilter = $ldapServer->getSearchFilter();
			$searchFilter = '(&(objectClass=*)(' . $searchFilter . '=?))';
			$filter = str_replace('?', $username, $searchFilter);
			$userSearchResult = null;
			$entry = null;
			$info = null;

			$bind = @ldap_bind($ldapconn, $ldapServer->getBindDN(), $ldapServer->getBindDNPassword());
			$err = ldap_error($ldapconn);

			if (strtolower($err) != 'success')
			{
				$authResponse = new Mo_Ldap_Auth_Response;
				$authResponse->status = false;
				$authResponse->statusMessage = 'LDAP_NOT_RESPONDING';
				$authResponse->userDn = '';

				return $authResponse;
			}

			error_reporting(E_ERROR | E_PARSE);
			@ldap_search($ldapconn, $ldapServer->getSearchBase(), $filter);
				$error = ldap_error($ldapconn);

			if ($error == "Bad search filter")
			{
				$authResponse = new Mo_Ldap_Auth_Response;
				$authResponse->status = false;
				$authResponse->statusMessage = 'BAD_SEARCH_FILTER';
				$authResponse->userDn = '';

				return $authResponse;
			}

			if (ldap_search($ldapconn, $ldapServer->getSearchBase(), $filter))
			{
				$userSearchResult = ldap_search($ldapconn, $ldapServer->getSearchBase(), $filter, array('*','+'));
			}
			else
			{
				$authResponse = new Mo_Ldap_Auth_Response;
				$authResponse->status = false;
				$authResponse->statusMessage = 'USER_NOT_EXIST';
				$authResponse->userDn = '';

				return $authResponse;
			}

			$info = ldap_first_entry($ldapconn, $userSearchResult);
			$entry = ldap_get_entries($ldapconn, $userSearchResult);

			if (!$info)
			{
				return $info;
			}

			return $entry;
		}
		else
		{
			$authResponse = new Mo_Ldap_Auth_Response;
			$authResponse->status = false;
			$authResponse->statusMessage = 'ERROR';
			$authResponse->userDn = '';

			return $authResponse;
		}
	}
}

