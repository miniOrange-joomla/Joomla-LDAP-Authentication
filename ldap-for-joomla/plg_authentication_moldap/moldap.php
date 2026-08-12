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

require_once JPATH_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_miniorange_dirsync' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'mo_ldap_utility.php';
 require_once JPATH_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_miniorange_dirsync' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'dirsync_ldap_config.php';
 require_once JPATH_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_miniorange_dirsync' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'dirsync_handler.php';
 require_once JPATH_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_miniorange_dirsync' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'mo_ldap_constants.php';
require_once JPATH_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_miniorange_dirsync' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'MoLdapLogger.php';

if (defined('_JEXEC'))
{
	/**
	 * This block of code is not used.
	 */
	class PlgAuthenticationMoldap extends CMSPlugin
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


		public function onUserAuthenticate(array $credentials, array $options, object &$response )
		{

			$app = Factory::getApplication('site');
			$uname = trim($credentials['username']);
			$upasswd = isset($credentials['password']) ? $credentials['password'] : '';

			if ($upasswd == '' || $upasswd == ' ')
			{
				$response->status = Authentication::STATUS_FAILURE;
				$app->enqueueMessage('Kindly please enter the password.', 'warning');

				return;
			}

			$ldapServerConfig = new MoLdapConstants;

			if ($ldapServerConfig->getEnableLdap() == 'ch')
			{
				$currentTime = date('Y-m-d H:i:s');
				$ipAddress = $_SERVER['REMOTE_ADDR'];
				$validatefilter = InputFilter::getInstance();
				$username = $validatefilter->clean($uname, 'username');

				// NOT VALIDATING PASSWORDS AS THEY MIGHT CONTAIN SPECIAL CHARACTERS
				$password = $upasswd;

				$serverName = $ldapServerConfig->getServerURL();
				$ldapHostname = $validatefilter->clean($serverName, 'string');
				$ldapServerConfig->setLdapConnObject(self::getConnection($ldapHostname));

				if ($ldapServerConfig->getLdapConnObject())
				{
					$searchFilter = $ldapServerConfig->getSearchFilter();
					$filterName = ldap_escape($username, "", LDAP_ESCAPE_FILTER);
					$searchFilter = '(&(' . $searchFilter . '=?)(|(objectClass=user)(objectClass=person)))';
					$filter = str_replace('?', $filterName, $searchFilter);

					$bind = @ldap_bind($ldapServerConfig->getLdapConnObject(), $ldapServerConfig->getBindDN(), $ldapServerConfig->getBindDNPassword());

					if (!$bind)
					{
						$app->enqueueMessage('<strong>MOLDAP A01:</strong> ' . Text::_('PLG_AUTHENTICATION_LDAP_CONTACT_ADMIN'), 'warning');
						MoLdapLogger::addLog('LDAP bind failed.', 'ERROR');
						$response->status = Authentication::STATUS_FAILURE;

						return;
					}

					$err = ldap_error($ldapServerConfig->getLdapConnObject());
					$errorNumber = ldap_errno($ldapServerConfig->getLdapConnObject());
					$errormessage = MoLdapUtility::moLdapErrorType($errorNumber);

					if ($errormessage != 'COM_MINIORANGE_SUCCESSFUL_CONNECTION')
					{
						$response->status = Authentication::STATUS_FAILURE;

						return;
					}

					if (strtolower($err) == 'success')
					{
						$userSearchResult = ldap_search($ldapServerConfig->getLdapConnObject(), $ldapServerConfig->getSearchBase(), $filter, array('*', '+'));

						if ($userSearchResult)
						{
							$info = ldap_first_entry($ldapServerConfig->getLdapConnObject(), $userSearchResult);
							$entry = ldap_get_entries($ldapServerConfig->getLdapConnObject(), $userSearchResult);

							if (!$info)
							{
								return;
							}

							$userAuth = @ldap_bind($ldapServerConfig->getLdapConnObject(), $entry[0]['dn'], $upasswd);

							if (!$userAuth)
							{
								return;
							}

							$response->type = 'Ldap';

							// Assign the response error message using the core property name.
							$errorMessageProperty = 'error_message';
							$response->$errorMessageProperty = '';

							$usernameAttribute = $ldapServerConfig->getUsernameAttribute();

							if (empty($usernameAttribute))
							{
								$usernameAttribute = $ldapServerConfig->getSearchFilter();
							}

							$response->username = self::moLdapAttributeMapping($entry, $usernameAttribute, 'string');
							$response->email = self::moLdapAttributeMapping($entry, $ldapServerConfig->getEmailAttribute(), 'email');
							$response->fullname = self::moLdapAttributeMapping($entry, $ldapServerConfig->getNameAttribute(), 'string');

							if (empty($response->email))
							{
								$app->enqueueMessage('<strong>MOLDAP A02:</strong> ' . Text::_('PLG_AUTHENTICATION_LDAP_CONTACT_ADMIN'), 'warning');
								MoLdapLogger::addLog('User email not retrieved.', 'CRITICAL');
								$response->status = Authentication::STATUS_FAILURE;

								return;
							}

							if (!filter_var($response->email, FILTER_VALIDATE_EMAIL))
							{
								$app->enqueueMessage('<strong>MOLDAP A03:</strong> ' . Text::_('PLG_AUTHENTICATION_LDAP_CONTACT_ADMIN'), 'warning');
								MoLdapLogger::addLog('User email attribute that you have contacted is incorrect.', 'CRITICAL');
								$response->status = Authentication::STATUS_FAILURE;

								return;
							}

							if (empty($response->username))
							{
								$response->username = $username;
							}

							if (empty($response->fullname))
							{
								$app->enqueueMessage('<strong>MOLDAP A05:</strong> ' . Text::_('PLG_AUTHENTICATION_LDAP_CONTACT_ADMIN'), 'warning');
								MoLdapLogger::addLog('User\'s name not retrieved. Not getting user\'s name.', 'WARNING');
								$response->status = Authentication::STATUS_FAILURE;

								return;
							}

							$joomlaUserId = UserHelper::getUserId($response->username);

							if ($joomlaUserId == 0)
							{
								$joomlaUserId = UserHelper::getUserId($username);
							}

							if ($joomlaUserId == 0)
							{
								$app->enqueueMessage(
									Text::_('PLG_AUTHENTICATION_MOLDAP_USER_NOT_IN_JOOMLA'),
									'error'
								);
								MoLdapLogger::addLog(
									"User {$response->username} does not exist in Joomla. LDAP login requires an existing Joomla account.",
									'WARNING'
								);
								$response->status = Authentication::STATUS_FAILURE;

								return;
							}

							$joomlaUser = Factory::getUser($joomlaUserId);
							$response->username = $joomlaUser->username;

							if ((int) $joomlaUser->block === 1)
							{
								$app->enqueueMessage('<strong>Error:</strong> ' . Text::_('JERROR_NOLOGIN_BLOCKED'), 'error');
								MoLdapLogger::addLog('Login blocked for user ' . $response->username, 'WARNING');
								$response->status = Authentication::STATUS_FAILURE;

								return;
							}

							$userEmailExists = MoLdapUtility::moLdapFetchData('#__users', array('email' => $response->email), 'loadAssoc', array('email', 'username'));

							if (!empty($userEmailExists) && $userEmailExists['username'] != $response->username)
							{
								$app->enqueueMessage('<strong>MOLDAP A06:</strong> ' . Text::_('PLG_AUTHENTICATION_LDAP_CONTACT_ADMIN'), 'warning');
								MoLdapLogger::addLog('Username and Email mismatch.', 'CRITICAL');
								$response->status = Authentication::STATUS_FAILURE;

								return;
							}

							$userUsernameExists = MoLdapUtility::moLdapFetchData('#__users', array('username' => $response->email), 'loadAssoc', array('email', 'username'));

							if (!empty($userUsernameExists) && $userUsernameExists['email'] != $response->email)
							{
								$app->enqueueMessage('<strong>MOLDAP A06:</strong> ' . Text::_('PLG_AUTHENTICATION_LDAP_CONTACT_ADMIN'), 'warning');
								MoLdapLogger::addLog('Username and Email mismatch.', 'CRITICAL');
								$response->status = Authentication::STATUS_FAILURE;

								return;
							}

							if ($ldapServerConfig->getEnableRoleMapping())
							{
								$defaultGroup = $ldapServerConfig->getMappingValueDefault();

								if (empty($defaultGroup))
								{
									$app->enqueueMessage('<strong>MOLDAP A09:</strong> ' . Text::_('PLG_AUTHENTICATION_LDAP_CONTACT_ADMIN'), 'warning');
									MoLdapLogger::addLog('Group mapping is enabled but default group is not configured.', 'ERROR');
									$response->status = Authentication::STATUS_FAILURE;

									return;
								}

								if (!isset($joomlaUser->groups[8]))
								{
									MoLdapUtility::updateUserAlreadyExist($joomlaUserId);
									UserHelper::addUserToGroup($joomlaUserId, $defaultGroup);
								}
							}

							$loginAttemptData = MoLdapUtility::moLdapFetchData(
								'#__mo_ldap_login_attempts',
								array('ip_address' => $ipAddress, 'username' => $username)
							);

							if ($loginAttemptData)
							{
								$userBlockedUntil = $loginAttemptData['blocked_until'];
								$userLoginAttempts = $loginAttemptData['user_login_attempts'];

								if ($userLoginAttempts >= 5 && $userBlockedUntil && strtotime($currentTime) < strtotime($userBlockedUntil))
								{
									$app->enqueueMessage('<strong>Error:</strong> ' . Text::_('PLG_AUTH_LDAP_LOGIN_BLOCKED_MESSAGE'), 'error');
									$response->status = Authentication::STATUS_FAILURE;

									return;
								}
							}

							MoLdapUtility::moLdapUpdateData(
								'#__mo_ldap_login_attempts',
								array('user_login_attempts' => 0, 'blocked_until' => date('Y-m-d H:i:s', strtotime('+1 year'))),
								array('ip_address' => $ipAddress, 'username' => $username)
							);

							if (!empty($response->fullname))
							{
								MoLdapUtility::moLdapUpdateData(
									'#__users',
									array('name' => $response->fullname),
									array('id' => $joomlaUserId)
								);
							}

							$app->setUserState('plg_authentication_moldap.ldap_login', 1);
							$response->status = Authentication::STATUS_SUCCESS;
							MoLdapLogger::addLog('Authentication successful for the user ' . $uname);

							return;
						}

						MoLdapLogger::addLog('LDAP search failed.', 'ERROR');
					}

					return;
				}

				MoLdapLogger::addLog('LDAP connection failed.', 'ERROR');
				$response->status = Authentication::STATUS_FAILURE;

				return;
			}
			else
			{
				$response->status = Authentication::STATUS_FAILURE;
				MoLdapLogger::addLog('You have not enabled LDAP Login.', 'NOTICE');
			}
		}


		public static function getConnection($serverName)
		{

			$ldapconn = @ldap_connect($serverName);

			if (!$ldapconn)
			{
						MoLdapLogger::addLog('LDAP connection failed.', 'ERROR');

				return false;
			}

			if (version_compare(PHP_VERSION, '5.3.0') >= 0)
			{
				ldap_set_option($ldapconn, LDAP_OPT_NETWORK_TIMEOUT, 5);
			}

			ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
			ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);
			$ldapServerConfig = new MoLdapConstants;
			$ignoreLdaps = $ldapServerConfig->getIgnoreCertificateState();
			$enableTls = $ldapServerConfig->getEnableTls();

			if ($ignoreLdaps == 'ch')
			{
				ldap_set_option(null, LDAP_OPT_X_TLS_REQUIRE_CERT, 0);
			}

			if ($enableTls == 'ch')
			{
				ldap_start_tls($ldapconn);
			}

			return $ldapconn;
		}

		public static function moLdapAttributeMapping($entry, $attributeMap,$attributeType)
		{

			$validatefilter = InputFilter::getInstance();
			$userAttribute = "";

			if (isset($attributeMap) && $attributeMap !== '')
			{
				$attributeMap = strtolower($attributeMap);

				if (!empty($entry[0][$attributeMap][0]))
				{
					$userAttribute = $validatefilter->clean($entry[0][$attributeMap][0], $attributeType);
				}
				elseif (!empty($entry[0]['userprincipalname'][0]))
				{
					$userAttribute = $validatefilter->clean($entry[0]['userprincipalname'][0], $attributeType);
				}
				elseif (!empty($entry[0]['mail'][0]))
				{
					$userAttribute = $validatefilter->clean($entry[0]['mail'][0], $attributeType);
				}
			}
			else
			{
				if (!empty($entry[0]['userprincipalname'][0]))
				{
					$userAttribute = $validatefilter->clean($entry[0]['userprincipalname'][0], $attributeType);
				}
				elseif (!empty($entry[0]['mail'][0]))
				{
					$userAttribute = $validatefilter->clean($entry[0]['mail'][0], $attributeType);
				}
			}

			return $userAttribute;

		}

		public function onUserAfterLogin($options)
		{
			$app = Factory::getApplication();

			if (!$app->getUserState('plg_authentication_moldap.ldap_login'))
			{
				return;
			}

			$app->setUserState('plg_authentication_moldap.ldap_login', null);
		}
	}
}

