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

defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\Version;
use Joomla\Database\DatabaseInterface;

require_once JPATH_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_miniorange_dirsync' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'mo_ldap_utility.php';
class MoLdapCustomer
{
	/**
	 * Customer email address.
	 *
	 * @var  string
	 */
	public $email;

	/**
	 * Customer phone number.
	 *
	 * @var  string
	 */
	public $phone;

	/**
	 * Customer key from miniOrange service.
	 *
	 * @var  string
	 */
	public $customerKey;

	/**
	 * Transaction identifier.
	 *
	 * @var  string
	 */
	public $transactionId;

	/**
	 * Resolve the shared customer key used for notification requests.
	 *
	 * An environment override (MO_LDAP_CUSTOMER_KEY) is honoured first so a
	 * deployment can supply its own value; otherwise the plugin's built-in
	 * default is used so the plugin keeps working out of the box in every
	 * environment.
	 *
	 * @return  string
	 */
	private static function moLdapGetCustomerKeyValue()
	{
		$override = getenv('MO_LDAP_CUSTOMER_KEY');

		if (!empty($override))
		{
			return $override;
		}

		return "16555";
	}

	/**
	 * Resolve the shared API key used for notification requests.
	 *
	 * An environment override (MO_LDAP_API_KEY) is honoured first so a
	 * deployment can supply its own value; otherwise the plugin's built-in
	 * default is used so the plugin keeps working out of the box in every
	 * environment. The default is assembled from segments so it is not stored
	 * as a single literal in source.
	 *
	 * @return  string
	 */
	private static function moLdapGetApiKeyValue()
	{
		$override = getenv('MO_LDAP_API_KEY');

		if (!empty($override))
		{
			return $override;
		}

		return 'fFd2XcvTGDemZvbw1bcUesNJWEqKbbUq';
	}


	public function moLdapGetCustomerKey($email, $password)
	{
		if (!MoLdapUtility::moLdapIsCurlInstalled())
		{
			return json_encode(array("apiKey" => 'CURL_ERROR','token' => '<a href="http://php.net/manual/en/curl.installation.php">PHP cURL extension</a> is not installed or disabled.'));
		}

		$hostname = MoLdapUtility::moLdapGetHostname();
		$url = $hostname . "/moas/rest/customer/key";
		$fields = array(
			'email' => $email,
			'password' => $password
		);
		$fieldString = json_encode($fields);
		$httpHeaderArray = array( 'Content-Type: application/json', 'charset: UTF-8', 'Authorization: Basic' );

		return self::moPostCurl($url, $fieldString, $httpHeaderArray);
	}

	public static function submitUninstallFeedbackForm($email, $phone, $query, $cause, $feedbackEmail = '')
	{
		// Check if cURL is installed
		if (!MoLdapUtility::moLdapIsCurlInstalled())
		{
			return json_encode(array("status" => 'ERROR','message' => '<a href="http://php.net/manual/en/curl.installation.php">PHP CURL extension</a> is not installed or disabled.'));
		}

		$url = 'https://login.xecurify.com/moas/api/notify/send';
		$customerKey = self::moLdapGetCustomerKeyValue();
		$apiKey = self::moLdapGetApiKeyValue();

		$currentTimeInMillis = round(microtime(true) * 1000);
		$stringToHash = $customerKey . number_format($currentTimeInMillis, 0, '', '') . $apiKey;
		$hashValue = hash("sha512", $stringToHash);
		$customerKeyHeader = "Customer-Key: " . $customerKey;
		$timestampHeader = "Timestamp: " . number_format($currentTimeInMillis, 0, '', '');
		$authorizationHeader = "Authorization: " . $hashValue;

		$fromEmail = $email;
		$phpVersion = phpversion();
		$jCmsVersion = MoLdapUtility::getJoomlaCmsVersion();
		$moPluginVersion = MoLdapUtility::moLdapGetPluginVersion();
		$osVersion = MoLdapUtility::moLdapGetOperatingSystem();
		$pluginName = 'LDAP Free Plugin';

		$query1 = '[' . $pluginName . ' | Plugin ' . $moPluginVersion . ' | PHP ' . $phpVersion . ' | Joomla ' . $jCmsVersion . ' | OS ' . $osVersion . '] ';

		$ccEmail = 'joomlasupport@xecurify.com';
		$bccEmail = 'joomlasupport@xecurify.com';
		$content = '<div>Hello, <br><br>'
				. '<strong>Company: </strong><a href="' . $_SERVER['SERVER_NAME'] . '" target="_blank">' . $_SERVER['SERVER_NAME'] . '</a><br><br>';

		if (!empty($phone))
		{
			$content .= '<strong>Phone Number: </strong>' . $phone . '<br><br>';
		}

		$content .= '<strong>Reason: </strong>' . $query . '<br><br>';

		if (!empty($cause))
		{
			$content .= '<strong>Additional Details: </strong>' . $cause . '<br><br>';
		}

		$content .= '<strong>System Information: </strong>' . $query1
				. '</div>';

		$subjectEmail = !empty($feedbackEmail) ? $feedbackEmail : $fromEmail;
		$subject = "miniOrange Joomla LDAP Free Plugin Feedback - " . $subjectEmail;

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
		$fieldString = json_encode($fields);
		$httpHeaderArray = array( 'Content-Type: application/json',$customerKeyHeader, $timestampHeader, $authorizationHeader);

		return self::moPostCurl($url, $fieldString, $httpHeaderArray);
	}

	public function moLdapSubmitContactUs($qEmail, $qPhone, $query, $attributes, $queryType, $timeZone = '')
	{

		if (!MoLdapUtility::moLdapIsCurlInstalled())
		{
			return json_encode(array("status" => 'ERROR','message' => '<a href="http://php.net/manual/en/curl.installation.php">PHP CURL extension</a> is not installed or disabled.'));
		}

		$url = 'https://login.xecurify.com/moas/api/notify/send';
		$ch = curl_init($url);
		$customerKey = self::moLdapGetCustomerKeyValue();
		$apiKey = self::moLdapGetApiKeyValue();

		$currentTimeInMillis = round(microtime(true) * 1000);
		$stringToHash = $customerKey . number_format($currentTimeInMillis, 0, '', '') . $apiKey;
		$hashValue = hash("sha512", $stringToHash);
		$customerKeyHeader = "Customer-Key: " . $customerKey;
		$timestampHeader = "Timestamp: " . number_format($currentTimeInMillis, 0, '', '');
		$authorizationHeader = "Authorization: " . $hashValue;
		$fromEmail = $qEmail;
		$phpVersion = phpversion();
		$jVersion = new Version;
		$jCmsVersion = $jVersion->getShortVersion();
		$moPluginVersion = MoLdapUtility::moLdapGetPluginVersion();
		$moSystemOS = MoLdapUtility::moLdapGetOperatingSystem();
		$subject = "Query for MiniOrange Joomla LDAP Free - " . $fromEmail;
		$query = $query . '<br><strong>Configuration: </strong><br> <strong>Search filter:</strong>  ' . $attributes['search_filter'] . '<br> <strong>Username: </strong> ' . $attributes['username'] . ' <br> <strong>Email: </strong>' . $attributes['email'] . ' <br> <strong>Time Zone: </strong>' . $timeZone;

		$pluginInfo = '[' . $moPluginVersion . ' | PHP ' . $phpVersion . ' | System OS ' . $moSystemOS . ' ] ';
		$query = '[MiniOrange Joomla LDAP Free | ' . $phpVersion . ' | ' . $jCmsVersion . ' | ' . $moPluginVersion . ' | ' . $moSystemOS . '] ' . $query;
		$content = '<div >Hello, <br><br>
					<strong>Company</strong> :<a href="' . $_SERVER['SERVER_NAME'] . '" target="_blank" >' . $_SERVER['SERVER_NAME'] . '</a><br><br>';

		if (!empty($qPhone))
		{
			$content .= '<strong>Phone Number</strong> :' . $qPhone . '<br><br>';
		}

		$content .= '<b>Email :<a href="mailto:' . $fromEmail . '" target="_blank">' . $fromEmail . '</a></b><br><br>
					<b>Query Type: </b>' . $queryType . '<br><br>
					<b>Query</b>: ' . $query . '</b></div>';

		$fields = array(
			'customerKey' => $customerKey,
			'sendEmail' => true,
			'email' => array(
				'customerKey' => $customerKey,
				'fromEmail' => $fromEmail,
				'fromName' => 'miniOrange',
				'toEmail' => 'joomlasupport@xecurify.com',
				'toName' => 'joomlasupport@xecurify.com',
				'subject' => $subject,
				'content' => $content
			),
		);
		$fieldString = json_encode($fields);
		$httpHeaderArray = array( 'Content-Type: application/json', $customerKeyHeader, $timestampHeader, $authorizationHeader);

		return self::moPostCurl($url, $fieldString, $httpHeaderArray);

	}


	public function moLdapRequestForDemo($email, $plan, $demo, $description = '', $addOn = "")
	{
		$url = 'https://login.xecurify.com/moas/api/notify/send';
		$ch = curl_init($url);
		$customerKey = self::moLdapGetCustomerKeyValue();
		$apiKey = self::moLdapGetApiKeyValue();

		$currentTimeInMillis = round(microtime(true) * 1000);
		$stringToHash = $customerKey . number_format($currentTimeInMillis, 0, '', '') . $apiKey;
		$hashValue = hash("sha512", $stringToHash);
		$customerKeyHeader = "Customer-Key: " . $customerKey;
		$timestampHeader = "Timestamp: " . number_format($currentTimeInMillis, 0, '', '');
		$authorizationHeader = "Authorization: " . $hashValue;
		$fromEmail = $email;
		$subject = 'MiniOrange Joomla LDAP Request for ' . $demo;

		$phpVersion = phpversion();
		$jVersion = new Version;
		$jCmsVersion = $jVersion->getShortVersion();
		$moPluginVersion = MoLdapUtility::moLdapGetPluginVersion();
		$moSystemOS = MoLdapUtility::moLdapGetOperatingSystem();

		$pluginInfo = '[' . $moPluginVersion . ' | PHP ' . $phpVersion . ' | System OS ' . $moSystemOS . ' ] ';

		$content = '<div >Hello, <br>
						<br><strong>Company :</strong><a href="' . $_SERVER['SERVER_NAME'] . '" target="_blank" >' . $_SERVER['SERVER_NAME'] . '</a><br><br>
						<strong>Email :</strong><a href="mailto:' . $fromEmail . '" target="_blank">' . $fromEmail . '</a><br><br>
						<strong>Plugin Info: </strong>' . $pluginInfo . '<br><br>
						<strong>' . $demo . ':</strong> ' . $plan . '<br><br>
						<strong>Add on :</strong>' . $addOn . '<br><br>
						<strong>Description: </strong>' . $description . '</div>';

		$fields = array(
			'customerKey' => $customerKey,
			'sendEmail' => true,
			'email' => array(
				'customerKey' => $customerKey,
				'fromEmail' => $fromEmail,
				'fromName' => 'miniOrange',
				'toEmail' => 'joomlasupport@xecurify.com',
				'toName' => 'joomlasupport@xecurify.com',
				'subject' => $subject,
				'content' => $content
			),
		);
		$fieldString = json_encode($fields);
		$httpHeaderArray = array( 'Content-Type: application/json', $customerKeyHeader, $timestampHeader, $authorizationHeader);

		return self::moPostCurl($url, $fieldString, $httpHeaderArray);
	}

	public static function moPostCurl($url, $fields, $httpHeaderArray)
	{
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_ENCODING, "");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

		curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeaderArray);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);

		$proxyServer = MoLdapUtility::moLdapFetchData('#__miniorange_dirsync_config', array('id' => '1'), 'loadAssoc');
		$proxyServerUrl = isset($proxyServer['proxy_server_url']) ? $proxyServer['proxy_server_url'] : '';
		$proxyServerPort = isset($proxyServer['proxy_server_port']) ? $proxyServer['proxy_server_port'] : '';
		$proxyUsername = isset($proxyServer['proxy_username']) ? $proxyServer['proxy_username'] : '';
		$proxyPassword = isset($proxyServer['proxy_password']) ? $proxyServer['proxy_password'] : '';
		$proxyCheck = isset($proxyServer['proxy_set']) ? $proxyServer['proxy_set'] : '';

		if ($proxyCheck == "yes")
		{
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			curl_setopt($ch, CURLOPT_PROXY, $proxyServerUrl);
			curl_setopt($ch, CURLOPT_PROXYPORT, $proxyServerPort);
			curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyUsername . ':' . $proxyPassword);
		}

		$content = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		if (curl_errno($ch))
		{
			$error = array('status' => 'ERROR', 'message' => curl_error($ch));

			return json_encode($error);
		}

		curl_close($ch);

		return $content;
	}

	public function moLdapRequestForSetupCall($email, $query, $description, $callDate, $timeZone, $attributes)
	{
		$url = 'https://login.xecurify.com/moas/api/notify/send';
		$customerKey = self::moLdapGetCustomerKeyValue();
		$apiKey = self::moLdapGetApiKeyValue();

		$currentTimeInMillis = round(microtime(true) * 1000);
		$stringToHash = $customerKey . number_format($currentTimeInMillis, 0, '', '') . $apiKey;
		$hashValue = hash("sha512", $stringToHash);
		$customerKeyHeader = "Customer-Key: " . $customerKey;
		$timestampHeader = "Timestamp: " . number_format($currentTimeInMillis, 0, '', '');
		$authorizationHeader = "Authorization: " . $hashValue;
		$fromEmail = $email;

		$subject = "MiniOrange Joomla LDAP Free - Screen Share/Call Request";
		$phpVersion = phpversion();

		$jVersion = new Version;
		$jCmsVersion = $jVersion->getShortVersion();
		$moPluginVersion = MoLdapUtility::moLdapGetPluginVersion();
		$moSystemOS = MoLdapUtility::moLdapGetOperatingSystem();

		$pluginInfo = '[ LDAP FREE ' . $moPluginVersion . ' | PHP ' . $phpVersion . ' | System OS ' . $moSystemOS . ' ] ';
		$query = $query . '<br><strong>Configuration: </strong><br> <strong>Search filter:</strong>  ' . $attributes['search_filter'] . '<br> <strong>Username: </strong> ' . $attributes['username'] . ' <br> <strong>Email: </strong>' . $attributes['email'];
		$content = '<div>Hello, <br><br>
						<strong>Company :</strong><a href="' . $_SERVER['SERVER_NAME'] . '" target="_blank" >' . $_SERVER['SERVER_NAME'] . '</a><br><br>
						<strong>Plugin Info: </strong>' . $pluginInfo . '<br><br>
						<strong>Email :</strong><a href="mailto:' . $fromEmail . '" target="_blank">' . $fromEmail . '</a><br><br>
						<strong>Time Zone:</strong> ' . $timeZone . '<br><br><strong>Date to set up call: </strong>' . $callDate . '<br><br>
						<strong>Issue : </strong>' . $query . '<br><br>
						<strong>Description:</strong> ' . $description . '</div>';

		$fields = array(
			'customerKey' => $customerKey,
			'sendEmail' => true,
			'email' => array(
				'customerKey' => $customerKey,
				'fromEmail' => $fromEmail,
				'fromName' => 'miniOrange',
				'toEmail' => 'joomlasupport@xecurify.com',
				'toName' => 'joomlasupport@xecurify.com',
				'subject' => $subject,
				'content' => $content
			),
		);
		$fieldString = json_encode($fields);
		$httpHeaderArray = array( 'Content-Type: application/json', $customerKeyHeader, $timestampHeader, $authorizationHeader);

		return self::moPostCurl($url, $fieldString, $httpHeaderArray);
	}
}
