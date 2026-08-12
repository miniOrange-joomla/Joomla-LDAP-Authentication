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
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Router\Route;
use Joomla\CMS\User\UserHelper;

$document = Factory::getApplication()->getDocument();
$document->addScript(Uri::base() . 'components/com_miniorange_dirsync/assets/js/jquery.1.11.0.min.js');
$document->addScript(Uri::base() . 'components/com_miniorange_dirsync/assets/js/utilityjs.js');
$document->addStyleSheet(Uri::base() . 'components/com_miniorange_dirsync/assets/css/miniorange_boot.css');
$document->addStyleSheet(Uri::base() . 'components/com_miniorange_dirsync/assets/css/miniorange_license.css');
require_once JPATH_ADMINISTRATOR . '/components/com_miniorange_dirsync/helpers/mo_customer_setup.php';

class MiniorangeDirsyncControllerAccountsetup extends FormController
{
	public function __construct()
	{
		$this->viewList = 'accountsetup';
		parent::__construct();
	}

	public function moLdapSavelogin()
	{
		$app = Factory::getApplication();
		$user = $app->getIdentity();

		// CHECKING THE USER PERMISSIONS
		if ($user->authorise('core.edit', 'com_miniorange_dirsync'))
		{
			$post = MoLdapUtility::moLdapGetApplicationInput($app)->post->getArray();

			$databaseName = '#__miniorange_dirsync_config';

			if ($post == null)
			{
				$ldapLogin = "";
			}
			else
			{
				// Convert toggle value ('1') to 'ch' for enabled, empty for disabled
				$ldapLogin = (isset($post['mo_ldap_login']) && $post['mo_ldap_login'] == '1') ? 'ch' : '';
			}

			$updatefieldsarray = array(
				'ldap_login' => $ldapLogin,
			);

			MoLdapUtility::moLdapUpdateData($databaseName, $updatefieldsarray, array('id' => '1'));

			$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=signinsettings', Text::_('COM_MINIORANGE_ENABLE_LOGIN_SAVED'));
		}
		else
		{
			$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=signinsettings', Text::_('COM_MINIORANGE_MULTI_NO_PERMISSION_TO_SAVE'), 'error');
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
		if (!$user->authorise('core.edit', 'com_miniorange_dirsync'))
		{
			$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=moLoggers', Text::_('COM_MINIORANGE_MULTI_NO_PERMISSION_TO_SAVE'), 'error');

			return;
		}

		// Get the value from form
		$input = MoLdapUtility::moLdapGetApplicationInput();
		$isEnabled = $input->getBool('mo_ldap_logger_toggle', 0);

		try
		{
			// Update the DB
			$db = MoDatabaseHelper::getDb();
			$query = $db->getQuery(true)
				->update($db->quoteName('#__miniorange_dirsync_config'))
				->set($db->quoteName('mo_ldap_enable_logger') . ' = ' . $db->quote($isEnabled ? 1 : 0))
				->where('id = 1');

			$db->setQuery($query)->execute();

			$message = $isEnabled ? 'COM_MINIORANGE_LOGGER_ENABLED_SUCCESS' : 'COM_MINIORANGE_LOGGER_DISABLED_MESSAGE';
			$messageType = 'message';
		}
		catch (Exception $e)
		{
			$message = 'COM_MINIORANGE_LOGGER_TOGGLE_ERROR';
			$messageType = 'error';
		}

		// Redirect back to the logs page
		$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=moLoggers', Text::_($message), $messageType);
	}

	/**
	 * Reset the LDAP authentication logs
	 *
	 * @return void
	 */
	public function resetLogs(): void
	{
		$db = MoDatabaseHelper::getDb();

		$query = $db->getQuery(true)
			->delete($db->quoteName('#__mo_ldap_logs'))
			->where('1=1');

		$db->setQuery($query);
		$db->execute();

		$this->setRedirect(
			'index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=moLoggers',
			Text::_('COM_MINIORANGE_LOGGER_RESET_MESSAGE'),
			'message'
		);
	}

	/**
	 * Downloads LDAP logs as a CSV file if logs are available.
	 *
	 * @return void
	 *
	 * @throws Exception If there is a database error or other unexpected issue.
	 */
	public function downloadLogs(): void
	{
		// Get Joomla database object
		$db = MoDatabaseHelper::getDb();
		$query = $db->getQuery(true)
			->select('*')
			->from($db->quoteName('#__mo_ldap_logs'))
			->order('timestamp DESC');
		$db->setQuery($query);
		$logs = $db->loadObjectList();

		// Check if logs are available
		if (empty($logs))
		{
			$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=moLoggers', Text::_('COM_MINIORANGE_LOGGER_DOWNLOAD_ERROR'), 'warning');

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
		fputcsv($output, array('Timestamp', 'Log Level', 'Code', 'Message'));

		// Write log data
		foreach ($logs as $log)
		{
			$logData = json_decode($log->message, true);

			if (!is_array($logData))
			{
				$logData = array('code' => '-', 'issue' => $log->message);
			}

			$logLevelName = get_object_vars($log)['log_level'] ?? '';

			fputcsv(
				$output,
				array(
					$log->timestamp,
					strtoupper($logLevelName),
					$logData['code'] ?? '-',
					$logData['issue'] ?? $log->message
				)
			);
		}

		fclose($output);
		jexit();
	}

	public function moLdapAttributeMapping()
	{
		$app = Factory::getApplication();
		$user = $app->getIdentity();

		// CHECKING THE USER PERMISSIONS
		if ($user->authorise('core.edit', 'com_miniorange_dirsync'))
		{
			$post = MoLdapUtility::moLdapGetApplicationInput($app)->post->getArray();

			$username = isset($post['username']) ? $post['username'] : '';
			$email = isset($post['email']) ? $post['email'] : '';
			$name = isset($post['name_attr']) ? $post['name_attr'] : '';

			if ($email == '' || $name == '')
			{
				$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=attributerolemapping', Text::_('COM_MINIORANGE_EMPTY_ATTRIBUTE_MAPPING'));

				return;
			}
			else
			{
				$databaseName = '#__miniorange_dirsync_config';
				$updatefieldsarray = array(
					'username' => $username,
					'email' => $email,
					'name' => $name,
					'ldap_login' => 'ch',
				);

				MoLdapUtility::moLdapUpdateData($databaseName, $updatefieldsarray, array('id' => '1'));
				MoLdapCustomer::moLdapSendEfficiencyTracking('Basic Mapping Saved Successfully. ');
				$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=attributerolemapping', Text::_('COM_MINIORANGE_ATTRIBUTE_MAPPING_SAVED_SUCCESSFULLY'));
			}
		}
		else
		{
			$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=attributerolemapping', Text::_('COM_MINIORANGE_MULTI_NO_PERMISSION_TO_SAVE'), 'error');
		}
	}

	public function moLdapSaveUserMapping($userAttributes, $ldapAttributes)
	{
		$attributeMapping = array();

		foreach ($userAttributes as $key => $value)
		{
			$trimmedValue = trim($value);
			$trimmedKey = trim($key);

			if (!empty($trimmedValue))
			{
				$ldapAttrValue = $ldapAttributes[$trimmedKey];
				$trimmedIaValue = trim($ldapAttrValue);

				if (!empty($trimmedIaValue))
				{
					$anArray = array();
					$anArray['attr_name'] = $trimmedValue;
					$anArray['attr_value'] = $trimmedIaValue;
					array_push($attributeMapping, $anArray);
				}
			}
		}

		$userAttributeNotNull = array();

		for ($i = 0; $i <= count($userAttributes) - 1; $i++)
		{
			if ($userAttributes[$i] != "")
			{
				array_push($userAttributeNotNull, $userAttributes[$i]);
			}
		}

		if (count($userAttributeNotNull) != 1)
		{
			$userAttributesCount = count($userAttributeNotNull);

			for ($i = 0; $i <= $userAttributesCount - 1; $i++)
			{
				$searchValue = $userAttributeNotNull[$i];

				for ($j = $i + 1; $j < $userAttributesCount; $j++)
				{
					$check = strcmp(trim($searchValue), trim($userAttributeNotNull[$j]));

					if ($check == 0)
					{
						return false;
					}
				}
			}
		}

		$attributeMapping = json_encode($attributeMapping);

		return $attributeMapping;
	}

	public function moLdapProfilemapping()
	{
		$app = Factory::getApplication();
		$post = MoLdapUtility::moLdapGetApplicationInput($app)->post->getArray();
		$validatefilter = InputFilter::getInstance();
		$app = Factory::getApplication();
		$user = $app->getIdentity();

		// CHECKING THE USER PERMISSIONS
		if ($user->authorise('core.edit', 'com_miniorange_dirsync'))
		{
			$userAttributes = array_key_exists('user_profile_attr_name', $post) ? $validatefilter->clean($post['user_profile_attr_name'], 'string') : array();
			$ldapAttributes = array_key_exists('user_profile_attr_value', $post) ? $validatefilter->clean($post['user_profile_attr_value'], 'string') : array();
			$attributeMapping = $this->moLdapSaveUserMapping($userAttributes, $ldapAttributes);

			if ($attributeMapping == false)
			{
				$message = Text::_('COM_MINIORANGE_DUPLICATE_USER_PROFILE_ATTRIBUTES1') . htmlspecialchars($userAttributes[0]) . Text::_('COM_MINIORANGE_DUPLICATE_USER_PROFILE_ATTRIBUTES2');
				$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=premium_features', $message, 'error');

				return;
			}
			else
			{
				$databaseName = '#__miniorange_dirsync_config';
				$updatefieldsarray = array(
					'user_profile_attributes' => $attributeMapping,
				);

				MoLdapUtility::moLdapUpdateData($databaseName, $updatefieldsarray, array('id' => '1'));
				$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=premium_features', Text::_('COM_MINIORANGE_USER_PROFILE_ATTRIBUTE_MAPPING_SAVED_SUCCESSFULLY'));

				return;
			}
		}
		else
		{
			$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=premium_features', Text::_('COM_MINIORANGE_MULTI_NO_PERMISSION_TO_SAVE'), 'error');
		}
	}

	public function moLdapFieldmapping()
	{
		$app = Factory::getApplication();
		$post = MoLdapUtility::moLdapGetApplicationInput($app)->post->getArray();
		$validatefilter = InputFilter::getInstance();
		$app = Factory::getApplication();
		$user = $app->getIdentity();

		// CHECKING THE USER PERMISSIONS
		if ($user->authorise('core.edit', 'com_miniorange_dirsync'))
		{
			$userAttributes = array_key_exists('user_field_attr_name', $post) ? $validatefilter->clean($post['user_field_attr_name'], 'string') : array();
			$ldapAttributes = array_key_exists('user_field_attr_value', $post) ? $validatefilter->clean($post['user_field_attr_value'], 'string') : array();
			$attributeMapping = $this->moLdapSaveUserMapping($userAttributes, $ldapAttributes);

			if ($attributeMapping == false)
			{
				$message = Text::_('COM_MINIORANGE_DUPLICATE_USER_FIELD_ATTRIBUTES1') . htmlspecialchars($userAttributes[0]) . Text::_('COM_MINIORANGE_DUPLICATE_USER_FIELD_ATTRIBUTES2');
				$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=premium_features', $message, 'error');

				return;
			}
			else
			{
				$databaseName = '#__miniorange_dirsync_config';
				$updatefieldsarray = array(
					'user_field_attributes' => $attributeMapping,
				);

				MoLdapUtility::moLdapUpdateData($databaseName, $updatefieldsarray, array('id' => '1'));
				$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=premium_features', Text::_('COM_MINIORANGE_USER_FIELD_ATTRIBUTE_MAPPING_SAVED_SUCCESSFULLY'));
			}
		}
		else
		{
			$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=premium_features', Text::_('COM_MINIORANGE_MULTI_NO_PERMISSION_TO_SAVE'), 'error');
		}
	}

	public function moLdapSaveRolemapping()
	{
		$app = Factory::getApplication();
		$user = $app->getIdentity();

		// CHECKING THE USER PERMISSIONS
		if ($user->authorise('core.edit', 'com_miniorange_dirsync'))
		{
			$post = MoLdapUtility::moLdapGetApplicationInput($app)->post->getArray();

			$databaseName = '#__miniorange_ldap_role_mapping';
			$updatefieldsarray = array(
				'mapping_value_default' => isset($post['mapping_value_default']) ? $post['mapping_value_default'] : '',
				'enable_ldap_role_mapping' => isset($post['enable_role_mapping']) ? $post['enable_role_mapping'] : '0',
			);

			MoLdapUtility::moLdapUpdateData($databaseName, $updatefieldsarray, array('id' => '1'));

			$result = MoLdapUtility::moLdapFetchData('#__miniorange_ldap_role_mapping', array('id' => '1'), 'loadAssoc');

			$enableRoleMapping = $result['enable_ldap_role_mapping'];

			$statusMessage = '';

			if (!$enableRoleMapping)
			{
				$statusMessage = Text::_('COM_MINIORANGE_CHECK_ENABLE_GROUP_MAPPING');
			}

			$message = Text::_('COM_MINIORANGE_GROUP_MAPPING_UPDATED');
			$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=attributerolemapping', $message . $statusMessage);
		}
		else
		{
			$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=attributerolemapping', Text::_('COM_MINIORANGE_MULTI_NO_PERMISSION_TO_SAVE'), 'error');
		}
	}

	public function moLdapSaveConfig()
	{
		$validatefilter = InputFilter::getInstance();
		$app = Factory::getApplication();
		$user = $app->getIdentity();

		// CHECKING THE USER PERMISSIONS
		if ($user->authorise('core.edit', 'com_miniorange_dirsync'))
		{
			$app = Factory::getApplication();
			$post = MoLdapUtility::moLdapGetApplicationInput($app)->post->getArray();
			$ldapConfigurationOption = isset($post['ldap_configuration_action']) ? $post['ldap_configuration_action'] : '';
			$ldapServerUrl = isset($post['mo_ldap_server_url']) ? trim($validatefilter->clean($post['mo_ldap_server_url'], 'string')) : '';
			$serviceAccountDn = isset($post['service_account_dn']) ? trim($validatefilter->clean($post['service_account_dn'], 'string')) : '';
			$serviceAccountPassword = isset($post['service_account_password']) ? $post['service_account_password'] : '';
			$moLdapDirectoryServerType = isset($post['mo_ldap_directory_server_type']) ? $post['mo_ldap_directory_server_type'] : '';
			$ldapType = isset($post['mo_ldap_type']) ? $post['mo_ldap_type'] : '';
			$ignoreLdaps = isset($post['mo_ignore_ldaps']) ? $post['mo_ignore_ldaps'] : '';
			$enableTls = isset($post['mo_enable_tls']) ? $post['mo_enable_tls'] : '';

			if (!MoLdapUtility::moLdapIsExtensionInstalled('ldap'))
			{
				$message = Text::_('COM_MINIORANGE_WARNING') . ' <a href="http://php.net/manual/en/curl.installation.php" target="_blank"> ' . Text::_('COM_MINIORANGE_CURL_EXTENSION') . '</a> ' . Text::_('COM_MINIORANGE_CURL_EXTENSION_DISABLED');
				$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration', $message, 'error');

				return;
			}

			if (empty($post['mo_ldap_port']))
			{
				$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration', Text::_('COM_MINIORANGE_LDAP_PORT_CANNOT_BE_EMPTY'), 'error');

				return;
			}

			if (empty($post['mo_ldap_server_url']))
			{
				$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration', Text::_('COM_MINIORANGE_LDAP_SERVER_URL_CANNOT_BE_EMPTY'), 'error');

				return;
			}

			if ($ldapType == 'STARTTLS')
			{
				$ldapServerUrl = 'ldap://' . $ldapServerUrl . ':' . $post['mo_ldap_port'];
			}
			else
			{
				$ldapServerUrl = $post['mo_ldap_type'] . '://' . $ldapServerUrl . ':' . $post['mo_ldap_port'];
			}

			$ldapServerUrl = MoLdapUtility::moLdapEncrypt($ldapServerUrl);

			if ($ldapConfigurationOption == 'ping_ldap_server')
			{
				$status = MoLdapConfig::moLdapPingLdapServer($ldapServerUrl, null, null, $ignoreLdaps, $enableTls);

				if ($status == "SUCCESS")
				{
					$databaseName = '#__miniorange_dirsync_config';
					$updatefieldsarray = array(
						'ldap_server_url' => $ldapServerUrl,
						'mo_ldap_directory_server_type' => $moLdapDirectoryServerType,
						'enable_dirsync_scheduler' => $ignoreLdaps,
						'enable_tls' => $enableTls,
					);

					// NEED TO ADD DEFAULT SEARCH BASE

					MoLdapUtility::moLdapUpdateData($databaseName, $updatefieldsarray, array('id' => '1'));

					$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration', Text::_('COM_MINIORANGE_CONNECTED_TO_AD_CONFIGURE_SERVICE_ACCOUNT'));

					return;
				}
				else
				{
					$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration', Text::_('COM_MINIORANGE_CONNECTION_FAILED'), 'error');
				}

				return;
			}

			if (empty($serviceAccountDn) || empty($serviceAccountPassword))
			{
				$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration', Text::_('COM_MINIORANGE_FILL_SERVICE_ACCOUNT_DETAILS'), 'error');

				return;
			}

			$serviceAccountDn = MoLdapUtility::moLdapEncrypt($serviceAccountDn);
			$serviceAccountPassword = MoLdapUtility::moLdapEncrypt($serviceAccountPassword);
			$status = MoLdapConfig::moLdapPingLdapServer($ldapServerUrl, $serviceAccountDn, $serviceAccountPassword, $ignoreLdaps, $enableTls);

			if ("SUCCESS" !== $status)
			{
				$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration', Text::_('COM_MINIORANGE_CONNECTION_SUCCESSFUL_VERIFY_SERVICE_ACCOUNT'), 'error');

				return;
			}

			$baseDn = MoLdapConfig::moLdapGetBaseDn($ldapServerUrl, $serviceAccountDn, $serviceAccountPassword, $ignoreLdaps, $enableTls);

			if ($baseDn != 'ERROR')
			{
				$searchBase = $baseDn;
			}
			else
			{
				$searchBase = "";
			}

			$databaseName = '#__miniorange_dirsync_config';
			$updatefieldsarray = array(
				'ldap_server_url' => $ldapServerUrl,
				'service_account_dn' => $serviceAccountDn,
				'service_account_password' => $serviceAccountPassword,
				'mo_ldap_directory_server_type' => $moLdapDirectoryServerType,
				'search_base' => MoLdapUtility::moLdapEncrypt($searchBase),
			);

			MoLdapUtility::moLdapUpdateData($databaseName, $updatefieldsarray, array('id' => '1'));

			$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration', Text::_('COM_MINIORANGE_CONNECTION_SUCCESSFUL_CONFIGURE_SEARCH_BASE'));
			MoLdapCustomer::moLdapSendEfficiencyTracking('LDAP Connection Setting Saved Successfully. ');
		}
		else
		{
			$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration', Text::_('COM_MINIORANGE_MULTI_NO_PERMISSION_TO_SAVE'), 'error');
			MoLdapCustomer::moLdapSendEfficiencyTracking('LDAP Connection Setting Failed. ');
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
		$db = MoDatabaseHelper::getDb();

		// Fetch current configuration to check if there is anything to reset
		$currentConfig = MoLdapUtility::moLdapFetchData('#__miniorange_dirsync_config', array('id' => '1'), 'loadAssoc');

		// Check if essential fields are empty
		$isEmpty = empty($currentConfig['ldap_server_url'])
			&& empty($currentConfig['service_account_dn'])
			&& empty($currentConfig['search_base']);

		if ($isEmpty)
		{
			$this->setRedirect(
				'index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration',
				Text::_('COM_MINIORANGE_RESET_NO_SETTINGS'),
				'warning'
			);

			return;
		}

		// Clear all LDAP configuration settings
		$updateFieldsArray = array(
			'ldap_server_url' => '',
			'mo_ldap_directory_server_type' => '',
			'service_account_dn' => '',
			'service_account_password' => '',
			'search_base' => '',
			'search_filter' => '',
			'ldap_test_username' => '',
			'username' => '',
			'email' => '',
			'name' => '',
			'ad_attribute_list' => '',
			'test_config_details' => '',
			'ldap_login' => '',
		);

		// Update or insert data using MoLdapUtility
		MoLdapUtility::moLdapUpdateData('#__miniorange_dirsync_config', $updateFieldsArray, array('id' => '1'));

		// Redirect back with success message
		$this->setRedirect(
			'index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration',
			Text::_('COM_MINIORANGE_RESET_SUCCESS_MESSAGE')
		);
	}

	public function moLdapSaveUserMappingConfig()
	{
		$validatefilter = InputFilter::getInstance();
		$app = Factory::getApplication();
		$user = $app->getIdentity();

		// CHECKING THE USER PERMISSIONS
		if ($user->authorise('core.edit', 'com_miniorange_dirsync'))
		{
			$app = Factory::getApplication();
			$post = MoLdapUtility::moLdapGetApplicationInput($app)->post->getArray();
			$searchBaseString = isset($post['search_base']) ? $validatefilter->clean($post['search_base'], 'string') : '';
			$searchFilter = isset($post['search_filter']) ? $validatefilter->clean($post['search_filter'], 'string') : '';

			if (empty($searchBaseString) || empty($searchFilter))
			{
				$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration', Text::_('COM_MINIORANGE_FILL_SEARCH_BASE_SEARCH_FILTER'), 'error');

				return;
			}

			$searchBaseString = MoLdapUtility::moLdapEncrypt($searchBaseString);
			$databaseName = '#__miniorange_dirsync_config';
			$updatefieldsarray = array(
				'search_base' => $searchBaseString,
				'search_filter' => $searchFilter,
			);
			MoLdapUtility::moLdapUpdateData($databaseName, $updatefieldsarray, array('id' => '1'));

			$link = Route::_('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration#mo_ldap_configuration_step3');

			$this->setRedirect(
				'index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration',
				Text::sprintf('COM_MINIORANGE_USER_MAPPING_SAVED_SUCCESSFULLY', $link)
			);
		}
		else
		{
			$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration', Text::_('COM_MINIORANGE_MULTI_NO_PERMISSION_TO_SAVE'), 'error');
		}
	}

	public function moLdapTestConfiguration()
	{
		$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=attributerolemapping');
	}

	public function moLdapTestAttributeMapping()
	{
		$app = Factory::getApplication();
		$user = $app->getIdentity();

		// CHECKING THE USER PERMISSIONS
		if ($user->authorise('core.edit', 'com_miniorange_dirsync'))
		{
			$result = MoLdapUtility::moLdapFetchData('#__miniorange_dirsync_config', array('id' => '1'), 'loadAssoc');

			if (empty($result['search_base']) || empty($result['search_filter']))
			{
				$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration', Text::_('COM_MINIORANGE_FILL_SEARCH_BASE_SEARCH_FILTER'), 'error');

				return;
			}

			$searchBase = MoLdapUtility::moLdapDecrypt($result['search_base']);
			$get = Factory::getApplication();
			$post = MoLdapUtility::moLdapGetApplicationInput($get)->post->getArray();

			$input = MoLdapUtility::moLdapGetApplicationInput($get);
			$username = trim($input->getString('test_attribute_username', ''));
			$password = $input->getString('test_attribute_password', '');

			// Simple styling using existing plugin patterns
			echo '<style>
			.mo_ldap_attr_success_message{color: #3c763d;background-color: #dff0d8; padding:1%;margin-bottom:10px;text-align:center; border:1px solid #AEDB9A; font-size:18pt;}
			.mo_ldap_test_unsuccessful{color: white;background-color: #e06d6d; padding:1%;margin-bottom:10px;text-align:center; border:1px solid #AEDB9A; font-size:18pt;}
			.mo_ldap_test_unsuccessful_details{margin-left:10px;padding:10px;border: 1px solid black;text-align:center}
			table {border-collapse: collapse; width: 90%; margin: 20px auto;}
			table, th, td {border: 1px solid #949090;}
			th {font-weight:bold; background-color: #f8f9fa; padding: 12px; text-align: center;}
			td {padding: 10px; word-wrap: break-word; vertical-align: top;}
			.search-input {width: 100%; max-width: 400px; padding: 8px; margin: 20px auto; display: block; border: 1px solid #ddd;}
		</style>';

			if (empty($username) || empty($password))
			{
				echo '<div class="mo_ldap_test_unsuccessful">' . Text::_('COM_MINIORANGE_TEST_UNSUCCESSFUL') . '</div>
				  <div class="mo_ldap_test_unsuccessful_details">' . Text::_('COM_MINIORANGE_USERNAME_PASSWORD_REQUIRED') . '</div>';
				exit;
			}

			$authResponse = MoLdapConfig::moLdapAuthenticateUser(trim($username), $password);

			if ($authResponse->statusMessage == "USER_NOT_EXIST")
			{
				echo '<div class="mo_ldap_test_unsuccessful">' . Text::_('COM_MINIORANGE_TEST_UNSUCCESSFUL') . '</div>
				  <div class="mo_ldap_test_unsuccessful_details">' . Text::_('COM_MINIORANGE_TEST_UNSUCCESSFUL_DETAILS1') . htmlspecialchars($username) . Text::_('COM_MINIORANGE_TEST_UNSUCCESSFUL_DETAILS2') . htmlspecialchars($username) . Text::_('COM_MINIORANGE_TEST_UNSUCCESSFUL_DETAILS3') . htmlspecialchars($result['search_filter']) . Text::_('COM_MINIORANGE_TEST_UNSUCCESSFUL_DETAILS4') . htmlspecialchars($searchBase) . Text::_('COM_MINIORANGE_TEST_UNSUCCESSFUL_DETAILS5') . '</div>';
				MoLdapCustomer::moLdapSendEfficiencyTracking('Test Authentication Failed.(User not exist in LDAP)');
				exit;
			}
			elseif ($authResponse->statusMessage == "BAD_SEARCH_FILTER")
			{
				echo '<div class="mo_ldap_test_unsuccessful">' . Text::_('COM_MINIORANGE_TEST_UNSUCCESSFUL') . '</div>
				  <div class="mo_ldap_test_unsuccessful_details">' . Text::_('COM_MINIORANGE_TEST_UNSUCCESSFUL_CHECK_USERNAME1') . htmlspecialchars($username) . Text::_('COM_MINIORANGE_TEST_UNSUCCESSFUL_CHECK_USERNAME2') . '</div>';
				MoLdapCustomer::moLdapSendEfficiencyTracking('Test Authentication Failed. (Bad Search Filter)');
				exit;
			}
			elseif ($authResponse->statusMessage == "LDAP_NOT_RESPONDING")
			{
				echo '<div class="mo_ldap_test_unsuccessful">' . Text::_('COM_MINIORANGE_TEST_UNSUCCESSFUL') . '</div>
				  <div class="mo_ldap_test_unsuccessful_details">' . Text::_('COM_MINIORANGE_LDAP_NOT_RESPONDING') . '</div>';
				MoLdapCustomer::moLdapSendEfficiencyTracking('Test Authentication Failed. (LDAP Not Responding)');
				exit;
			}
			elseif ($authResponse->statusMessage == "USER_PASSWORD_DOESNTMATCH")
			{
				echo '<div class="mo_ldap_test_unsuccessful">' . Text::_('COM_MINIORANGE_TEST_UNSUCCESSFUL') . '</div>
				  <div class="mo_ldap_test_unsuccessful_details">' . Text::_('COM_MINIORANGE_PASSWORD_MISMATCH') . '</div>';
				exit;
			}
			elseif ($authResponse->statusMessage == "SUCCESS")
			{
				MoLdapCustomer::moLdapSendEfficiencyTracking('Test Authentication Success. ');

				// Success - Display attributes
				error_reporting(E_ERROR | E_PARSE);

				// Get user attributes after successful authentication
				$userAttributes = MoLdapConfig::moLdapGetUserDetails(trim($username));

				if ($userAttributes && is_array($userAttributes) && isset($userAttributes[0]))
				{
					$authResponse->attributeList = $userAttributes[0];
				}
				else
				{
					$authResponse->attributeList = array();
				}

				$attributeList = array();
				$filteredAttributes = array();

				foreach ($authResponse->attributeList as $attribute => $value)
				{
					if (is_numeric($attribute) || $attribute === 'count')
					{
						continue;
					}

					array_push($attributeList, $attribute);
					$filteredAttributes[$attribute] = MoLdapUtility::formatLdapAttributeValue($attribute, $value);
				}

				$totalAttributes = count($filteredAttributes);

				echo '<div class="mo_ldap_attr_success_message">' . Text::_('COM_MINIORANGE_TEST_SUCCESSFUL') . '</div>';

				if (UserHelper::getUserId($username) == 0)
				{
					echo '<div class="mo_ldap_test_unsuccessful">' . Text::_('COM_MINIORANGE_TEST_UNSUCCESSFUL_DETAILS6') . '</div>';
				}

				echo '<input type="text" class="search-input" id="attributeSearch" placeholder="Search attributes..." onkeyup="filterAttributes()">';

				echo '<table id="attributeTable">
				<tr>
					<th style="width: 30%;">' . Text::_('COM_MINIORANGE_ATTRIBUTE_NAME') . '</th>
					<th style="width: 70%;">' . Text::_('COM_MINIORANGE_ATTRIBUTE_VALUE') . '</th>
				</tr>';

				foreach ($filteredAttributes as $attribute => $value)
				{
					if ($attribute === 'dn')
					{
						continue;
					}

					echo '<tr class="attribute-row" data-attribute="' . strtolower($attribute) . '">';
					echo '<td><strong>' . htmlspecialchars($attribute) . '</strong></td>';
					echo '<td>';

					if ($attribute == 'thumbnailphoto' && !empty($value))
					{
						$thumbValue = is_array($value) ? ($value[0] ?? '') : $value;
						echo '<img src="' . htmlspecialchars($value) . '" style="max-width: 60px; max-height: 60px; border-radius: 50%;" alt="User thumbnail">';
					}
					elseif ($attribute == 'memberOf' && is_array($value) && $value != "not available")
					{
						// Extract actual group values from LDAP array structure
						$actualGroups = array();

						for ($i = 0; $i < $value['count']; $i++)
						{
							if (isset($value[$i]))
							{
								$actualGroups[] = $value[$i];
							}
						}

						echo '<strong>Group Memberships (' . count($actualGroups) . '):</strong><br>';

						foreach ($actualGroups as $group)
						{
							echo '• ' . htmlspecialchars($group) . '<br>';
						}
					}
					elseif (is_array($value))
					{
						// Extract actual values from LDAP array structure
						$actualValues = array();

						for ($i = 0; $i < $value['count']; $i++)
						{
							if (isset($value[$i]))
							{
								$actualValues[] = $value[$i];
							}
						}

						if (count($actualValues) > 1)
						{
							echo '<strong>Multiple Values (' . count($actualValues) . '):</strong><br>';

							foreach ($actualValues as $index => $val)
							{
								echo '<strong>Value ' . ($index + 1) . ':</strong> ' . htmlspecialchars($val) . '<br>';
							}
						}
						elseif (count($actualValues) == 1)
						{
							echo htmlspecialchars($actualValues[0]);
						}
						else
						{
							echo 'No values';
						}
					}
					else
					{
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

				$adUserDetails = array_map('MoLdapUtility::convertBinaryToString', $authResponse->attributeList ? $authResponse->attributeList : array());
				$adDateInJson = json_encode($adUserDetails, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE);
				$userDetails = array();
				$userDetails['Name'] = $username;
				$userDetails['Details'] = ($adDateInJson);

				$databaseName = '#__miniorange_dirsync_config';
				$updatefieldsarray = array(
					'ad_attribute_list' => json_encode($attributeList),
					'ldap_login' => 'ch',
					'test_config_details' => json_encode($userDetails),
				);
				MoLdapUtility::moLdapUpdateData($databaseName, $updatefieldsarray, array('id' => '1'));
				exit;
			}
			else
			{
				echo '<div class="mo_ldap_test_unsuccessful">' . Text::_('COM_MINIORANGE_TEST_UNSUCCESSFUL') . '</div>
				  <div class="mo_ldap_test_unsuccessful_details">' . Text::_('COM_MINIORANGE_TEST_UNSUCCESSFUL_DETAILS1') . htmlspecialchars($username) . Text::_('COM_MINIORANGE_TEST_UNSUCCESSFUL_DETAILS2') . htmlspecialchars($username) . Text::_('COM_MINIORANGE_TEST_UNSUCCESSFUL_DETAILS3') . htmlspecialchars($result['search_filter']) . Text::_('COM_MINIORANGE_TEST_UNSUCCESSFUL_DETAILS4') . htmlspecialchars($searchBase) . Text::_('COM_MINIORANGE_TEST_UNSUCCESSFUL_DETAILS5') . '</div>';
				exit;
			}
		}
	}

	public function attributeMappingResults()
	{
		$validatefilter = InputFilter::getInstance();
		$app = Factory::getApplication();
		$user = $app->getIdentity();

		// CHECKING THE USER PERMISSIONS
		if ($user->authorise('core.edit', 'com_miniorange_dirsync'))
		{
			$moLdapServerDetails = new MoLdapConstants;
			$usernameAttr = $moLdapServerDetails->getUsernameAttribute();
			$emailAttr = $moLdapServerDetails->getEmailAttribute();
			$nameAttr = $moLdapServerDetails->getNameAttribute();
			$userFieldAttributes = $moLdapServerDetails->getFieldAttributes();
			$userProfileAttributes = $moLdapServerDetails->getProfileAttributes();

			if (empty($moLdapServerDetails->getSearchBase()) || empty($moLdapServerDetails->getSearchFilter()))
			{
				$message = Text::_('COM_MINIORANGE_FILL_SEARCH_BASE_SEARCH_FILTER');
				$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration', $message, 'error');

				return;
			}

			$get = MoLdapUtility::moLdapGetApplicationInput()->get->getArray();

			$username = isset($get['test_attribute_username']) ? $validatefilter->clean($get['test_attribute_username'], 'string') : '';
			$password = isset($get['test_attribute_password']) ? $get['test_attribute_password'] : '';

			if (empty($username) || empty($password))
			{
				$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=attributerolemapping', Text::_('COM_MINIORANGE_USERNAME_PASSWORD_REQUIRED'), 'error');

				return;
			}

			$authResponse = MoLdapConfig::moLdapAuthenticateUser(trim($username), $password);
			?>
			<style>.mo_ldap_test_premium_features{font-weight:bold;border:2px solid #949090;padding:2%;}
			.mo_ldap_test_unsuccessful{color:white;background-color: #e06d6d;padding:2%;margin-bottom:20px;text-align:center; border:1px solid #AEDB9A; font-size:18pt;}
			.mo_ldap_test_unsuccessful_message{margin-left:10px;padding:10px;border: 1px solid black;}
			</style>
			<?php
			if ($authResponse->statusMessage == "USER_NOT_EXIST")
			{
				$searchBase = $moLdapServerDetails->getSearchBase();
				$searchFilter = $moLdapServerDetails->getSearchFilter();
				$message = Text::_('COM_MINIORANGE_CANNOT_FIND_USER1') . $username . Text::_('COM_MINIORANGE_CANNOT_FIND_USER2') . $searchBase . Text::_('COM_MINIORANGE_CANNOT_FIND_USER3') . $searchFilter . Text::_('COM_MINIORANGE_CANNOT_FIND_USER4') . $searchBase . Text::_('COM_MINIORANGE_CANNOT_FIND_USER5');
				?><div class="mo_ldap_test_unsuccessful"><?php echo Text::_('COM_MINIORANGE_TEST_UNSUCCESSFUL');?></div>

			<div class="mo_ldap_test_unsuccessful_message">
				<?php echo $message;?>
			</div><?php
				exit;
			}
			elseif ($authResponse->statusMessage == "LDAP_NOT_RESPONDING")
			{
				?><div class="mo_ldap_test_unsuccessful"><?php echo Text::_('COM_MINIORANGE_TEST_UNSUCCESSFUL');?></div>

			<div class="mo_ldap_test_unsuccessful_message">
				<?php echo Text::_('COM_MINIORANGE_LDAP_NOT_RESPONDING_CHECK_CONGIG');?>
			</div><?php
				exit;
			}
			elseif ($authResponse->statusMessage == "USER_PASSWORD_DOESNTMATCH")
			{
				?><div class="mo_ldap_test_unsuccessful"><?php echo Text::_('COM_MINIORANGE_TEST_UNSUCCESSFUL');?></div>

			<div class="mo_ldap_test_unsuccessful_message">
				<?php echo Text::_('COM_MINIORANGE_PASSWORD_MISMATCH');?>
			</div><?php
				exit;
			}
			elseif ($authResponse->statusMessage == "SUCCESS")
			{
				$moLdapUserAttributes = MoLdapConfig::moLdapGetUserDetails($username);

				echo "<div style='color: #3c763d;	background-color: #dff0d8;text-align:center; border:1px solid #AEDB9A;'><h3>" . Text::_('COM_MINIORANGE_TEST_USER_ATTRIBUTE_MAPPING_DETAILS') . "</h3></div>
				<div class='mo_boot_mx-4'>
					<p><strong>" . Text::_('COM_MINIORANGE_TEST_USER_ATTRIBUTE_MAPPING_SUCCESSFULLY_AUTHENTICATED') . " <span style='color:red'>" . $username . "</span></strong></p>
					<br>" . Text::_('COM_MINIORANGE_TEST_USER_LOGIN_ATTRIBUTE_MAPPING_DETAILS') . "
					<table style='width:80%;margin:auto;text-align:center'>
						<tr style='text-align:center;'>
							<th style='font-weight:bold;border:2px solid #949090;padding:2%;background-color:#d5d3cd'>" . Text::_('COM_MINIORANGE_TEST_ATTRIBUTE_MAPPING_USER_LOGIN_ATTRIBUTE_NAME') . "</th>
							<th style='font-weight:bold;padding:2%;border:2px solid #949090; word-wrap:break-word;background-color:#d5d3cd'>" . Text::_('COM_MINIORANGE_TEST_ATTRIBUTE_MAPPING_USER_LOGIN_ATTRIBUTE_VALUE') . "</th>
						</tr>";

				if (isset($usernameAttr) || isset($nameAttr) || isset($emailAttr))
				{
					$username = isset($moLdapUserAttributes[0][$usernameAttr][0]) ? $moLdapUserAttributes[0][$usernameAttr][0] : Text::_('COM_MINIORANGE_TEST_ATTRIBUTE_MAPPING_RECHECK_VALUE');
					$name = isset($moLdapUserAttributes[0][$nameAttr][0]) ? $moLdapUserAttributes[0][$nameAttr][0] : Text::_('COM_MINIORANGE_TEST_ATTRIBUTE_MAPPING_RECHECK_VALUE');
					$email = isset($moLdapUserAttributes[0][$emailAttr][0]) ? $moLdapUserAttributes[0][$emailAttr][0] : Text::_('COM_MINIORANGE_TEST_ATTRIBUTE_MAPPING_RECHECK_VALUE');
					echo "<tr class='mo_ldap_test_premium_features'>
						<td class='mo_ldap_test_premium_features'>Username</td>
						<td class='mo_ldap_test_premium_features'>" . $username . "</td>
						</tr>
						<tr class='mo_ldap_test_premium_features'>
						<td class='mo_ldap_test_premium_features'>Name</td>
						<td class='mo_ldap_test_premium_features'>" . $name . "</td>
						</tr>
						<tr class='mo_ldap_test_premium_features'>
						<td class='mo_ldap_test_premium_features'>Email</td>
						<td class='mo_ldap_test_premium_features'>" . $email . "</td>
						</tr>
						</table><br>";
				}

				echo "<strong class='mo_boot_my-4' >" . Text::_('COM_MINIORANGE_TEST_ATTRIBUTE_MAPPING_NOTE') . "</strong>
				<br><br>
				<div class='mo_boot_my-4' style='color: #black;	background-color: #d8e7f0; text-align:center; border:1px solid #9aa0db;'>
					<h4>" . Text::_('COM_MINIORANGE_TEST_ATTRIBUTE_MAPPING_USER_PROFILE_ATTRIBUTE') . "</h4>
				</div>
				<p>" . Text::_('COM_MINIORANGE_TEST_ATTRIBUTE_MAPPING_USER_PROFILE_ATTRIBUTE_DETAILS') . "</p>
				<table style='width:80%;margin:auto;text-align:center'>
					<tr style='text-align:center;'>
						<th style='font-weight:bold;border:2px solid #949090;padding:2%;background-color:#d5d3cd'>" . Text::_('COM_MINIORANGE_TEST_ATTRIBUTE_MAPPING_USER_PROFILE_ATTRIBUTE_NAME') . "</th>
						<th style='font-weight:bold;padding:2%;border:2px solid #949090; word-wrap:break-word;background-color:#d5d3cd'>" . Text::_('COM_MINIORANGE_TEST_ATTRIBUTE_MAPPING_USER_PROFILE_ATTRIBUTE_VALUE') . "</th>
					</tr>";

				if (isset($userProfileAttributes[0]['attr_name']))
				{
					$profileValue1 = isset($moLdapUserAttributes[0][$userProfileAttributes[0]['attr_value']][0]) ? $moLdapUserAttributes[0][$userProfileAttributes[0]['attr_value']][0] : Text::_('COM_MINIORANGE_TEST_ATTRIBUTE_MAPPING_RECHECK_VALUE');
					echo "<tr class='mo_ldap_test_premium_features'>
						<td class='mo_ldap_test_premium_features'>" . $userProfileAttributes[0]['attr_name'] . "</td>
						<td class='mo_ldap_test_premium_features'>" . $profileValue1 . "</td>
						</tr>";

					if (isset($userProfileAttributes[1]['attr_name']))
					{
						$profileValue2 = isset($moLdapUserAttributes[0][$userProfileAttributes[1]['attr_value']][0]) ? $moLdapUserAttributes[0][$userProfileAttributes[1]['attr_value']][0] : Text::_('COM_MINIORANGE_TEST_ATTRIBUTE_MAPPING_RECHECK_VALUE');
						echo "<tr class='mo_ldap_test_premium_features'>
							<td class='mo_ldap_test_premium_features'>" . $userProfileAttributes[1]['attr_name'] . "</td>
							<td class='mo_ldap_test_premium_features'>" . $profileValue2 . "</td>
							</tr>";
					}
				}
				else
				{
					echo "<tr class='mo_ldap_test_premium_features'>
							<td class='mo_ldap_test_premium_features'>" . Text::_('COM_MINIORANGE_TEST_ATTRIBUTE_MAPPING_NOT_CONFIGURED') . "</td>
							<td class='mo_ldap_test_premium_features'>" . Text::_('COM_MINIORANGE_TEST_ATTRIBUTE_MAPPING_NOT_CONFIGURED') . "</td>
							</tr>";
				}

				echo "
				</table><br>
				<div class='mo_boot_my-4' style='color: black;background-color: #d8e7f0; text-align:center; border:1px solid #9aa0db;'>
					<h4>" . Text::_('COM_MINIORANGE_TEST_ATTRIBUTE_MAPPING_USER_FIELD_ATTRIBUTE') . "</h4>
				</div>
				<p>" . Text::_('COM_MINIORANGE_TEST_ATTRIBUTE_MAPPING_USER_FIELD_ATTRIBUTE_DETAILS') . "</p>
				<table style='width:80%;margin:auto;text-align:center'>
					<tr style='text-align:center;'>
						<th style='font-weight:bold;border:2px solid #949090;padding:2%;background-color:#d5d3cd'>" . Text::_('COM_MINIORANGE_TEST_ATTRIBUTE_MAPPING_USER_FIELD_ATTRIBUTE_NAME') . "</th>
						<th style='font-weight:bold;padding:2%;border:2px solid #949090; word-wrap:break-word;background-color:#d5d3cd'>" . Text::_('COM_MINIORANGE_TEST_ATTRIBUTE_MAPPING_USER_FIELD_ATTRIBUTE_VALUE') . "</th>
					</tr>";

				if (isset($userFieldAttributes[0]['attr_name']))
				{
					$fieldValue1 = isset($moLdapUserAttributes[0][$userFieldAttributes[0]['attr_value']][0]) ? $moLdapUserAttributes[0][$userFieldAttributes[0]['attr_value']][0] : Text::_('COM_MINIORANGE_TEST_ATTRIBUTE_MAPPING_RECHECK_VALUE');
					echo "<tr class='mo_ldap_test_premium_features'>
						<td class='mo_ldap_test_premium_features'>" . $userFieldAttributes[0]['attr_name'] . "</td>
						<td class='mo_ldap_test_premium_features'>" . $fieldValue1 . "</td>
						</tr>";

					if (isset($userFieldAttributes[1]['attr_name']))
					{
						$fieldValue2 = isset($moLdapUserAttributes[0][$userFieldAttributes[1]['attr_value']][0]) ? $moLdapUserAttributes[0][$userFieldAttributes[1]['attr_value']][0] : Text::_('COM_MINIORANGE_TEST_ATTRIBUTE_MAPPING_RECHECK_VALUE');
						echo "<tr class='mo_ldap_test_premium_features'>
							<td class='mo_ldap_test_premium_features'>" . $userFieldAttributes[1]['attr_name'] . "</td>
							<td class='mo_ldap_test_premium_features'>" . $fieldValue2 . "</td>
							</tr>";
					}
				}
				else
				{
					echo "<tr class='mo_ldap_test_premium_features'>
							<td class='mo_ldap_test_premium_features'>" . Text::_('COM_MINIORANGE_TEST_ATTRIBUTE_MAPPING_NOT_CONFIGURED') . "</td>
							<td class='mo_ldap_test_premium_features'>" . Text::_('COM_MINIORANGE_TEST_ATTRIBUTE_MAPPING_NOT_CONFIGURED') . "</td>
							</tr>";
				}

				echo "</table>
				<br><br>";

				$moLdapDefaultRole = $moLdapServerDetails->getMappingValueDefault();
				$moLdapRoleMappingKeyValue = $moLdapServerDetails->getRoleMappingKeyValue();
				$moLdapRoleMappingGroupvalue = $moLdapServerDetails->getRoleMappingGroupValue();

				$moLdapUserGroupDefined = 'group_not_found';
				$groupList = array();
				$moLdapUserGroupValue = array();
				$moLdapUserBelongsToGroup = array();

				$i = 1;

				if (gettype($moLdapRoleMappingKeyValue) == 'object')
				{
					foreach ($moLdapRoleMappingKeyValue as $keys)
					{
						if (!empty($moLdapUserAttributes[0]['memberof']))
						{
							if (in_array($keys, $moLdapUserAttributes[0]['memberof']))
							{
								$moLdapUserGroupDefined = 'group_found';
								array_push($moLdapUserGroupValue, $moLdapRoleMappingGroupvalue[$i]);
							}
						}

						$i++;
					}
				}

				if ($moLdapUserGroupDefined == 'group_not_found')
				{
					array_push($moLdapUserGroupValue, $moLdapDefaultRole);
				}

				for ($j = 0; $j < count($moLdapUserGroupValue); $j++)
				{
					switch ($moLdapUserGroupValue[$j])
					{
						case "2":
							$moLdapUserBelongsToGroup[$j] = "Registered";
							array_push($groupList, $moLdapUserBelongsToGroup[$j]);
							break;
						case "3":
							$moLdapUserBelongsToGroup[$j] = "Author";
							array_push($groupList, $moLdapUserBelongsToGroup[$j]);
							break;
						case "4":
							$moLdapUserBelongsToGroup[$j] = "Editor";
							array_push($groupList, $moLdapUserBelongsToGroup[$j]);
							break;
						case "5":
							$moLdapUserBelongsToGroup[$j] = "Publisher";
							array_push($groupList, $moLdapUserBelongsToGroup[$j]);
							break;
						case "6":
							$moLdapUserBelongsToGroup[$j] = "Manager";
							array_push($groupList, $moLdapUserBelongsToGroup[$j]);
							break;
						case "7":
							$moLdapUserBelongsToGroup[$j] = "Administrator";
							array_push($groupList, $moLdapUserBelongsToGroup[$j]);
							break;
					}
				}

				echo "<div style='color: black;background-color: #d8e7f0; text-align:center; border:1px solid #9aa0db;'><h3>" . Text::_('COM_MINIORANGE_TEST_GROUP_MAPPING_USER_GROUP_MAPPING_DETAILS') . "</h3></div>
				<div class='mo_boot_mx-4'>
					<p><strong>" . Text::_('COM_MINIORANGE_TEST_GROUP_MAPPING_SUCCESSFULLY_AUTHENTICATED') . " <span style='color:red'>" . $username . "</span></strong></p>
					<br>" . Text::_('COM_MINIORANGE_TEST_GROUP_MAPPING_DETAILS') . "


				<table style='width:80%;margin:auto;text-align:center'>
					<tr style='text-align:center;'>
						<th style='font-weight:bold;border:2px solid #949090;padding:2%;background-color:#d5d3cd'>" . Text::_('COM_MINIORANGE_TEST_GROUP_MAPPING_JOOMLA_SITE_ROLES') . "</th>
					</tr>";

				if ($groupList)
				{
					for ($i = 0; $i < count($groupList); $i++)
					{
						echo "<tr class='mo_ldap_test_premium_features'>
						<td class='mo_ldap_test_premium_features'>" . $groupList[$i] . "</td>
						</tr>";
					}
				}
				else
				{
					echo "<tr class='mo_ldap_test_premium_features'>
						<td class='mo_ldap_test_premium_features'>" . Text::_('COM_MINIORANGE_TEST_GROUP_MAPPING_NOT_CONFIGURED') . "</td>
						</tr>";
				}

				echo "</table></div>
			</div>";
				exit;
			}
			else
			{
				?><div class="mo_ldap_test_unsuccessful"><?php echo Text::_('COM_MINIORANGE_TEST_UNSUCCESSFUL');?></div>

			<div class="mo_ldap_test_unsuccessful_message">
				<?php echo Text::_('COM_MINIORANGE_LDAP_NOT_RESPONDING');?>
			</div><?php
				exit;
			}
		}
	}

	public function testConfigurations()
	{
		$validatefilter = InputFilter::getInstance();
		$app = Factory::getApplication();
		$user = $app->getIdentity();

		// CHECKING THE USER PERMISSIONS
		if ($user->authorise('core.edit', 'com_miniorange_dirsync'))
		{
			$result = MoLdapUtility::moLdapFetchData('#__miniorange_dirsync_config', array('id' => '1'), 'loadAssoc');

			if (empty($result['search_base']) || empty($result['search_filter']))
			{
				$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration', Text::_('COM_MINIORANGE_FILL_SEARCH_BASE_SEARCH_FILTER'), 'error');

				return;
			}

			$app = Factory::getApplication();
			$post = MoLdapUtility::moLdapGetApplicationInput($app)->post->getArray();
			$username = isset($post['test_username']) ? $validatefilter->clean($post['test_username'], 'string') : '';
			$password = isset($post['test_password']) ? $post['test_password'] : '';

			if (empty($username))
			{
				if (!empty($result['ldap_test_username']))
				{
					$username = $validatefilter->clean($result['ldap_test_username'], 'string');
				}
			}

			if (empty($username) || empty($password))
			{
				$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration', Text::_('COM_MINIORANGE_USERNAME_PASSWORD_REQUIRED'), 'error');

				return;
			}

			// SAVE USERNAME
			$databaseName = '#__miniorange_dirsync_config';
			$updatefieldsarray = array(
				'ldap_test_username' => $username,
			);
			MoLdapUtility::moLdapUpdateData($databaseName, $updatefieldsarray, array('id' => '1'));

			$authResponse = MoLdapUtility::saveTestAuthenticationAttributes(trim($username), $password);

			if ($authResponse->statusMessage == "SUCCESS")
			{
				$link = Route::_('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=attributerolemapping');
				$this->setRedirect(
					'index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration',
					Text::sprintf('COM_MINIORANGE_SUCCESSFUL_CONNECTED', $link)
				);
			}
			elseif ($authResponse->statusMessage == "USER_NOT_EXIST")
			{
				$searchBase = MoLdapUtility::moLdapDecrypt($result['search_base']);
				$searchFilter = $result['search_filter'];
				$message = Text::_('COM_MINIORANGE_CANNOT_FIND_USER1') . htmlspecialchars($username) . Text::_('COM_MINIORANGE_CANNOT_FIND_USER2') . htmlspecialchars($searchBase) . Text::_('COM_MINIORANGE_CANNOT_FIND_USER3') . htmlspecialchars($searchFilter) . Text::_('COM_MINIORANGE_CANNOT_FIND_USER4') . htmlspecialchars($searchBase) . Text::_('COM_MINIORANGE_CANNOT_FIND_USER5');
				$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration', $message, 'error');
			}
			elseif ($authResponse->statusMessage == "LDAP_NOT_RESPONDING")
			{
				$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration', Text::_('COM_MINIORANGE_LDAP_NOT_RESPONDING_CHECK_CONFIG'), 'error');
			}
			elseif ($authResponse->statusMessage == "USER_PASSWORD_DOESNTMATCH")
			{
				$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration', Text::_('COM_MINIORANGE_PASSWORD_MISMATCH'), 'error');
			}
			else
			{
				$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration', Text::_('COM_MINIORANGE_LDAP_NOT_RESPONDING'), 'error');
			}
		}
		else
		{
			$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration', Text::_('COM_MINIORANGE_MULTI_NO_PERMISSION_TO_SAVE'), 'error');
		}
	}

	public function moLdappsbsearchbases()
	{
		$app = Factory::getApplication();
		$user = $app->getIdentity();

		// CHECKING THE USER PERMISSIONS
		if ($user->authorise('core.edit', 'com_miniorange_dirsync'))
		{
			$result = MoLdapUtility::moLdapFetchData('#__miniorange_dirsync_config', array('id' => '1'), 'loadAssoc');
			$previousSearchBases = isset($result['search_base']) ? MoLdapUtility::moLdapDecrypt($result['search_base']) : "";
			$serverName = isset($result['ldap_server_url']) ? MoLdapUtility::moLdapDecrypt($result['ldap_server_url']) : "";

			if (empty($serverName))
			{
				?><div style="color: white;	background-color: #e06d6d; padding:2%;margin-bottom:20px;text-align:center; border:1px solid #AEDB9A; font-size:18pt;"><?php echo Text::_('COM_MINIORANGE_TEST_UNSUCCESSFUL');?></div>

			<div style="margin-left:10px;padding:10px;border: 1px solid black;">
				<?php echo Text::_('COM_MINIORANGE_ERROR_RETRIEVING_SEARCH_BASE');?>
			</div><?php
				exit;
			}

			$data = MoLdapConfig::moLdapPsbSearchBases();

			if ($data)
			{
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

				$limit = MoLdapUtility::moLdapGetApplicationInput($app)->post->getInt('limit', 10);
				$total = count($data);
				$totalPages = ceil($total / $limit);
				$currentPage = MoLdapUtility::moLdapGetApplicationInput($app)->get('page', 1, 'int');
				$startIndex = ($currentPage - 1) * $limit;
				$currentLimit = $limit;
				$htmlOutput = '';

				if (in_array($previousSearchBases, $data))
				{
					if ($previousSearchBases !== false)
					{
						$data = array_values(array_diff($data, array($previousSearchBases)));
						$htmlOutput .= "<div class='inputGroup list-group-item border rounded p-2 mb-2'>
							<input type='radio' name='select_ldap_search_bases' id='select_ldap_search_previous' class='form-check-input'value='{$previousSearchBases}' checked required><label for='select_ldap_search_previous' class='form-check-label ms-2'>" . htmlspecialchars($previousSearchBases) . "</label><br>
						  </div>";
						unset($previousSearchBases);
					}
				}

				for ($i = $startIndex; $i < min($startIndex + $limit, count($data)); $i++)
				{
					$htmlOutput .= "<div class='inputGroup list-group-item border rounded p-2 mb-2'>
							<input type='radio' name='select_ldap_search_bases' id='select_ldap_search_{$i}' class='form-check-input' value='{$data[$i]}' required><label for='select_ldap_search_{$i}' class='form-check-label ms-2'>" . htmlspecialchars($data[$i]) . "</label><br>
						  </div>";
				}

				echo '<div>';
				?>
			<form name="sbase" method="post" action="<?php echo Route::_('index.php?option=com_miniorange_dirsync&view=accountsetup&task=accountsetup.moLdapUpdatesearchbase'); ?>" id="updatesearchbase_form" class="form-horizontal">

				<input type="hidden" id="search_base_list_id" class="search_base_list_id" value='<?php echo json_encode($data); ?>'>
				<div class="form-group d-flex align-items-center mb-3">
					<label for="limit" class="mb-0 me-2"><?php echo Text::_('COM_MINIORANGE_DIRSYNC_SELECT_SEARCH_BASES_PER_PAGE'); ?></label>
					<select class="form-select me-3" name="limit" id="limit" onchange="updateSearchBaseLimit()" style="width: 15%">
						<option value="10" <?php echo ($currentLimit == 10) ? 'selected' : ''; ?>>10</option>
						<option value="25" <?php echo ($currentLimit == 25) ? 'selected' : ''; ?>>25</option>
						<option value="50" <?php echo ($currentLimit == 50) ? 'selected' : ''; ?>>50</option>
						<option value="100" <?php echo ($currentLimit == 100) ? 'selected' : ''; ?>>100</option>
					</select>

					<label for="search" class="mb-0 me-2"><?php echo Text::_('COM_MINIORANGE_DIRSYNC_SEARCH'); ?></label>
					<input type="text" id="search" onkeyup="filterSearchBases()" placeholder="Search for search bases..." class="form-control" style="width:40% ;">
				</div>

				<div id="search_base_results">
					<?php echo $htmlOutput; ?>
				</div>
				<?php
				$baseUrl = Route::_('index.php?option=com_miniorange_dirsync&view=accountsetup&task=accountsetup.moLdappsbsearchbases');

				echo '<div class="mo_boot_pagination" ' . ($limit >= $total ? 'style="display:none;"' : '') . '>';

				if ($currentPage > 1)
				{
					echo '<a href="' . $baseUrl . '&page=' . ($currentPage - 1) . '" class="btn btn-secondary" style="margin-right: 5px;">
		<span class="fas fa-arrow-left" aria-hidden="true"></span> ' . Text::_('COM_MINIORANGE_DIRSYNC_PREVIOUS') . '</a>';
				}

				for ($page = 1; $page <= $totalPages; $page++)
				{
					$activeClass = ($page == $currentPage) ? 'btn btn-primary' : 'btn btn-secondary';
					$style = ($page == $currentPage) ? 'margin: 0 5px; padding: 6px 12px;' : 'margin: 0 5px;';
					$pageLink = ($page == $currentPage)
						? "<span class=\"$activeClass\" style=\"$style\">$page</span>"
						: "<a href=\"$baseUrl&page=$page\" class=\"$activeClass\" style=\"$style\">$page</a>";
					echo $pageLink;
				}

				if ($currentPage < $totalPages)
				{
					echo '<a href="' . $baseUrl . '&page=' . ($currentPage + 1) . '" class="btn btn-secondary" style="margin-left: 5px;">' . Text::_('COM_MINIORANGE_DIRSYNC_NEXT') . '<span class="fas fa-arrow-right" aria-hidden="true"></span></a>';
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

	public function moLdapUpdatesearchbase()
	{
		$app = Factory::getApplication();
		$user = $app->getIdentity();

		// CHECKING THE USER PERMISSIONS
		if ($user->authorise('core.edit', 'com_miniorange_dirsync'))
		{
			$app = Factory::getApplication();
			$post = MoLdapUtility::moLdapGetApplicationInput($app)->post->getArray();

			$searchBaseString = $post['select_ldap_search_bases'];
			$searchBaseString = MoLdapUtility::moLdapEncrypt($searchBaseString);

			$databaseName = '#__miniorange_dirsync_config';
			$updatefieldsarray = array(
				'search_base' => $searchBaseString,
			);

			MoLdapUtility::moLdapUpdateData($databaseName, $updatefieldsarray, array('id' => '1'));
			$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration', Text::_('COM_MINIORANGE_SAVED_SEARCH_BASE'));
		}
		else
		{
			$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration', Text::_('COM_MINIORANGE_MULTI_NO_PERMISSION_TO_SAVE'), 'error');
		}
	}

	public function moLdapContactUs()
	{
		$app = Factory::getApplication();
		$post = MoLdapUtility::moLdapGetApplicationInput($app)->post->getArray();
		$queryEmail = isset($post['mo_ldap_query_email']) ? $post['mo_ldap_query_email'] : '';
		$query = isset($post['mo_ldap_query']) ? $post['mo_ldap_query'] : '';
		$queryType = isset($post['mo_ldap_setup_call_issue']) ? $post['mo_ldap_setup_call_issue'] : '';

		if (MoLdapUtility::moLdapCheckEmptyOrNull($queryType))
		{
			$queryTypeParam = MoLdapUtility::moLdapGetApplicationInput($app)->getString('query_type', '');

			if ($queryTypeParam === 'trial')
			{
				$queryType = Text::_('COM_MINIORANGE_TRIAL_REQUEST');
			}

			if ($queryTypeParam === 'configuration')
			{
				$queryType = Text::_('COM_MINIORANGE_CONFIGURATION_ISSUES');
			}
		}

		$queryWithconfig = isset($post['mo_ldap_query_withconfig']) ? $post['mo_ldap_query_withconfig'] : '';
		$attributes = MoLdapUtility::moLdapFetchData('#__miniorange_dirsync_config', array('id' => '1'), 'loadAssoc');

		if ($queryWithconfig != 1)
		{
			$attributes['search_filter'] = '';
			$attributes['username'] = '';
			$attributes['email'] = '';
		}

		if (MoLdapUtility::moLdapCheckEmptyOrNull($queryEmail) || MoLdapUtility::moLdapCheckEmptyOrNull($query))
		{
			$message = Text::_('COM_MINIORANGE_QUERY_WITH_EMAIL');
			$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=mo_ldap_trial_demo', $message, 'error');

			return;
		}
		else
		{
			$query = $post['mo_ldap_query'];
			$email = $post['mo_ldap_query_email'];
			$phone = isset($post['mo_ldap_query_phone']) ? $post['mo_ldap_query_phone'] : '';
			$user = Factory::getUser();

			// Prioritize JS captured timezone
			$timeZone = isset($post['mo_ldap_query_timezone']) ? $post['mo_ldap_query_timezone'] : '';

			if (empty($timeZone))
			{
				$timeZone = $user->getParam('timezone');
			}

			// Fallback to site timezone
			if (empty($timeZone))
			{
				$timeZone = Factory::getConfig()->get('offset');
			}

			// Final hard fallback (only if still empty)
			if (empty($timeZone))
			{
				$timeZone = 'UTC';
			}

			try
			{
				$date = new DateTime('now', new DateTimeZone($timeZone));
				$displayTimezone = $timeZone . ' (UTC' . $date->format('P') . ')';
			}
			catch (Exception $e)
			{
				$displayTimezone = 'UTC (UTC+00:00)';
			}

			$contactUs = new MoLdapCustomer;
			$submited = json_decode($contactUs->moLdapSubmitContactUs($email, $phone, $query, $attributes, $queryType, $displayTimezone), true);

			if (json_last_error() == JSON_ERROR_NONE)
			{
				if (is_array($submited) && array_key_exists('status', $submited) && $submited['status'] == 'ERROR')
				{
					$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=mo_ldap_trial_demo', $submited['message'], 'error');
				}
				else
				{
					if ($submited == false)
					{
						$message = Text::_('COM_MINIORANGE_QUERY_NOT_SUBMITTED');
						$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=mo_ldap_trial_demo', $message, 'error');
					}
					else
					{
						$message = Text::_('COM_MINIORANGE_QUERY_SENT');
						$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=mo_ldap_trial_demo', $message);
					}
				}
			}
			else
			{
				$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=mo_ldap_trial_demo', $submitted, 'error');
			}
		}
	}

	public function moLdapExport()
	{
		$ldapServerDetails = MoLdapUtility::moLdapFetchData('#__miniorange_dirsync_config', array('id' => '1'), 'loadAssoc');
		$ldapServerUrl = $ldapServerDetails['ldap_server_url'];
		$username = $ldapServerDetails['username'];

		if ($ldapServerUrl == '' && $username == '')
		{
			$message = Text::_('COM_MINIORANGE_FILL_ATTRIBUTE_MAPPING_SERVER_URL');
			$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration', $message, 'error');

			return;
		}

		foreach ($ldapServerDetails as $key => $value)
		{
			if ($key == 'ldap_server_url' || $key == 'service_account_dn' || $key == 'service_account_password' || $key == 'search_base')
			{
				$moLdapDecryptedValue = MoLdapUtility::moLdapDecrypt($value);
				$ldapServerDetails[$key] = $moLdapDecryptedValue;
			}
		}

		$ldapGroupMapping = MoLdapUtility::moLdapFetchData('#__miniorange_ldap_role_mapping', array('id' => '1'), 'loadAssoc');
		$ntlmConfiguration = MoLdapUtility::moLdapFetchData('#__miniorange_ntlm', array('id' => '1'), 'loadAssoc');
		$pluginConfiguration = array();
		array_unshift($ldapServerDetails, 'miniorange_dirsync_config');
		array_unshift($ldapGroupMapping, 'miniorange_ldap_role_mapping');
		array_unshift($ntlmConfiguration, 'miniorange_ntlm');
		array_push($pluginConfiguration, $ldapServerDetails, $ldapGroupMapping, $ntlmConfiguration);

		$fileContent = json_encode($pluginConfiguration, JSON_PRETTY_PRINT);

		header('Content-Disposition: attachment; filename=ldap-server.json');
		header('Content-Type: application/json');
		echo $fileContent;

		$message = Text::_('COM_MINIORANGE_EXPORT_SUCCESSFUL');
		$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration', $message);
		exit;
	}

	public function moLdapRequestForDemo()
	{
		$app = Factory::getApplication();
		$post = MoLdapUtility::moLdapGetApplicationInput($app)->post->getArray();

		if ((!isset($post['email'])) || (!isset($post['plan'])) || (!isset($post['description'])))
		{
			$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=mo_ldap_trial_demo');

			return;
		}

		$email = $post['email'];
		$plan = $post['plan'];
		$addOn = $post['add_on'];
		$description = trim($post['description']);
		$demo = 'Demo';

		if (!isset($plan) || empty($description))
		{
			$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=mo_ldap_trial_demo', Text::_('COM_MINIORANGE_FILL_PLAN_DETAILS_FOR_DEMO'), 'error');

			return;
		}

		$customer = new MoLdapCustomer;
		$response = json_decode($customer->moLdapRequestForDemo($email, $plan, $demo, $description, $addOn));

		if ($response->status != 'ERROR')
		{
			$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=mo_ldap_trial_demo', Text::_('COM_MINIORANGE_DEMO_REQUEST_RECIEVED_SUCCESSFULLY'));
		}
		else
		{
			$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=mo_ldap_trial_demo', Text::_('COM_MINIORANGE_CONTACT_US_AGAIN'), 'error');

			return;
		}
	}

	public function callContactUs()
	{
		$app = Factory::getApplication();
		$post = MoLdapUtility::moLdapGetApplicationInput($app)->post->getArray();

		if (count($post) == 0)
		{
			$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=mo_ldap_trial_demo');

			return;
		}

		$queryEmail = isset($post['mo_ldap_setup_call_email']) ? $post['mo_ldap_setup_call_email'] : '';
		$query = isset($post['mo_ldap_setup_call_issue']) ? $post['mo_ldap_setup_call_issue'] : '';
		$description = isset($post['mo_ldap_setup_call_desc']) ? $post['mo_ldap_setup_call_desc'] : '';
		$callDate = isset($post['mo_ldap_setup_call_date']) ? $post['mo_ldap_setup_call_date'] : '';
		$timeZone = isset($post['mo_ldap_setup_call_timezone']) ? $post['mo_ldap_setup_call_timezone'] : '';
		$queryWithconfig = isset($post['mo_ldap_query_withconfig']) ? $post['mo_ldap_query_withconfig'] : '';
		$attributes = MoLdapUtility::moLdapFetchData('#__miniorange_dirsync_config', array('id' => '1'), 'loadAssoc');

		if ($queryWithconfig != 1)
		{
			$attributes['search_filter'] = '';
			$attributes['username'] = '';
			$attributes['email'] = '';
		}

		if (MoLdapUtility::moLdapCheckEmptyOrNull($timeZone) || MoLdapUtility::moLdapCheckEmptyOrNull($callDate) || MoLdapUtility::moLdapCheckEmptyOrNull($queryEmail) || MoLdapUtility::moLdapCheckEmptyOrNull($description))
		{
			$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=mo_ldap_trial_demo', Text::_('COM_MINIORANGE_SUPPORT_FILL_ALL_FIELDS'), 'error');

			return;
		}
		else
		{
			$contactUs = new MoLdapCustomer;
			$submited = json_decode($contactUs->moLdapRequestForSetupCall($queryEmail, $query, $description, $callDate, $timeZone, $attributes), true);

			if (json_last_error() == JSON_ERROR_NONE)
			{
				if (is_array($submited) && array_key_exists('status', $submited) && $submited['status'] == 'ERROR')
				{
					$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=mo_ldap_trial_demo', $submited['message'], 'error');
				}
				else
				{
					if ($submited == false)
					{
						$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=mo_ldap_trial_demo', Text::_('COM_MINIORANGE_QUERY_NOT_SUBMITTED'), 'error');
					}
					else
					{
						$this->setRedirect('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=mo_ldap_trial_demo', Text::_('COM_MINIORANGE_QUERY_SENT'));
					}
				}
			}
		}
	}

	public function exportConfiguration()
	{
		$app  = Factory::getApplication();
		$user = $app->getIdentity();

		if (!$user->authorise('core.edit', 'com_miniorange_dirsync'))
		{
			$this->setRedirect(
				'index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration',
				Text::_('COM_MINIORANGE_MULTI_NO_PERMISSION_TO_SAVE'),
				'error'
			);

			return;
		}

		$this->checkToken('request') or jexit(Text::_('JINVALID_TOKEN'));

		$tableNames = array(
			'#__miniorange_ldap_customer',
			'#__miniorange_dirsync_config',
			'#__miniorange_ntlm',
			'#__miniorange_ldap_role_mapping',
		);

		JLoader::register('MoLdapUtility', JPATH_COMPONENT . '/helpers/mo_ldap_utility.php');

		MoLdapUtility::exportData($tableNames);
	}
}
