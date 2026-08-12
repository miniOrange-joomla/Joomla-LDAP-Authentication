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

// No direct access to this file
defined('_JEXEC') or die('Restricted Access');

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Version;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

require_once JPATH_ADMINISTRATOR . '/components/com_miniorange_dirsync/helpers/MoConstants.php';
MoConstants::includeHelpers();

$document = Factory::getApplication()->getDocument();

// Load assets from constants
MoConstants::loadAssets($document);

$jsonFile = JPATH_ADMINISTRATOR . '/components/com_miniorange_dirsync/assets/json/tabs.json';

$tabsJsonString = file_get_contents($jsonFile);

if ($tabsJsonString === false)
{
	Factory::getApplication()->enqueueMessage('Failed to load JSON file.', 'warning');
	$tabs = array();
}
else
{
	$tabs = json_decode($tabsJsonString, true);

	if (json_last_error() !== JSON_ERROR_NONE)
	{
		Factory::getApplication()->enqueueMessage('Failed to decode JSON: ' . json_last_error_msg(), 'warning');
		$tabs = array();
	}
}

if (MoLdapUtility::moLdapIsExtensionInstalled('curl') == 0)
{
	?>
	<div class="mo_boot_alert mo_boot_alert-warning mo_boot_border mo_boot_border-3 mo_boot_border-primary mo_boot_bg-light mo_boot_p-3 mo_boot_rounded mo_boot_mb-3" >
		<p class="mo_ldap_highlight mo_boot_mb-0">
			<?php echo Text::_('COM_MINIORANGE_WARNING');?>:
			<?php echo sprintf(
				Text::_('COM_MINIORANGE_CURL_EXTENSION_DISABLED'),
				'<a href="http://php.net/manual/en/curl.installation.php" target="_blank">' . Text::_('COM_MINIORANGE_CURL_EXTENSION') . '</a>'
			); ?>
		</p>
	</div>
	<?php
}

if (MoLdapUtility::moLdapIsExtensionInstalled('ldap') == 0)
{
	?>
	<div class="mo_boot_alert mo_boot_alert-warning mo_boot_border mo_boot_border-3 mo_boot_border-primary mo_boot_bg-light mo_boot_p-3 mo_boot_rounded mo_boot_mb-3" >
		<p class="mo_ldap_highlight mo_boot_mb-0">
			<?php echo Text::_('COM_MINIORANGE_WARNING');?>:
			<?php echo sprintf(
				Text::_('COM_MINIORANGE_LDAP_EXTENSION_DISABLED'),
				'<a href="http://php.net/manual/en/ldap.installation.php" target="_blank">' . Text::_('COM_MINIORANGE_LDAP_EXTENSION') . '</a>'
			); ?>
		</p>
	</div>
	<?php
}

$isSystemEnabled = MoLdapUtility::moLdapIsPluginEnabled('system', 'miniorangedirsync');
$isAuthEnabled = MoLdapUtility::moLdapIsPluginEnabled('authentication', 'moldap');

if (!$isSystemEnabled || !$isAuthEnabled)
{
	?>
	<div id="system-message-container">
		<div class="alert alert-error">
			<h4 class="alert-heading"><?php echo Text::_('COM_MINIORANGE_WARNING');?></h4>
			<div class="alert-message">
				<?php echo Text::_('COM_MINIORANGE_ACTIVATE_SYSTEM_EXTENSION');?>
			</div>
			</h4>
		</div>
	</div>
	<?php
}

$dirsyncActiveTab = 'ldapconfiguration';
$app  = Factory::getApplication();
$input = MoLdapUtility::moLdapGetApplicationInput($app);

// Use getString() so tab-panel is read reliably across servers (e.g. Windows/IIS).
$dirsyncActiveTab = $input->getString('tab-panel', '');

if ($dirsyncActiveTab === '')
{
	$dirsyncActiveTab = $input->post->getString('tab-panel', 'ldapconfiguration');
}

if ($dirsyncActiveTab === 'loggers')
{
	$dirsyncActiveTab = 'moLoggers';
}

function getDirectoryDetails()
{
	return MoLdapUtility::moLdapFetchData('#__miniorange_dirsync_config', array('id' => '1'), 'loadAssoc');
}

function getCustomerDetails()
{
	return MoLdapUtility::moLdapFetchData('#__miniorange_ldap_customer', array('id' => '1'), 'loadAssoc');
}

function moLdapBuildSearchFilterOptionsHtml($searchFilter)
{
	$html = '';

	foreach (MoConstants::LDAP_SERVER_ATTRIBUTES as $value => $text)
	{
		$valueEsc = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
		$selected = (isset($searchFilter) && $searchFilter == $value) ? ' selected' : '';
		$html .= '<option value="' . $valueEsc . '"' . $selected . '>' . Text::_($text) . '</option>';
	}

	return $html;
}

function moLdapBuildJoomlaGroupSelectOptionsHtml($groups)
{
	$html = '';

	foreach ($groups as $group)
	{
		if (!in_array($group[4], array('Super Users')))
		{
			$html .= '<option value="' . (int) $group[0] . '">' . htmlspecialchars($group[4], ENT_QUOTES, 'UTF-8') . '</option>';
		}
	}

	return $html;
}

function moLdapBuildGroupCheckboxesHtml($groups)
{
	$html = '';

	foreach ($groups as $group)
	{
		if ($group[0] != '8')
		{
			$groupId = htmlspecialchars($group[0], ENT_QUOTES, 'UTF-8');
			$groupName = htmlspecialchars($group[4], ENT_QUOTES, 'UTF-8');
			$html .= '<div class="mo_boot_col-sm-3"><div class="mo_boot_form-check">';
			$html .= '<input type="checkbox" class="form-check-input" name="selected_groups[]" value="' . $groupId . '"';
			$html .= ' id="group_' . $groupId . '" checked disabled/>';
			$html .= '<label for="group_' . $groupId . '" class="form-check-label"> ' . $groupName . ' </label>';
			$html .= '</div></div>';
		}
	}

	return $html;
}

function moLdapBuildUserAttributesTableHtml($activeDirectoryUserAttributes)
{
	$html = '';

	foreach ($activeDirectoryUserAttributes as $moLdapAttribute => $moLdapKey)
	{
		if (is_numeric($moLdapAttribute) || $moLdapAttribute === 'count')
		{
			continue;
		}

		$moLdapKey = MoLdapUtility::formatLdapAttributeValue($moLdapAttribute, $moLdapKey);
		$attrEsc = htmlspecialchars($moLdapAttribute, ENT_QUOTES, 'UTF-8');
		$valueHtml = '';

		if (is_array($moLdapKey))
		{
			$values = array();

			foreach ($moLdapKey as $k => $v)
			{
				if ($k !== 'count' && is_numeric($k))
				{
					$values[] = $v;
				}
			}

			if ($moLdapAttribute === 'thumbnailphoto' && !empty($values[0]))
			{
				$valueHtml = '<img src="' . htmlspecialchars((string) $values[0], ENT_QUOTES, 'UTF-8');
				$valueHtml .= '" style="max-width: 60px; max-height: 60px; border-radius: 50%;" alt="User thumbnail">';
			}
			else
			{
				$mapped = array_map(
					function ($v)
					{
						return htmlspecialchars(is_array($v) ? implode(', ', $v) : (string) $v, ENT_QUOTES, 'UTF-8');
					},
					$values
				);
				$valueHtml = implode('<br>', $mapped);
			}
		}
		else
		{
			$valueHtml = htmlspecialchars((string) $moLdapKey, ENT_QUOTES, 'UTF-8');
		}

		$html .= '<tr style="border-bottom: 1px solid #dee2e6; background-color: #ffffff;">';
		$html .= '<td class="mo_ldap_user_details_styles" style="border: 1px solid #dee2e6; padding: 0.5rem 0.75rem;';
		$html .= ' vertical-align: top; background-color: #ffffff; white-space: nowrap; font-size: 0.82rem;">';
		$html .= '<strong>' . $attrEsc . '</strong></td>';
		$html .= '<td class="mo_ldap_user_details_styles" style="border: 1px solid #dee2e6; padding: 0.5rem 0.75rem;';
		$html .= ' vertical-align: top; overflow-wrap: break-word; word-break: break-word; background-color: #ffffff; font-size: 0.82rem;">';
		$html .= $valueHtml . '</td></tr>';
	}

	return $html;
}

$jVersion = new Version;
$jcmsVersion = $jVersion->getShortVersion();


$version = new Version;

if (version_compare($version->getShortVersion(), '4.0', '<='))
{
	?>
		<div class="mo_boot_row mo_boot_p-1">
			<div class="mo_boot_col-sm-12 mo_boot_d-flex mo_boot_justify-content-end mo_boot_align-items-center mo_boot_gap-2 ">

				<a href="index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=mo_ldap_trial_demo&query_type=trial"
				   class="mo_boot_btn mo_boot_px-4 mo_boot_py-1 mo_boot_btn-primary"
				   title="Need Premium features? Contact us">
					<?php echo Text::_('COM_MINIORANGE_LDAP_FREE_TRIAL'); ?>
				</a>


				<a href="index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=mo_ldap_trial_demo&query_type=configuration"
				   class="mo_boot_btn mo_boot_px-4 mo_boot_py-1 mo_boot_btn-warning"
				   title="Need help? Contact us">
					<?php echo Text::_('COM_MINIORANGE_SUPPORT'); ?>
				</a>
			</div>
		</div>
	<?php
}

$tabNavLinksHtml = '';

if (is_array($tabs))
{
	foreach ($tabs as $key => $tab)
	{
		$tabId = htmlspecialchars($tab['id'], ENT_QUOTES, 'UTF-8');
		$tabIcon = htmlspecialchars($tab['icon'], ENT_QUOTES, 'UTF-8');
		$tabHref = htmlspecialchars(
			Route::_('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=' . rawurlencode($key)),
			ENT_QUOTES,
			'UTF-8'
		);
		$paneId = htmlspecialchars(ltrim($tab['href'], '#'), ENT_QUOTES, 'UTF-8');
		$tabKey = htmlspecialchars($key, ENT_QUOTES, 'UTF-8');
		$activeClass = $dirsyncActiveTab == $key ? 'mo_nav_tab_active' : '';
		$tabNavLinksHtml .= '<a id="' . $tabId . '" class="mo_boot_col mo_ldap_nav-tab ' . $activeClass . '"';
		$tabNavLinksHtml .= ' href="' . $tabHref . '"';
		$tabNavLinksHtml .= ' data-tab-key="' . $tabKey . '" data-pane-id="' . $paneId . '" role="tab">';
		$tabNavLinksHtml .= '<span><i class="fa fa-solid ' . $tabIcon . '"></i></span>';
		$tabNavLinksHtml .= '<span class="tab-label mo_boot_p-1">' . Text::_($tab['text']) . '</span>';

		if ($key === 'ntlmsso' || $key === 'addons')
		{
			$crownUrl = htmlspecialchars(MoConstants::getImageUrl('crown.webp'), ENT_QUOTES, 'UTF-8');
			$crownTitle = htmlspecialchars(Text::_('COM_MINIORANGE_UPGRADE_TO_PREMIUM'), ENT_QUOTES, 'UTF-8');
			$tabNavLinksHtml .= '<sup><img class="crown_img_small mo_boot_ml-1 mo_ldap_cursor-type" src="' . $crownUrl;
			$tabNavLinksHtml .= '" style="width: 16px; height: 16px;" onclick="mo_ldap_upgrade()" title="' . $crownTitle . '"></sup>';
		}

		$tabNavLinksHtml .= '</a>';
	}
}

?>
<div class="mo_boot_container-fluid">
	<div class="mo_boot_row mo_ldap_navbar">
		<?php echo $tabNavLinksHtml; ?>
	</div>
</div>

	<div style="position: fixed; bottom: 30px; right: 30px; z-index: 9999;">
		<a href="<?php echo Route::_('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=mo_ldap_trial_demo&query_type=configuration'); ?>"
		   class="mo_boot_btn mo_boot_btn-warning"
		   style="border-radius: 50px; padding: 12px 20px; font-weight: bold; box-shadow: 0 4px 12px rgba(0,0,0,0.3);">
								<i class="fas fa-envelope"></i> Need Help?
		</a>
	</div>
	<div class="tab-content mo_ldap_tab-content">
		<div id="ldapconfiguration" class="tab-pane <?php echo $dirsyncActiveTab == 'ldapconfiguration' ? 'active show' : ''; ?>">
			<div class="mo_boot_row">
				<div class="mo_boot_col-sm-12">
					<div id="ldapConfigurationContent">
						<?php moLdapConfiguration($dirsyncActiveTab);?>
					</div>

					<!-- Import/Export Section -->
					<div id="importExportView" class="mo_boot_mt-4" style="display: none;">
						<!-- Back Button -->
						<div class="mo_boot_mb-3">
							<button type="button" class="mo_boot_btn mo_boot_btn-primary" onclick="toggleImportExportView()">
								<i class="icon-arrow-left mo_boot_me-2"></i>
								<?php echo Text::_('COM_MINIORANGE_CLOSE'); ?>
							</button>
						</div>

						<!-- Import/Export Container -->
						<div class="mo_boot_container-fluid">
							<div class="mo_boot_row mo_boot_justify-content-center">
								<div class="mo_boot_col-lg-8">
									<div class="mo_boot_card">
										<div class="mo_boot_card-header">
											<div class="mo_boot_d-flex mo_boot_justify-content-between mo_boot_align-items-center">
												<h4 class="mo_boot_card-title mb-0" style="font-size: 1.5rem; font-weight: bold;">
													<?php echo Text::_('COM_MINIORANGE_IMPORT_EXPORT'); ?>
												</h4>
											</div>
										</div>
										<div class="mo_boot_card-body" style="padding: 2rem;">
											<!-- Export Section -->
											<div class="mo_boot_mb-4 mo_ldap_mini_section">
												<h5 class="mo_boot_mb-3" style="font-weight: bold; color: #333;">
													<?php echo Text::_('COM_MINIORANGE_EXPORT_CONFIGURATION'); ?>
												</h5>

												<form id="exportConfigurationForm" method="post" action="<?php echo Route::_(MoConstants::LDAP_EXPORT_URL); ?>">
													<button type="submit"
														class="mo_boot_btn mo_boot_btn-primary"
														id="exportBtn">
														<i class="icon-download mo_boot_me-2"></i>
														<span class="btn-text"><?php echo Text::_('COM_MINIORANGE_EXPORT_CONFIGURATION'); ?></span>
														<span class="btn-loading" style="display: none;">
															<i class="fa fa-spinner fa-spin mo_boot_me-2"></i>
															<?php echo Text::_('COM_MINIORANGE_EXPORTING'); ?>...
														</span>
													</button>
													<?php echo HTMLHelper::_('form.token'); ?>
												</form>
											</div>

											<!-- Import Section (Premium) -->
											<div class="mo_boot_mb-4 mo_ldap_mini_section">
												<h5 class="mo_boot_mb-3" style="font-weight: bold; color: #333;">
													<?php echo Text::_('COM_MINIORANGE_IMPORT_CONFIGURATION'); ?>
													<sup>
														<img class="crown_img_small mo_boot_ml-1 mo_ldap_cursor-type"
															src="<?php echo htmlspecialchars(MoConstants::getImageUrl('crown.webp'), ENT_QUOTES, 'UTF-8'); ?>"
															style="width: 16px; height: 16px;"
															onclick="mo_ldap_upgrade()"
															title="<?php echo htmlspecialchars(Text::_('COM_MINIORANGE_UPGRADE_TO_PREMIUM'), ENT_QUOTES, 'UTF-8'); ?>">
													</sup>
												</h5>

												<form id="importConfigurationForm" method="post" action="<?php echo Route::_(MoConstants::LDAP_IMPORT_URL); ?>" enctype="multipart/form-data">
													<button type="submit" class="mo_boot_btn mo_boot_btn-primary mo_ldap_disabled_input" id="importBtn" disabled>
														<i class="icon-upload mo_boot_me-2"></i>
														<span class="btn-text"><?php echo Text::_('COM_MINIORANGE_IMPORT_CONFIGURATION'); ?></span>
														<span class="btn-loading" style="display: none;">
															<i class="fa fa-spinner fa-spin mo_boot_me-2"></i>
															<?php echo Text::_('COM_MINIORANGE_IMPORTING'); ?>...
														</span>
													</button>
													<?php echo HTMLHelper::_('form.token'); ?>
												</form>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div id="signinsettings" class="tab-pane <?php echo $dirsyncActiveTab == 'signinsettings' ? 'active show' : ''; ?>">
			<div class="mo_boot_row">
				<div class="mo_boot_col-sm-12" >
					<?php moLdapLoginSettings();?>
				</div>
			</div>
		</div>

		<div id="attributerolemapping" class="tab-pane <?php echo $dirsyncActiveTab == 'attributerolemapping' ? 'active show' : ''; ?>">
			<div class="mo_boot_row">
				<div class="mo_boot_col-sm-12" >
					<?php moLdapAttributeMapping();?>
				</div>
			</div>
		</div>

		<div id="ntlmsso" class="tab-pane <?php echo $dirsyncActiveTab == 'ntlmsso' ? 'active show' : ''; ?>">
			<div class="mo_boot_row">
				<div class="mo_boot_col-12">
					<?php moLdapNtlmSso();?>
				</div>
			</div>
		</div>

		<div id="addons" class="tab-pane <?php echo $dirsyncActiveTab == 'addons' ? 'active show' : ''; ?>">
			<div class="mo_boot_row">
				<div class="mo_boot_col-sm-12">
					<?php moLdapProvisioning();?>
				</div>
			</div>
		</div>

		<div id="mo_ldap_trial_demo" class="tab-pane <?php echo $dirsyncActiveTab == 'mo_ldap_trial_demo' ? 'active show' : ''; ?>">
			<div class="mo_boot_row">
				<div class="mo_boot_col-sm-12" >
					<?php moLdapSupportTab();?>
				</div>
			</div>
		</div>

		<div id="licensing" class="tab-pane <?php echo $dirsyncActiveTab == 'licensing' ? 'active show' : ''; ?>">
			<div class="mo_boot_row">
				<div class="mo_boot_col-sm-12" >
					<?php moLdapLicensingPlan();?>
				</div>
			</div>
		</div>

		<div id="loggers" class="tab-pane <?php echo $dirsyncActiveTab == 'moLoggers' ? 'active show' : ''; ?>">
			<div class="mo_boot_row">
				<div class="mo_boot_col-sm-12" >
					<?php moLoggers();?>
				</div>
			</div>
		</div>
	</div>
<?php


function moLdapConfiguration($dirsyncActiveTab = 'ldapconfiguration')
{
	$moLdapServerConfig = new MoLdapConstants;
	$ldapType = "";
	$ldapPort = "";
	$ldapServerUrl = "";
	$searchBase = "";
	$serviceAccountDn = "";
	$serviceAccountPassword = "";
	$serverType = $moLdapServerConfig->getServerType();
	$ignoreLdaps = "";
	$enableTls = "";
	$searchFilter = "";
	$testUsername = "";
	$isTestServer = false;
	$activeDirectoryAttributes = $moLdapServerConfig->getActiveDirectoryAttributes();

	if (!empty($moLdapServerConfig->getServerURL()))
	{
		$ldapServerUrl          = $moLdapServerConfig->getServerURL();
		$serviceAccountDn       = $moLdapServerConfig->getBindDN();
		$serviceAccountPassword = $moLdapServerConfig->getBindDNPassword();
		$searchBase 	  = $moLdapServerConfig->getSearchBase();
		$searchFilter 			  = $moLdapServerConfig->getSearchFilter();
		$serverType 			  = $moLdapServerConfig->getServerType();
		$ignoreLdaps             = $moLdapServerConfig->getIgnoreCertificateState();
		$enableTls 				= $moLdapServerConfig->getEnableTls();
		$testUsername 			= $moLdapServerConfig->getTestUsername();

		$ldapType = strtok($ldapServerUrl, '://');
		$ldapPort = substr($ldapServerUrl, strrpos($ldapServerUrl, ':') + 1);
		$ldapServerUrlSub = substr($ldapServerUrl, strrpos($ldapServerUrl, '://') + 3);
		$ldapServerUrl = strtok($ldapServerUrlSub, ':');
		$isTestServer = (strpos($ldapServerUrl, 'ldap.forumsys.com') !== false);
	}

	if (empty($ldapPort))
	{
		$ldapPort = "389";
	}

	$searchFilterOptionsHtml = moLdapBuildSearchFilterOptionsHtml($searchFilter);

	?>
	<div class="mo_boot_container-fluid mo_main_ldap_section">
		<div id="mo_ldap_server_config" class="mo_boot_col-sm-12">
			<div class="mo_boot_col-sm-12" id="mo_ldap_server_configuration">
				<div class="mo_boot_col-sm-12 mo_boot_d-flex mo_boot_justify-content-between mo_boot_align-items-center mo_boot_flex-wrap mo_boot_gap-2">
					<h3 class="mo_ldap_sub_heading mo_boot_mb-0"><?php echo Text::_('COM_MINIORANGE_LDAP_CONNECTION'); ?></h3>
					<div class="mo_boot_d-flex mo_boot_justify-content-end mo_boot_align-items-center mo_boot_gap-3 mo_boot_flex-wrap">
						<a href="<?php echo MoConstants::LDAP_CONFIGURATION_GUIDE; ?>"
							target="_blank"
							class="mo_boot_text-dark">
							<i class="fa fa-book mo_boot_me-1"></i>
							<?php echo Text::_('COM_MINIORANGE_SETUP_GUIDE'); ?>
						</a>
						<a href="<?php echo MoConstants::LDAP_VIDEO_SETUP_GUIDE; ?>"
							target="_blank"
							class="mo_boot_text-dark">
							<i class="fa fa-play-circle mo_boot_me-1"></i>
							<?php echo Text::_('COM_MINIORANGE_VIDEO_SETUP_GUIDE'); ?>
						</a>
					</div>
				</div>

				<!-- Step 1: LDAP Server Configuration -->
				<div class="mo_boot_col-sm-12 mo_ldap_mini_section">
						<form id="mo_ldap_config_form" name="mo_ldap_config_form" method="post"
							  action="<?php echo Route::_(MoConstants::LDAP_SAVE_CONFIG_URL); ?>">
							<input type="hidden" id="ldap_configuration_action" name="ldap_configuration_action"
								   value="saveconfig">
							<input type="hidden" id="current_tab_ldap_config" name="current_tab" value="<?php echo $dirsyncActiveTab; ?>">

							<?php
							// LDAP Server Name dropdown
							$ldapServerOptions = MoConstants::LDAP_SERVER_TYPES;

							$serverTypeConfig = (new FormFieldConfig('mo_ldap_directory_server_type', Text::_('COM_MINIORANGE_DIRECTORY_SERVER')))
								->setType('dropdown')
								->setOptions($ldapServerOptions)
								->setSelectedValue(isset($serverType) ? $serverType : '')
								->setPlaceholder('COM_MINIORANGE_SELECT_AD')
								->setRequired(true)
								->setHelpTitle(Text::_('COM_MINIORANGE_LDAP_SERVER_NAME_HELP'))
								->setLayout(4, 7, 1);

							echo FormRenderer::renderField($serverTypeConfig);
							?>

							<!-- LDAP Server URL -->
							<div class="mo_boot_row mo_boot_col-sm-12 mo_boot_mb-4">
								<div class="mo_boot_col-12 mo_boot_col-md-4 mo_boot_mb-2 ">
									<label class="form-label fw-medium">
										<?php echo Text::_('COM_MINIORANGE_LDAP_SERVER_URL'); ?> <span
												class="mo_ldap_highlight">*</span>
										<i class="fa fa-info-circle mo_boot_ms-1"
										   title="<?php echo Text::_('COM_MINIORANGE_LDAP_SERVER_URL_HELP'); ?>"></i>
									</label>
								</div>
								<div class="mo_boot_col-12 mo_boot_col-md-7">
									<div class="mo_boot_d-flex mo_boot_flex-column mo_boot_flex-md-row mo_boot_gap-2">
										<select class="form-select" id="mo_ldap_type" name="mo_ldap_type"
												style="min-width: 120px; max-width: 200px;">
											<option value="ldap" <?php echo ($ldapType == 'ldap') ? 'selected' : ''; ?>><?php echo Text::_('COM_MINIORANGE_LDAP'); ?></option>
											<option value="ldaps" <?php echo ($ldapType == 'ldaps') ? 'selected' : ''; ?>><?php echo Text::_('COM_MINIORANGE_LDAPS'); ?></option>
										</select>
										<input class="form-control" id="mo_ldap_server_url" name="mo_ldap_server_url"
											   type="text"
											   placeholder="<?php echo Text::_('COM_MINIORANGE_LDAP_SERVER_URL_PLACEHOLDER'); ?>"
											   value='<?php echo $ldapServerUrl; ?>' required>
										<input class="form-control" id="mo_ldap_port" name="mo_ldap_port" type="text"
											   placeholder="<?php echo Text::_('COM_MINIORANGE_PORT_NO_PLACEHOLDER'); ?>"
											   value='<?php echo $ldapPort; ?>' style="min-width: 80px; max-width: 120px;">
									</div>
								</div>
							</div>

							<!-- Service Account DN -->
							<?php
								$serviceAccountConfig = (new FormFieldConfig('service_account_dn', Text::_('COM_MINIORANGE_SERVICE_ACCOUNT_DN')))
									->setType('text')
									->setValue(isset($serviceAccountDn) ? $serviceAccountDn : '')
									->setPlaceholder(Text::_('COM_MINIORANGE_SERVICE_ACCOUNT_DN_PLACEHOLDER'))
									->setRequired(true)
									->setHelpTitle(Text::_('COM_MINIORANGE_SERVICE_ACCOUNT_DN_HELP'))
									->setLayout(4, 7, 1);

								echo FormRenderer::renderField($serviceAccountConfig);

								// Service Account Password
								$servicePasswordConfig = (new FormFieldConfig('service_account_password', Text::_('COM_MINIORANGE_SERVICE_ACCOUNT_PASSWORD')))
									->setType('password')
									->setValue(isset($serviceAccountPassword) ? $serviceAccountPassword : '')
									->setPlaceholder(Text::_('COM_MINIORANGE_SERVICE_ACCOUNT_PASSWORD_PLACEHOLDER'))
									->setRequired(true)
									->setHelpTitle(Text::_('COM_MINIORANGE_SERVICE_ACCOUNT_PASS_HELP'))
									->setLayout(4, 7, 1);

								echo FormRenderer::renderField($servicePasswordConfig);

								// Ignore LDAPS Certificate Toggle
								$ignoreLdapsConfig = (new FormFieldConfig('mo_ignore_ldaps', Text::_('COM_MINIORANGE_IGNORE_LDAPS')))
									->setType('toggle')
									->setDisabled(true)
									->setHelpText(Text::_('COM_MINIORANGE_IGNORE_LDAPS_DESCRIPTION'))
									->setIsPremium(true)
									->setLayout(4, 7, 1);

								echo FormRenderer::renderField($ignoreLdapsConfig);

								// Enable TLS Toggle
								$enableTlsConfig = (new FormFieldConfig('mo_enable_tls', Text::_('COM_MINIORANGE_CONNECTION_VIA_TLS')))
									->setType('toggle')
									->setDisabled(true)
									->setHelpText(Text::_('COM_MINIORANGE_CONNECTION_VIA_TLS_DESCRIPTION'))
									->setIsPremium(true)
									->setLayout(4, 7, 0);

								echo FormRenderer::renderField($enableTlsConfig);

								// Connect LDAP Server Button
								$testConfigButton = (new FormFieldConfig('action_btn', Text::_('COM_MINIORANGE_TEST_CONFIGURATION')))
									->setType('button')
									->setButtonType('submit')
									->setBtnClass('primary')
									->setLayout(0, 12, 0)
									->setAttributes(array('mo_boot_col-sm-12' => 'mo_boot_col-sm-12 mo_boot_row mo_boot_justify-content-center'));

								echo FormRenderer::renderField($testConfigButton);
							?>
						</form>
				</div>

				<!-- Step 2: LDAP Mapping Configuration -->
				<div class="mo_boot_col-sm-12 mo_boot_d-flex mo_boot_justify-content-between mo_boot_align-items-center mo_boot_mt-5" id="mo_ldap_configuration_step2">
					<h3 class="mo_ldap_sub_heading"><?php echo Text::_('COM_MINIORANGE_LDAP_MAPPING_CONFIGURATION'); ?></h3>
				</div>
					<div class="mo_ldap_mini_section mo_boot_col-sm-12">
						<form id="ldap_mapping_config_form" class="mo_boot_ms-5" name="ldap_mapping_config_form" method="post"
							  action="<?php echo Route::_(MoConstants::LDAP_SAVE_USER_MAPPING_URL); ?>">
							<input type="hidden" id="current_tab_mapping" name="current_tab" value="<?php echo $dirsyncActiveTab; ?>">

							<div class="mo_ldap_mapping_config">
								<!-- Search Base -->
								<div class="mo_boot_row mo_boot_mb-4">
									<div class="mo_boot_col-12 mo_boot_col-md-4">
										<label for="search_base" class="form-label fw-medium">
											<?php echo Text::_('COM_MINIORANGE_SEARCH_BASE'); ?> <span
													class="mo_ldap_highlight">*</span>
											<i class="icon-info-circle mo_boot_ms-1"
											   title="<?php echo Text::_('COM_MINIORANGE_SEARCH_BASE_HELP'); ?>"></i>
										</label>
									</div>
									<div class="mo_boot_col-12 mo_boot_col-md-5">
										<input class="form-control" id="search_base" name="search_base"
											   placeholder="dc=domain,dc=com" type="text"
											   value='<?php echo $searchBase; ?>' required>
									</div>
									<div class="mo_boot_col-12 mo_boot_col-md-2">
										<button type="button"
												class="mo_boot_btn mo_boot_btn-outline-secondary mo_boot_w-100 <?php echo empty($ldapServerUrl) ? 'mo_ldap_disabled_input' : ''; ?>"
												onclick="mo_ldap_possible_search_bases()"
												<?php echo empty($ldapServerUrl) ? 'disabled' : ''; ?>
												<?php echo empty($ldapServerUrl) ? 'title="' . Text::_('COM_MINIORANGE_PLEASE_ADD_LDAP_SERVER_URL') . '"' : ''; ?>>
											<?php echo Text::_('COM_MINIORANGE_POSSIBLE_SEARCH_BASES'); ?>
										</button>
									</div>
								</div>

								<!-- Search Filter -->
								<div class="mo_boot_row mo_boot_mb-4">
									<div class="mo_boot_col-12 mo_boot_col-md-4">
										<label for="search_filter" class="form-label fw-medium">
											<?php echo Text::_('COM_MINIORANGE_SEARCH_FILTER'); ?> <span
													class="mo_ldap_highlight">*</span>
											<i class="icon-info-circle mo_boot_ms-1"
											   title="<?php echo Text::_('COM_MINIORANGE_SEARCH_FILTER_HELP'); ?>"></i>
										</label>
									</div>
									<div class="mo_boot_col-12 mo_boot_col-md-7">
										<select name="search_filter" id="search_filter" class="form-select" required>
											<option value="" disabled><?php echo Text::_('COM_MINIORANGE_SELECT_USERNAME'); ?></option>
											<?php echo $searchFilterOptionsHtml; ?>
										</select>
									</div>
								</div>
							</div>

							<!-- Action Buttons -->
							<div class="mo_boot_row">
								<div class="mo_boot_col-12 mo_boot_col-md-2">
									<!-- Empty div for alignment -->
								</div>

								<div class="mo_boot_col-12 mo_boot_col-md-8 mo_boot_d-flex mo_boot_justify-content-center">
									<button type="submit"
											class="mo_boot_btn mo_boot_btn-primary mo_boot_px-4 mo_boot_me-2 <?php echo empty($ldapServerUrl) ? 'mo_ldap_disabled_input' : ''; ?>"
											<?php echo empty($ldapServerUrl) ? 'disabled' : ''; ?>
											<?php echo empty($ldapServerUrl) ? 'title="' . Text::_('COM_MINIORANGE_PLEASE_ADD_LDAP_SERVER_URL') . '"' : ''; ?>>
										<i class="fa fa-check mo_boot_me-1"></i> <?php echo Text::_('COM_MINIORANGE_SAVE_CONFIGURATION'); ?>
									</button>
								</div>
							</div>
						</form>
					</div>

				<!-- Step 3: Test Authentication -->
				<div class="mo_boot_col-sm-12 mo_boot_d-flex mo_boot_justify-content-between mo_boot_align-items-center mo_boot_mt-5">
					<h3 class="mo_ldap_sub_heading">
						<?php echo Text::_('COM_MINIORANGE_LDAP_TEST_AUTHENTICATION'); ?>
					</h3>
				</div>

				<div class="mo_boot_col-sm-12 mo_ldap_mini_section" id="mo_ldap_configuration_step3">
					<div class="mo_boot_mb-3">
						<em>
							<?php echo Text::sprintf('COM_MINIORANGE_LDAP_TEST_NOTE', $searchFilter, $searchBase); ?>
						</em>
					</div>

					<form id="mo_ldap_mapping_testauth_form" name="mo_ldap_mapping_testauth_form" method="post"
						  action="<?php echo Route::_(MoConstants::LDAP_TEST_CONFIG_URL); ?>">
						<?php
							// Test Username Field
							$testUsernameConfig = (new FormFieldConfig('test_username', Text::_('COM_MINIORANGE_TEST_USERNAME')))
								->setType('text')
								->setValue(isset($testUsername) ? $testUsername : '')
								->setPlaceholder(Text::_('COM_MINIORANGE_TEST_AUTHENTICATION_USERNAME'))
								->setRequired(true);

							echo FormRenderer::renderField($testUsernameConfig);

							// Test Password Field with helper text for test server
							$passwordHelpText = $isTestServer ? Text::_('COM_MINIORANGE_TEST_DUMMY_PASSWORD') . ': <code>password</code>' : '';
							$testPasswordConfig = (new FormFieldConfig('test_password', Text::_('COM_MINIORANGE_TEST_PASSWORD')))
								->setType('password')
								->setPlaceholder(Text::_('COM_MINIORANGE_TEST_AUTHENTICATION_PASSWORD'))
								->setRequired(true)
								->setHelpText($passwordHelpText);

							echo FormRenderer::renderField($testPasswordConfig);

							// Check attribute receiving
							?>
						<div class="mo_boot_row Mo_boot_col-sm-12 mo_boot_mb-3">
								<div class="mo_boot_col-sm-4"><!-- alignment spacer --></div>
								<div class="mo_boot_col-sm-7 mo_boot_d-flex mo_boot_gap-3 mo_boot_mb-3">
									<button type="button"
											class="mo_boot_btn mo_boot_btn-primary mo_boot_px-4 mo_boot_py-2 <?php echo empty($ldapServerUrl) ? 'mo_ldap_disabled_input' : ''; ?>"
											onclick="checkLdapAttributes()"
											<?php echo empty($ldapServerUrl) ? 'disabled' : ''; ?>
											<?php echo empty($ldapServerUrl) ? 'title="' . Text::_('COM_MINIORANGE_PLEASE_ADD_LDAP_SERVER_URL') . '"' : ''; ?>>
										<i class="fa fa-cog mo_boot_me-1"></i>
										<?php echo Text::_('COM_MINIORANGE_TEST_AUTHENTICATION_AND_SAVE'); ?>
									</button>
									</div>
								<div class="mo_boot_col-sm-1"><!-- right spacer --></div>
								</div>
							<?php
							?>
						</form>
				</div>

				<!-- Configuration Management Buttons -->
				<div class="mo_boot_col-sm-12 mo_boot_d-flex mo_boot_gap-3 mo_boot_mt-5">
					<button type="button"
							id="toggleImportExportBtn"
							class="mo_boot_btn mo_boot_btn-primary"
							onclick="toggleImportExportView()">
						<i class="icon-download mo_boot_me-2"></i>
						<?php echo Text::_('COM_MINIORANGE_IMPORT_EXPORT'); ?>
					</button>

					<form id="resetLdapSettings" name="resetLdapSettings" method="post" class="mo_boot_d-inline"
						  action="<?php echo Route::_(MoConstants::LDAP_RESET_SETTINGS_URL); ?>">
						<button type="submit" class="mo_boot_btn mo_boot_btn-danger">
							<i class="icon-trash mo_boot_me-2"></i>
							<?php echo Text::_('COM_MINIORANGE_RESET_CONFIGURATION_SETTINGS'); ?>
						</button>
						<?php echo HTMLHelper::_('form.token'); ?>
					</form>
				</div>
			</div>
		</div>
	</div>

	<?php
}

function moLdapLoginSettings()
{
	$moLdapServerConfig = new MoLdapConstants;
	$enableLdap = $moLdapServerConfig->getEnableLdap();
	$searchFilter = $moLdapServerConfig->getSearchFilter();
	$moLdapConfiguration = MoLdapUtility::moLdapGetDetails('#__miniorange_dirsync_config');
	$moRedirectUrl = isset($moLdapConfiguration['redirect_url']) ? $moLdapConfiguration['redirect_url'] : "";

	$groups = MoLdapUtility::moLdapGetJoomlaGroups();
	$groupCheckboxesHtml = moLdapBuildGroupCheckboxesHtml($groups);

	?>
	<div class="mo_boot_container-fluid mo_main_ldap_section">
		<div class="mo_boot_col-sm-12">
			<div class="mo_boot_col-sm-12">
				<div class="mo_boot_col-sm-12 mo_boot_d-flex mo_boot_justify-content-between mo_boot_align-items-center"">
					<h3 class="mo_ldap_sub_heading">
						<?php echo Text::_('COM_MINIORANGE_LDAP_LOGIN_SETTINGS'); ?>
					</h3>
				</div>

				<!-- Single LDAP Login Settings Card -->
				<div class="mo_boot_col-sm-12">
					<form name="mo_ldap_signin_form" class="mo_boot_row mo_boot_col-sm-12" id="mo_ldap_enable_both_login_form" method="post"
						action="<?php echo Route::_(MoConstants::LOGIN_SAVE_URL); ?>">

						<!-- 1. Login Settings Configuration -->
						<div class="mo_ldap_mini_section mo_boot_col-sm-12">
							<?php
								// Enable Role Mapping Toggle
								$enableLoginConfig = (new FormFieldConfig('mo_ldap_login', Text::_('COM_MINIORANGE_ENABLE_LOGIN_DETAILS1')))
									->setType('toggle')
									->setChecked($enableLdap == 'ch')
									->setDisabled(!$searchFilter)
									->setLayout(4, 6, 0);

								echo FormRenderer::renderField($enableLoginConfig);?>
								<div class="alert alert-info" role="alert">
									<span class="icon-info-circle" aria-hidden="true"></span>
									<?php echo Text::_('COM_MINIOARNGE_ENABLE_LOGIN_DETAILS_INFO'); ?>
								</div>
						</div>

						<!--  Redirect URL Configuration -->
						<div class="mo_ldap_mini_section mo_boot_col-sm-12">
							<?php
								$redirectUrlConfig = (new FormFieldConfig('mo_ldap_redirect_url', Text::_('COM_MINIORANGE_REDIRECT_URL')))
									->setType('text')
									->setValue(isset($moRedirectUrl) ? htmlspecialchars($moRedirectUrl) : '')
									->setPlaceholder(Text::_('COM_MINIORANGE_REDIRECT_URL_PLACEHOLDER'))
									->setDisabled(true)
									->setHelpTitle(Text::_('COM_MINIORANGE_REDIRECT_URL_DESCRIPTION'))
									->setIsPremium(true)
									->setLayout(4, 6, 0);

								echo FormRenderer::renderField($redirectUrlConfig);
							?>
						</div>

						<!-- Login Restriction Based on User's Joomla Group -->
						<div class="mo_ldap_mini_section mo_boot_col-sm-12">
							<label class="form-label fw-medium">
								<?php echo Text::_('COM_MINIORANGE_LOGIN_RESTRICTION_BASED_ON_GROUPS'); ?>
								<i class="fa fa-info-circle" title=" <?php echo Text::_('COM_MINIORANGE_GROUP_SELECTION_DESCRIPTION'); ?>"></i>
								<sup>
									<img class="crown_img_small mo_boot_ml-2"
											src="<?php echo Uri::base() . MoConstants::CROWN_IMAGE; ?>"
											title="<?php echo Text::_('COM_MINIORANGE_UPGRADE_TO_PREMIUM'); ?>">
								</sup>
							</label>

							<div id="group-tree" class="mo_boot_row mo_ldap_disabled_input">
								<?php echo $groupCheckboxesHtml; ?>
							</div>
						</div>

						<!-- Save Settings Button -->
						<?php
							$saveLoginButton = (new FormFieldConfig('save_login_seeting_btn', Text::_('COM_MINIORANGE_SAVE_CONFIGURATION')))
								->setType('button')
								->setButtonType('submit')
								->setBtnClass('primary')
								->setLayout(0, 12, 0)
								->setAttributes(array('mo_boot_col-sm-12' => 'mo_boot_col-sm-12 mo_boot_row mo_boot_justify-content-center'))
								->setIcon('fa fa-check')
								->setDisabled(!$searchFilter)
								->setTitle(!$searchFilter ? Text::_('COM_MINIORANGE_PLEASE_ADD_SEARCH_FILTER') : '');

							echo FormRenderer::renderField($saveLoginButton);
						?>
					</form>
				</div>
			</div>
		</div>
	</div>
	<?php
}

function moLdapAttributeMapping()
{

	$moLdapServerConfig = new MoLdapConstants;
	$username = $moLdapServerConfig->getSearchFilter();
	$email = $moLdapServerConfig->getEmailAttribute();
	$name = $moLdapServerConfig->getNameAttribute();
	$searchFilter = $moLdapServerConfig->getSearchFilter();
	$activeDirectoryUserAttributes = $moLdapServerConfig->getTestConfigDetails();
	$userName = $moLdapServerConfig->getTestConfigUsername();
	$ldapServerUrl = $moLdapServerConfig->getServerURL();
	$savedTestUsername = $moLdapServerConfig->getTestConfigUsername();
	$ldapAttrOptions = MoLdapUtility::buildLdapAttributeOptions($moLdapServerConfig->getActiveDirectoryAttributes());
	$groups = MoLdapUtility::moLdapGetJoomlaGroups();
	$groupOptions = array();

	foreach ($groups as $group)
	{
		if (!in_array($group[4], array('Super Users')))
		{
			$groupOptions[$group[0]] = $group[4];
		}
	}

	$joomlaGroupSelectOptionsHtml = moLdapBuildJoomlaGroupSelectOptionsHtml($groups);
	$userAttributesTableHtml = moLdapBuildUserAttributesTableHtml($activeDirectoryUserAttributes);
	$userDetailsSidebarHtml = '';

	if (empty($activeDirectoryUserAttributes))
	{
		$userDetailsSidebarHtml = '<div class="alert alert-info mo_boot_mb-0">';
		$userDetailsSidebarHtml .= '<i class="fa fa-info-circle mo_boot_me-2"></i>';
		$userDetailsSidebarHtml .= Text::_('COM_MINIORANGE_LDAP_GET_USER_DETAILS_1') . '</div>';
	}
	else
	{
		$userNameEsc = htmlspecialchars($userName, ENT_QUOTES, 'UTF-8');
		$userDetailsSidebarHtml = '<div class="mo_boot_mb-2"><strong>' . Text::_('COM_MINIORANGE_LDAP_USERNAME') . ':</strong>';
		$userDetailsSidebarHtml .= '<span class="text-primary mo_boot_ms-1">' . $userNameEsc . '</span></div>';
		$userDetailsSidebarHtml .= '<div class="mo_ldap_attributes_container mo_ldap_user_details" style="max-height: 500px; overflow-y: auto; overflow-x: auto; border: 1px solid #dee2e6; border-radius: 0.375rem; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);">';
		$userDetailsSidebarHtml .= '<table class="table mb-0" style="border-collapse: collapse; width: 100%; table-layout: auto;">';
		$userDetailsSidebarHtml .= '<thead class="table-light" style="position: sticky; top: 0; z-index: 10;"><tr>';
		$userDetailsSidebarHtml .= '<th style="white-space: nowrap; border: 1px solid #dee2e6; padding: 0.5rem 0.75rem; background-color: #f8f9fa; font-weight: 600; font-size: 0.8rem;">';
		$userDetailsSidebarHtml .= Text::_('COM_MINIORANGE_ATTRIBUTE_NAME') . '</th>';
		$userDetailsSidebarHtml .= '<th style="width: 60%; border: 1px solid #dee2e6; padding: 0.5rem 0.75rem; background-color: #f8f9fa; font-weight: 600; font-size: 0.8rem;">';
		$userDetailsSidebarHtml .= Text::_('COM_MINIORANGE_ATTRIBUTE_VALUE') . '</th></tr></thead><tbody>';
		$userDetailsSidebarHtml .= $userAttributesTableHtml . '</tbody></table></div>';
	}

	?>
<!--    No need to again select the username-->
	<input type="hidden"
		   name="username"
		   value="<?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>" />
	<div class="mo_boot_container-fluid mo_main_ldap_section">
		<div id="mo_ldap_server_config_wrapper">
			<div class="mo_boot_col-sm-12 mo_boot_d-flex mo_boot_justify-content-between mo_boot_align-items-center mo_boot_flex-wrap mo_boot_gap-2" id="mo_ldap_server_configuration">
				<h3 class="mo_ldap_sub_heading mo_boot_mb-0">
					<?php echo Text::_('COM_MINIORANGE_ATTRIBUTE_AND_GROUP_MAPPING'); ?>
				</h3>
				<div class="mo_boot_d-flex mo_boot_justify-content-end mo_boot_align-items-center mo_boot_gap-3 mo_boot_flex-wrap">
					<a href="<?php echo MoConstants::LDAP_CONFIGURATION_GUIDE; ?>"
						target="_blank"
						class="mo_boot_text-dark">
						<i class="fa fa-book mo_boot_me-1"></i>
						<?php echo Text::_('COM_MINIORANGE_SETUP_GUIDE'); ?>
					</a>
					<a href="<?php echo MoConstants::LDAP_VIDEO_SETUP_GUIDE; ?>"
						target="_blank"
						class="mo_boot_text-dark">
						<i class="fa fa-play-circle mo_boot_me-1"></i>
						<?php echo Text::_('COM_MINIORANGE_VIDEO_SETUP_GUIDE'); ?>
					</a>
				</div>
			</div>

			<!-- Main Content and Sidebar Layout -->
			<div class="mo_boot_col-sm-12 mo_boot_row">
				<div class="mo_boot_col-sm-12 mo_boot_col-xl-7">
					<!-- 2. Basic Attribute Mapping Section -->
					<div class="mo_ldap_mini_section mo_boot_col-sm-12">
						<form name="mo_ldap_attribute_mapping_form" method="post"
									action="<?php echo Route::_(MoConstants::ACCOUNT_SETUP_BASE_URL . '.moLdapAttributeMapping'); ?>">
							<div class="mo_boot_row mo_boot_col-sm-12 mo_boot_mb-3">
								<div class="mo_boot_col-12 mo_boot_col-md-4 mo_boot_mb-2">
									<label class="form-label fw-medium"><?php echo Text::_('COM_MINIORANGE_JOOMLA_ATTRIBUTE'); ?></label>
								</div>
								<div class="mo_boot_col-12 mo_boot_col-md-7">
									<label class="form-label fw-medium"><?php echo Text::_('COM_MINIORANGE_ACTIVE_DIRECTORY_ATTRIBUTE'); ?></label>
								</div>
							</div>

							<?php
								// Email Mapping
								$emailMappingConfig = (new FormFieldConfig('email', Text::_('COM_MINIORANGE_SELECT_EMAIL')))
									->setType('dropdown')
									->setOptions($ldapAttrOptions)
									->setSelectedValue(isset($email) ? $email : '')
									->setPlaceholder('COM_MINIORANGE_SELECT_EMAIL')
									->setRequired(true)
									->setHelpTitle(Text::_('COM_MINIORANGE_SELECT_EMAIL_DESCRIPTION'))
									->setLayout(4, 7, 1);

								echo FormRenderer::renderField($emailMappingConfig);

								// Name Mapping
								$nameMappingConfig = (new FormFieldConfig('name_attr', Text::_('COM_MINIORANGE_NAME')))
									->setType('dropdown')
									->setOptions($ldapAttrOptions)
									->setSelectedValue(isset($name) ? $name : '')
									->setPlaceholder('COM_MINIORANGE_SELECT_NAME')
									->setRequired(true)
									->setHelpTitle(Text::_('COM_MINIORANGE_SELECT_NAME_DESCRIPTION'))
									->setLayout(4, 7, 1);

								echo FormRenderer::renderField($nameMappingConfig);

								// Username LDAP attribute (read-only; value follows LDAP Search Filter
								$usernameMappingConfig = (new FormFieldConfig('username_attr_display', Text::_('COM_MINIORANGE_USERNAME')))
									->setType('dropdown')
									->setOptions($ldapAttrOptions)
									->setSelectedValue(isset($searchFilter) ? $searchFilter : '')
									->setPlaceholder('COM_MINIORANGE_SELECT_USERNAME')
									->setRequired(false)
									->setDisabled(true)
									->setIsPremium(true)
									->setHelpTitle(Text::_('COM_MINIORANGE_SELECT_USERNAME_MAPPING_DESCRIPTION'))
									->setLayout(4, 7, 1);

								echo FormRenderer::renderField($usernameMappingConfig);
							?>

							<?php
								$saveAttrButton = (new FormFieldConfig('save_attr_mapping_btn', Text::_('COM_MINIORANGE_SAVE_CONFIGURATION')))
									->setType('button')
									->setButtonType('submit')
									->setBtnClass('primary')
									->setLayout(0, 12, 0)
									->setAttributes(array('mo_boot_col-sm-12' => 'mo_boot_col-sm-12 mo_boot_row mo_boot_justify-content-center'))
									->setIcon('fa fa-check')
									->setDisabled(!$searchFilter)
									->setTitle(!$searchFilter ? Text::_('COM_MINIORANGE_PLEASE_ADD_SEARCH_FILTER') : '');

								echo FormRenderer::renderField($saveAttrButton);
							?>
						</form>
					</div>
					<!-- User Profile Attributes Mapping -->
					<div class="mo_ldap_mini_section mo_boot_col-sm-12">
						<h5 class="mo_boot_row mo_boot_col-sm-12">
							<?php echo Text::_('COM_MINIORANGE_ADD_JOOMLA_USER_PROFILE_ATTRIBUTES'); ?>
							&nbsp;
							<i class="fa fa-info-circle"
								title="<?php echo Text::_('COM_MINIORANGE_USER_PROFILE_ATTRIBUTE_NOTE'); ?>"></i>
							<sup>
								<img class="crown_img_small mo_boot_ml-2"
										src="<?php echo Uri::base() . MoConstants::CROWN_IMAGE; ?>"
										title="<?php echo Text::_('COM_MINIORANGE_UPGRADE_TO_PREMIUM'); ?>">
							</sup>
						</h5>
						<div class="mo_boot_row mo_boot_mb-3">
							<div class="mo_boot_col-12 mo_boot_col-md-4 mo_boot_mb-2">
								<label class="form-label fw-medium"><?php echo Text::_('COM_MINIORANGE_JOOMLA_ATTRIBUTE'); ?></label>
							</div>
							<div class="mo_boot_col-12 mo_boot_col-md-7">
								<label class="form-label fw-medium"><?php echo Text::_('COM_MINIORANGE_ACTIVE_DIRECTORY_ATTRIBUTE'); ?></label>
							</div>
						</div>

						<?php
							// Address1 Mapping
							$addressMappingConfig = (new FormFieldConfig('address_mapping', Text::_('COM_MINIORANGE_ADDRESS1')))
								->setType('dropdown')
								->setOptions(array('streetAddress' => 'Street Address', 'postalAddress' => 'Postal Address', 'homePostalAddress' => 'Home Postal Address'))
								->setSelectedValue(isset($name) ? $name : '')
								->setPlaceholder('COM_MINIORANGE_SELECT_ATTRIBUTE')
								->setLayout(4, 7, 1);

							echo FormRenderer::renderField($addressMappingConfig);

							// City Mapping
							$cityMappingConfig = (new FormFieldConfig('city_mapping', Text::_('COM_MINIORANGE_CITY')))
								->setType('dropdown')
								->setOptions(array('l' => 'City / Locality (l)', 'localityName' => 'Locality Name'))
								->setSelectedValue(isset($name) ? $name : '')
								->setPlaceholder(Text::_('COM_MINIORANGE_SELECT_ATTRIBUTE'))
								->setLayout(4, 7, 1);

							echo FormRenderer::renderField($cityMappingConfig);

							// Phone Mapping
							$phoneMappingConfig = (new FormFieldConfig('phone_mapping', Text::_('COM_MINIORANGE_PHONE')))
								->setType('dropdown')
								->setOptions(array('telephoneNumber' => 'Telephone Number', 'mobile' => 'Mobile', 'homePhone' => 'Home Phone'))
								->setSelectedValue(isset($name) ? $name : '')
								->setPlaceholder(Text::_('COM_MINIORANGE_SELECT_ATTRIBUTE'))
								->setLayout(4, 7, 1);

							echo FormRenderer::renderField($phoneMappingConfig);

							$saveProfileButton = (new FormFieldConfig('save_profile_mapping_btn', Text::_('COM_MINIORANGE_ADD_MORE_PROFILE_ATTRIBUTES')))
								->setType('button')
								->setButtonType('submit')
								->setBtnClass('primary')
								->setLayout(0, 12, 0)
								->setAttributes(array('mo_boot_col-sm-12' => 'mo_boot_col-sm-12 mo_boot_row mo_boot_justify-content-center'))
								->setIcon('fa fa-plus')
								->setDisabled(true);

							echo FormRenderer::renderField($saveProfileButton);
						?>
					</div>

					<!-- User Field Attributes Mapping -->
					<div class="mo_ldap_mini_section mo_boot_col-sm-12">
						<h5 class="mo_boot_row mo_boot_col-sm-12">
							<?php echo Text::_('COM_MINIORANGE_ADD_JOOMLA_USER_FIELD_ATTRIBUTES'); ?>&nbsp;
							<i class="fa fa-info-circle"
								title="<?php echo Text::_('COM_MINIORANGE_USER_FIELD_ATTRIBUTE_NOTE'); ?>"></i>
							<sup>
								<img class="crown_img_small mo_boot_ml-2"
										src="<?php echo Uri::base() . MoConstants::CROWN_IMAGE; ?>"
										title="<?php echo Text::_('COM_MINIORANGE_UPGRADE_TO_PREMIUM'); ?>">
							</sup>
						</h5>

						<div class="mo_boot_row mo_boot_mb-3">
							<div class="mo_boot_col-12 mo_boot_col-md-4 mo_boot_mb-2 ">
								<label class="form-label fw-medium"><?php echo Text::_('COM_MINIORANGE_JOOMLA_USER_FIELD'); ?></label>
							</div>
							<div class="mo_boot_col-12 mo_boot_col-md-7">
								<label class="form-label fw-medium"><?php echo Text::_('COM_MINIORANGE_ACTIVE_DIRECTORY_ATTRIBUTE'); ?></label>
							</div>
						</div>

						<?php
							// Department Mapping
							$deptMappingConfig = (new FormFieldConfig('dept_mapping', Text::_('COM_MINIORANGE_DEPARTMENT')))
								->setType('dropdown')
								->setOptions(array('department' => 'Department', 'division' => 'Division', 'company' => 'Company'))
								->setSelectedValue(isset($name) ? $name : '')
								->setPlaceholder('COM_MINIORANGE_SELECT_ATTRIBUTE')
								->setLayout(4, 7, 1);

							echo FormRenderer::renderField($deptMappingConfig);

							// Job Mapping
							$jobMappingConfig = (new FormFieldConfig('job_mapping', Text::_('COM_MINIORANGE_JOB_TITLE')))
								->setType('dropdown')
								->setOptions(array('title' => 'Title', 'jobTitle' => 'Job Title', 'businessCategory' => 'Business Category'))
								->setSelectedValue(isset($name) ? $name : '')
								->setPlaceholder(Text::_('COM_MINIORANGE_SELECT_ATTRIBUTE'))
								->setLayout(4, 7, 1);

							echo FormRenderer::renderField($jobMappingConfig);

							// Manager Mapping
							$managerMappingConfig = (new FormFieldConfig('manager_mapping', Text::_('COM_MINIORANGE_MANAGER')))
								->setType('dropdown')
								->setOptions(array('manager' => 'Manager', 'supervisor' => 'Supervisor'))
								->setSelectedValue(isset($name) ? $name : '')
								->setPlaceholder(Text::_('COM_MINIORANGE_SELECT_ATTRIBUTE'))
								->setLayout(4, 7, 1);

							echo FormRenderer::renderField($managerMappingConfig);

							$saveFieldButton = (new FormFieldConfig('save_field_mapping_btn', Text::_('COM_MINIORANGE_ADD_MORE_FIELD_ATTRIBUTES')))
								->setType('button')
								->setButtonType('submit')
								->setBtnClass('primary')
								->setLayout(0, 12, 0)
								->setAttributes(array('mo_boot_col-sm-12' => 'mo_boot_col-sm-12 mo_boot_row mo_boot_justify-content-center'))
								->setIcon('fa fa-plus')
								->setDisabled(true);

							echo FormRenderer::renderField($saveFieldButton);
						?>
					</div>

					<!-- 4. Group Mapping Section -->
					<div class="mo_boot_col-sm-12 mo_boot_d-flex mo_boot_justify-content-between mo_boot_align-items-center mo_boot_mt-5">
						<h3 class="mo_ldap_sub_heading">
							<?php echo Text::_('COM_MINIORANGE_GROUP_MAPPING'); ?>
						</h3>
					</div>

					<?php
						// Get Group Mapping variables
						$moLdapServerConfig = new MoLdapConstants;
						$mappingValueDefault = $moLdapServerConfig->getMappingValueDefault();
						$enableRoleMapping = $moLdapServerConfig->getEnableRoleMapping();
						$groups = MoLdapUtility::moLdapGetJoomlaGroups();
					?>
					<form action="<?php echo Route::_(MoConstants::ACCOUNT_SETUP_BASE_URL . '.moLdapSaveRolemapping'); ?>" method="post" name="adminForm">
						<div class="mo_ldap_mini_section mo_boot_col-sm-12">
							<?php
								// Enable Role Mapping Toggle
								$enableRoleMappingConfig = (new FormFieldConfig('enable_role_mapping', Text::_('COM_MINIORANGE_ENABLE_GROUP_MAPPING')))
									->setType('toggle')
									->setChecked($enableRoleMapping == 1)
									->setLayout(9, 2, 0);

								echo FormRenderer::renderField($enableRoleMappingConfig);

								// Disable Update Existing Users Role Toggle
								$disableUpdateUsersConfig = (new FormFieldConfig('disable_update_existing_users_role', Text::_('COM_MINIORANGE_NO_UPDATE_EXISTING_USER')))
									->setType('toggle')
									->setDisabled(true)
									->setIsPremium(true)
									->setLayout(9, 2, 0);

								echo FormRenderer::renderField($disableUpdateUsersConfig);

								// Map Super Users Toggle
								$mapSuperUsersConfig = (new FormFieldConfig('map_super_users', Text::_('COM_MINIORANGE_MAP_SUPER_USERS')))
									->setType('toggle')
									->setDisabled(true)
									->setIsPremium(true)
									->setLayout(9, 2, 0);

								echo FormRenderer::renderField($mapSuperUsersConfig);

								// Select Default Group Mapping Dropdown
								$defaultGroupConfig = (new FormFieldConfig('mapping_value_default', Text::_('COM_MINIORANGE_SELECT_DEFAULT_GROUPS')))
									->setType('dropdown')
									->setOptions($groupOptions)
									->setSelectedValue($mappingValueDefault ?? '')
									->setPlaceholder('COM_MINIORANGE_SELECT_DEFAULT_GROUPS')
									->setLayout(7, 5);

								echo FormRenderer::renderField($defaultGroupConfig);

								// Save Group Mapping Button
								$saveGroupButton = (new FormFieldConfig('save_group_mapping_btn', Text::_('COM_MINIORANGE_SAVE_CONFIGURATION')))
									->setType('button')
									->setButtonType('submit')
									->setBtnClass('primary')
									->setLayout(0, 12, 0)
									->setAttributes(array('mo_boot_col-sm-12' => 'mo_boot_col-sm-12 mo_boot_row mo_boot_justify-content-center'))
									->setIcon('fa fa-check')
									->setDisabled(!$searchFilter);

								echo FormRenderer::renderField($saveGroupButton);
							?>
						</div>
					</form>
					<!-- 5. Custom Group Mapping Section -->
					<div class="mo_ldap_mini_section mo_boot_col-sm-12">
						<h5 class="mo_boot_row mo_boot_col-sm-12">
							<?php echo Text::_('COM_MINIORANGE_CUSTOM_GROUP_MAPPING'); ?>&nbsp;
							<i class="fa fa-info-circle mo_boot_ms-1"
								title="<?php echo Text::_('COM_MINIORANGE_GROUP_MAPPING_NOTE'); ?>"></i>
							<sup>
								<img class="crown_img_small"
										src="<?php echo Uri::base() . MoConstants::CROWN_IMAGE; ?>"
										title="<?php echo Text::_('COM_MINIORANGE_UPGRADE_TO_PREMIUM'); ?>">
							</sup>
						</h5>
						<div class="mo_boot_row g-3">
							<div class="mo_boot_col-md-6">
								<label class="form-label fw-medium"><?php echo Text::_('COM_MINIORANGE_LDAP_GROUP'); ?></label>
							</div>
							<div class="mo_boot_col-md-4">
								<label class="form-label fw-medium"><?php echo Text::_('COM_MINIORANGE_JOOMLA_GROUP'); ?></label>
							</div>
							<div class="mo_boot_col-md-1">
								<label class="form-label fw-medium"><?php echo Text::_('COM_MINIORANGE_ACTION'); ?></label>
							</div>
						</div>

						<!-- Sample Group Mappings -->
						<div class="mo_boot_row g-3">
							<div class="mo_boot_col-md-6">
								<select class="form-select mo_ldap_disabled_input form-label">
									<option><?php echo Text::_('COM_MINIORANGE_SELECT_LDAP_GROUP'); ?></option>
									<option>CN=Administrators,CN=Builtin,DC=example,DC=com</option>
									<option>CN=Users,CN=Builtin,DC=example,DC=com</option>
									<option>CN=PowerUsers,CN=Builtin,DC=example,DC=com</option>
								</select>
							</div>
							<div class="mo_boot_col-md-4">
								<select class="form-select mo_ldap_disabled_input form-label">
									<option><?php echo Text::_('COM_MINIORANGE_SELECT_JOOMLA_GROUP'); ?></option>
									<?php echo $joomlaGroupSelectOptionsHtml; ?>
								</select>
							</div>
							<div class="mo_boot_col-md-1">
								<button type="button"
										class="mo_boot_btn mo_boot_btn-outline-danger mo_boot_btn-sm">
									<i class="fa fa-minus mo_boot_me-2"></i>
								</button>
							</div>
						</div>

						<div class="mo_boot_row g-3">
							<div class="mo_boot_col-md-6">
								<select class="form-select mo_ldap_disabled_input form-label">
									<option><?php echo Text::_('COM_MINIORANGE_SELECT_LDAP_GROUP'); ?></option>
									<option>CN=Managers,OU=Groups,DC=example,DC=com
									</option>
									<option>CN=Developers,OU=Groups,DC=example,DC=com
									</option>
									<option>CN=Support,OU=Groups,DC=example,DC=com
									</option>
								</select>
							</div>
							<div class="mo_boot_col-md-4">
								<select class="form-select mo_ldap_disabled_input form-label">
									<option><?php echo Text::_('COM_MINIORANGE_SELECT_JOOMLA_GROUP'); ?></option>
									<?php echo $joomlaGroupSelectOptionsHtml; ?>
								</select>
							</div>
							<div class="mo_boot_col-md-1">
								<button type="button"
										class="mo_boot_btn mo_boot_btn-outline-danger mo_boot_btn-sm">
									<i class="fa fa-minus mo_boot_me-2"></i>
								</button>
							</div>
						</div>

						<?php
							$addGroupButton = (new FormFieldConfig('add_group_mapping_btn', Text::_('COM_MINIORANGE_ADD_GROUP_MAPPING')))
								->setType('button')
								->setButtonType('submit')
								->setBtnClass('primary')
								->setLayout(0, 12, 0)
								->setAttributes(array('mo_boot_col-sm-12' => 'mo_boot_col-sm-12 mo_boot_row mo_boot_justify-content-center'))
								->setIcon('fa fa-plus')
								->setDisabled(true);

							echo FormRenderer::renderField($addGroupButton);
						?>
					</div>
				</div>

				<!-- User Details Sidebar -->
				<div class="mo_boot_col-sm-12 mo_boot_col-xl-5">
					<div class="mo_ldap_mini_section mo_boot_col-sm-12">
						<h6 class="mo_boot_mb-3">
							<i class="fa fa-user-circle mo_boot_me-2"></i>
							<?php echo Text::_('COM_MINIORANGE_USER_DETAILS'); ?>
						</h6>

						<?php echo $userDetailsSidebarHtml; ?>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Renders the NTLM SSO configuration section.
 */
function moLdapNtlmSso()
{
	?>
	<div class="mo_boot_container-fluid mo_main_ldap_section">
		<div class="mo_boot_col-sm-12 mo_boot_row mo_boot_d-flex mo_boot_justify-content-between mo_boot_align-items-center">
			<h3 class="mo_ldap_sub_heading">
				<?php echo Text::_('COM_MINIORANGE_NTLM_SSO_TITLE'); ?>
				<sup>
					<img class="crown_img_small mo_boot_ml-2"
							src="<?php echo Uri::base() . MoConstants::CROWN_IMAGE; ?>"
							title="<?php echo Text::_('COM_MINIORANGE_UPGRADE_TO_PREMIUM'); ?>">
				</sup>
			</h3>

			<div class="mo_boot_d-flex mo_boot_gap-3">
				<a href="<?php echo MoConstants::NTLM_SSO_SETUP_GUIDE; ?>"
					target="_blank"
					class="mo_boot_text-dark">
					<i class="fa fa-question-circle mo_boot_me-1"></i>
					<?php echo Text::_('COM_MINIORANGE_NTLM_WHAT_IS_GUIDE'); ?>
				</a>
				<a href="<?php echo MoConstants::NTLM_AUTH_DOCS; ?>"
					target="_blank"
					class="mo_boot_text-dark">
					<i class="fa fa-book mo_boot_me-1"></i>
					<?php echo Text::_('COM_MINIORANGE_SETUP_GUIDE'); ?>
				</a>
			</div>
		</div>

		<!-- NTLM SSO Feature Card -->
		<div class="mo_boot_row">
			<form>
				<div class="mo_boot_col-sm-12 mo_boot_row">
					<div class="mo_ldap_mini_section mo_boot_col-sm-12">
						<?php
							// Enable Role Mapping Toggle
							$enableNtlmConfig = (new FormFieldConfig('enable_ntlm_kerberos', Text::_('COM_MINIORANGE_ENABLE_NTLM_KERBEROS_LOGIN')))
								->setType('toggle')
								->setDisabled(true)
								->setHelpTitle(Text::_('COM_MINIORANGE_NTLM_SSO_NOTE'))
								->setIsPremium(true)
								->setLayout(6, 5);

							echo FormRenderer::renderField($enableNtlmConfig);
						?>
					</div>

					<div class="mo_ldap_mini_section mo_boot_col-sm-12">
						<?php
							// NTLM Server Variable Input
							$ntlmServerConfig = (new FormFieldConfig('ntlm_server_variable', Text::_('COM_MINIORANGE_NTLM_SERVER_VARIABLE')))
								->setType('text')
								->setValue('REMOTE_USER')
								->setPlaceholder(Text::_('COM_MINIORANGE_NTLM_SERVER_VARIABLE'))
								->setDisabled(true)
								->setHelpTitle(Text::_('COM_MINIORANGE_NTLM_SERVER_VARIABLE'))
								->setIsPremium(true)
								->setLayout(6, 5);

							echo FormRenderer::renderField($ntlmServerConfig);

							// NTLM Strip Domain Toggle
							$stripDomainConfig = (new FormFieldConfig('strip_domain_variable', Text::_('COM_MINIORANGE_NTLM_STRIP_DOMAIN')))
								->setType('toggle')
								->setDisabled(true)
								->setHelpTitle(Text::_('COM_MINIORANGE_NTLM_STRIP_DOMAIN_DESC'))
								->setIsPremium(true)
								->setLayout(6, 5);

							echo FormRenderer::renderField($stripDomainConfig);
						?>
					</div>

					<div class="mo_ldap_mini_section mo_boot_col-sm-12">
						<?php
							// NTLM Disable User Input
							$ntlmDisableUserConfig = (new FormFieldConfig('ntlm_disable_user', Text::_('COM_MINIORANGE_USERNAME')))
								->setType('text')
								->setPlaceholder('Enter semicolon (;) sepearted username')
								->setDisabled(true)
								->setHelpTitle('Ex: username1;username2;username3')
								->setIsPremium(true)
								->setLayout(6, 5);

							echo FormRenderer::renderField($ntlmDisableUserConfig);
						?>
					</div>
					<?php
						$ntlmSaveButton = (new FormFieldConfig('ntlm_sso_btn', Text::_('COM_MINIORANGE_SAVE_NTLM_SETTINGS')))
							->setType('button')
							->setButtonType('submit')
							->setBtnClass('primary')
							->setLayout(0, 12, 0)
							->setAttributes(array('mo_boot_col-sm-12' => 'mo_boot_col-sm-12 mo_boot_row mo_boot_justify-content-center'))
							->setIcon('fa fa-check')
							->setDisabled(true);

						echo FormRenderer::renderField($ntlmSaveButton);
					?>
				</div>
			</form>
		</div>
	</div>
	<?php
}

function moLdapLicensingPlan()
{
	$useremail = MoLdapUtility::moLdapFetchData('#__miniorange_ldap_customer', array('id' => '1'), 'loadAssoc');

	if (isset($useremail))
	{
		$userEmail = $useremail['email'];
	}
	else
	{
		$userEmail = "xyz";
	}
	?>
	<div class="mo_boot_container-fluid mo_main_ldap_section">
		<div class="mo_boot_row">
			<div class="mo_boot_col-sm-12">
				<div class="mo_boot_col-sm-12 mo_boot_row mo_boot_d-flex mo_boot_justify-content-between mo_boot_align-items-center">
					<h3 class="mo_ldap_sub_heading"><?php echo Text::_('COM_MINIORANGE_FEATURE_COMPARISON'); ?></h3>
				</div>
			</div>

			<!-- Feature Comparison Section -->
			<div>
				<div class="mo_boot_mb-5">
					<div class="mo_boot_row">
						<?php
							echo FormRenderer::renderPlan(
								'free',
								Text::_('COM_MINIORANGE_PLAN_FREE'),
								'$0*',
								Text::_('COM_MINIORANGE_CURRENT_PLAN'),
								'button',
								array(Text::_('COM_MINIORANGE_BASIC_LDAP_AUTHENTICATION'), Text::_('COM_MINIORANGE_BASIC_PROFILE_MAPPING'), Text::_('COM_MINIORANGE_EXPORT_CONFIGURATION')),
								array(Text::_('COM_MINIORANGE_AUTO_USER_CREATION'), Text::_('COM_MINIORANGE_NTLM_KERBEROS_AUTHENTICATION'), Text::_('COM_MINIORANGE_CUSTOM_REDIRECT_URL'), Text::_('COM_MINIORANGE_SUPPORT_TLS_CONNECTION'), Text::_('COM_MINIORANGE_IGNORE_LDAPS_CERTIFICATE'), Text::_('COM_MINIORANGE_ADVANCED_MAPPING'), Text::_('COM_MINIORANGE_GROUP_SYNC'), Text::_('COM_MINIORANGE_LDAP_DIRECTORY_PASSWORD_SYNC'))
							);

							echo FormRenderer::renderPlan(
								'basic',
								Text::_('COM_MINIORANGE_PLAN_BASIC'),
								'$149*',
								Text::_('COM_MINIORANGE_CONTACT_US'),
								'link',
								array(Text::_('COM_MINIORANGE_EMAIL_SUPPORT'), Text::_('COM_MINIORANGE_EVERYTHING_IN_FREE'), Text::_('COM_MINIORANGE_AUTO_REGISTER_USERS'), Text::_('COM_MINIORANGE_ADVANCED_MAPPING'), Text::_('COM_MINIORANGE_SUPPORT_TLS_CONNECTION'), Text::_('COM_MINIORANGE_CUSTOM_REDIRECT_URL')),
								array(Text::_('COM_MINIORANGE_NTLM_KERBEROS_AUTHENTICATION'), Text::_('COM_MINIORANGE_IMPORT_USERS_FROM_AD'), Text::_('COM_MINIORANGE_USER_PASSWORD_SYNC_AD'), Text::_('COM_MINIORANGE_MULTIPLE_LDAP_SERVER_SUPPORT'), Text::_('COM_MINIORANGE_MULTIPLE_SEARCH_BASES')),
								false,
								MoConstants::LDAP_BASIC_PLAN_URL
							);

							echo FormRenderer::renderPlan(
								'premium',
								Text::_('COM_MINIORANGE_PLAN_PREMIUM'),
								'$449*',
								Text::_('COM_MINIORANGE_CONTACT_US'),
								'link',
								array(Text::_('COM_MINIORANGE_EVERYTHING_IN_BASIC'), Text::_('COM_MINIORANGE_PRIORITY_SUPPORT'), Text::_('COM_MINIORANGE_MULTIPLE_SEARCH_BASES'), Text::_('COM_MINIORANGE_CUSTOM_INTEGRATION_PAID')),
								array(Text::_('COM_MINIORANGE_DEDICATED_SUPPORT'), Text::_('COM_MINIORANGE_NTLM_KERBEROS_AUTHENTICATION'), Text::_('COM_MINIORANGE_MULTIPLE_LDAP_SERVER_SUPPORT'), Text::_('COM_MINIORANGE_IMPORT_USERS_FROM_AD'), Text::_('COM_MINIORANGE_USER_PASSWORD_SYNC_AD')),
								true, // Highlight this plan
								MoConstants::LDAP_PREMIUM_PLAN_URL
							);

							echo FormRenderer::renderPlan(
								'enterprise',
								Text::_('COM_MINIORANGE_PLAN_ENTERPRISE'),
								'$549*',
								Text::_('COM_MINIORANGE_CONTACT_US'),
								'link',
								array(Text::_('COM_MINIORANGE_EVERYTHING_IN_PREMIUM'), Text::_('COM_MINIORANGE_DEDICATED_SUPPORT'), Text::_('COM_MINIORANGE_CUSTOM_DEVELOPMENT'), Text::_('COM_MINIORANGE_NTLM_KERBEROS_AUTHENTICATION'), Text::_('COM_MINIORANGE_MULTIPLE_LDAP_SERVER_SUPPORT'), Text::_('COM_MINIORANGE_USER_PASSWORD_SYNC_AD'), Text::_('COM_MINIORANGE_IMPORT_USERS_FROM_AD')),
								array(),
								false,
								MoConstants::LDAP_ENTERPRISE_PLAN_URL
							);
						?>
					</div>
				</div>
			</div>

			<!-- Expandable Sections -->
			<div class="mo_boot_col-sm-12">
				<div class="mo_boot_row">
					<!-- How to Upgrade -->
					<div class="mo_ldap_mini_section mo_boot_col-sm-12 mo_boot_row">
						<div class="mo_boot_d-flex mo_boot_justify-content-between mo_boot_align-items-center mo_boot_cursor-pointer mo_boot_row mo_boot_col-sm-12"
							data-bs-toggle="collapse" data-bs-target="#upgrade-section">
							<h3><?php echo Text::_('COM_MINIORANGE_LDAP_UPGRADE_HEADER'); ?></h3>
							<i class="fa fa-plus"></i>
						</div>
						<div class="collapse" id="upgrade-section">
							<div class="mo_boot_row mo_boot_mt-3 mo_boot_col-sm-12">
								<div class="mo_boot_col-sm-6 mo_works-step mo_boot_d-flex">
									<div class="mo_ldap_step_number">1</div>
									<p class="mo_boot_mb-0"><?php echo Text::_('COM_MINIORANGE_LDAP_UPGRADE_STEP_ONE'); ?></p>
								</div>
								<div class="mo_boot_col-sm-6 mo_works-step mo_boot_d-flex">
									<div class="mo_ldap_step_number">4</div>
									<p class="mo_boot_mb-0"><?php echo Text::_('COM_MINIORANGE_LDAP_UPGRADE_FOUR'); ?></p>
								</div>
							</div>

							<div class="mo_boot_row mo_boot_col-sm-12">
								<div class="mo_boot_col-sm-6 mo_works-step mo_boot_d-flex">
									<div class="mo_ldap_step_number">2</div>
									<p class="mo_boot_mb-0"><?php echo Text::_('COM_MINIORANGE_LDAP_UPGRADE_STEP_TWO'); ?></p>
								</div>
								<div class="mo_boot_col-sm-6 mo_works-step mo_boot_d-flex">
									<div class="mo_ldap_step_number">5</div>
									<p class="mo_boot_mb-0"><?php echo Text::_('COM_MINIORANGE_LDAP_UPGRADE_FIVE'); ?></p>
								</div>
							</div>

							<div class="mo_boot_row mo_boot_col-sm-12">
								<div class="mo_boot_col-sm-6 mo_works-step mo_boot_d-flex">
									<div class="mo_ldap_step_number">3</div>
									<p class="mo_boot_mb-0"><?php echo Text::_('COM_MINIORANGE_LDAP_UPGRADE_STEP_THREE'); ?></p>
								</div>
								<div class="mo_boot_col-sm-6 mo_works-step mo_boot_d-flex">
									<div class="mo_ldap_step_number">6</div>
									<p class="mo_boot_mb-0"><?php echo Text::_('COM_MINIORANGE_LDAP_UPGRADE_SIX'); ?></p>
								</div>
							</div>
						</div>
					</div>
					<!-- End to end LDAP Server Integration -->
					<div class="mo_ldap_mini_section mo_boot_col-sm-12 mo_boot_row">
						<div class="mo_boot_d-flex mo_boot_justify-content-between mo_boot_align-items-center mo_boot_cursor-pointer mo_boot_row mo_boot_col-sm-12"
							 data-bs-toggle="collapse" data-bs-target="#ldap-integration">
							<h3><?php echo Text::_('COM_MINIORANGE_END_TO_END_LDAP_INTEGRATION'); ?></h3>
							<i class="fa fa-plus"></i>
						</div>
						<div class="collapse" id="ldap-integration">
								<p class="mo_boot_mb-2">
									<?php echo Text::_('COM_MINIORANGE_LDAP_INTEGRATION_DESCRIPTION'); ?>
									<?php echo Text::_('COM_MINIORANGE_CONFIGURATION_SERVICE_DESCRIPTION'); ?>
									<?php echo Text::_('COM_MINIORANGE_LICENSING_QUESTIONS_EMAIL'); ?>
								</p>
						</div>
					</div>

					<!-- Return Policy -->
					<div class="mo_ldap_mini_section mo_boot_col-sm-12 mo_boot_row">
						<div class="mo_boot_d-flex mo_boot_justify-content-between mo_boot_align-items-center mo_boot_cursor-pointer mo_boot_row mo_boot_col-sm-12"
							 data-bs-toggle="collapse" data-bs-target="#return-policy">
							<h3><?php echo Text::_('COM_MINIORANGE_RETURN_POLICY'); ?></h3>
							<i class="fa fa-plus"></i>
						</div>
						<div class="collapse mo_boot_mt-2" id="return-policy">
							<div class="mo_boot_p-2">
								<p class="mo_boot_mb-2">
									<?php echo Text::_('COM_MINIORANGE_RETURN_POLICY_SATISFACTION'); ?>
									<?php echo Text::_('COM_MINIORANGE_RETURN_POLICY_RESOLUTION'); ?>
								</p>
								<p class="mo_boot_mb-2">
									<strong><?php echo Text::_('COM_MINIORANGE_NOTE'); ?>:</strong> <?php echo Text::_('COM_MINIORANGE_RETURN_POLICY_EXCLUSIONS'); ?>
									<br>1. <?php echo Text::_('COM_MINIORANGE_RETURN_POLICY_CHANGE_MIND'); ?>
									<br>2. <?php echo Text::_('COM_MINIORANGE_RETURN_POLICY_INFRASTRUCTURE'); ?>
								</p>
								<p class="mo_boot_mb-1">
									<?php echo Text::_('COM_MINIORANGE_RETURN_POLICY_CONTACT_EMAIL'); ?>
								</p>
							</div>
						</div>
					</div>
					</div>
				</div>
			</div>
		</div>

	<?php
}

function moLoggers()
{
	$app = Factory::getApplication();
	$input = MoLdapUtility::moLdapGetApplicationInput($app);
	$moLdapServerConfig = new MoLdapConstants;
	$enableLoggers = $moLdapServerConfig->getEnableLoggers();
	$list = MoLdapLogger::getAllLogs();

	// Get filter parameters
	$searchTerm = $input->get('search', '', 'string');
	$levelFilter = $input->get('level', '', 'string');
	$dateFrom = $input->get('date_from', '', 'string');
	$dateTo = $input->get('date_to', '', 'string');
	$codeFilter = $input->get('code', '', 'string');
	$limit = $input->get('limit', 10, 'int');

	// Apply filters
	$filteredLogs = array();

	foreach ($list as $log)
	{
		$logData = json_decode($log->message, true);
		$issue = $logData['issue'] ?? '';
		$logCode = $logData['code'] ?? '';
		$logLevelName = get_object_vars($log)['log_level'] ?? '';
		$logLevel = strtolower($logLevelName);
		$logDate = strtotime($log->timestamp);

		// Search term filter
		if (!empty($searchTerm))
		{
			$searchableText = strtolower($issue . ' ' . $logCode . ' ' . $logLevelName);

			if (strpos($searchableText, strtolower($searchTerm)) === false)
			{
				continue;
			}
		}

		// Level filter
		if (!empty($levelFilter) && $logLevel !== strtolower($levelFilter))
		{
			continue;
		}

		// Date range filter
		if (!empty($dateFrom))
		{
			$fromTimestamp = strtotime($dateFrom . ' 00:00:00');

			if ($logDate < $fromTimestamp)
			{
				continue;
			}
		}

		if (!empty($dateTo))
		{
			$toTimestamp = strtotime($dateTo . ' 23:59:59');

			if ($logDate > $toTimestamp)
			{
				continue;
			}
		}

		// Code filter
		if (!empty($codeFilter) && strpos(strtolower($logCode), strtolower($codeFilter)) === false)
		{
			continue;
		}

		$filteredLogs[] = $log;
	}

	// Pagination Logic
	$totalLogs = count($filteredLogs);
	$totalPages = ceil($totalLogs / $limit);
	$currentPage = $input->get('page', 1, 'int');
	$currentPage = max(1, min($totalPages, max(1, $currentPage)));
	$startIndex = ($currentPage - 1) * $limit;

	// Slice the logs for the current page
	$paginatedLogs = array_slice($filteredLogs, $startIndex, $limit);

	// Calculate statistics
	$errorCount = 0;
	$warningCount = 0;
	$infoCount = 0;
	$noticeCount = 0;

	foreach ($filteredLogs as $log)
	{
		$logLevelName = get_object_vars($log)['log_level'] ?? '';
		$level = strtolower($logLevelName);

		switch ($level)
		{
			case 'error':
			case 'err':
			case 'critical':
			case 'alert':
				$errorCount++;
				break;
			case 'warning':
			case 'warn':
				$warningCount++;
				break;
			case 'info':
				$infoCount++;
				break;
			case 'notice':
				$noticeCount++;
				break;
		}
	}

	$loggerDisabledWarningHtml = '';

	if (!$enableLoggers)
	{
		$loggerDisabledWarningHtml = '<div class="mo_boot_col-sm-12 mo_boot_row">';
		$loggerDisabledWarningHtml .= '<div class="mo_ldap_mini_section mo_boot_col-sm-12 mo_boot_row">';
		$loggerDisabledWarningHtml .= '<i class="fa fa-exclamation-triangle text-warning"></i><div>';
		$loggerDisabledWarningHtml .= '<h6>' . Text::_('COM_MINIORANGE_LOGGER_DISABLED_TITLE') . '</h6>';
		$loggerDisabledWarningHtml .= '<p>' . Text::_('COM_MINIORANGE_LOGGER_DISABLED_MESSAGE') . '</p></div></div></div>';
	}

	$logsListSectionHtml = '';
	$logsPaginationHtml = '';

	if ($enableLoggers)
	{
		if (empty($paginatedLogs))
		{
			$hasFilters = !empty($searchTerm) || !empty($levelFilter) || !empty($dateFrom) || !empty($dateTo) || !empty($codeFilter);
			$logsListSectionHtml = '<div class="mo_boot_col-sm-12 mo_boot_row mo_boot_d-flex mo_boot_justify-content-center mo_boot_align-items-center mo_boot_mt-4 mo_boot_mb-5">';

			if ($hasFilters)
			{
				$logsListSectionHtml .= Text::_('COM_MINIORANGE_LOGGER_NO_FILTERED_LOGS');
				$logsListSectionHtml .= '<div class="mo_boot_col-sm-12 mo_boot_row mo_boot_d-flex mo_boot_justify-content-center mo_boot_align-items-center mo_boot_mt-4">';
				$logsListSectionHtml .= '<a href="index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=moLoggers" class="mo_boot_btn mo_boot_btn-primary">';
				$logsListSectionHtml .= '<i class="fa fa-times me-1"></i>' . Text::_('COM_MINIORANGE_LOGGER_CLEAR_FILTERS') . '</a></div>';
			}
			else
			{
				$logsListSectionHtml .= Text::_('COM_MINIORANGE_LOGGER_NO_LOGS');
			}

			$logsListSectionHtml .= '</div>';
		}
		else
		{
			$logsRowsHtml = '';

			foreach ($paginatedLogs as $log)
			{
				$logData = json_decode($log->message, true);

				if (!is_array($logData))
				{
					$logData = array('code' => '-', 'issue' => $log->message);
				}

				$issue = $logData['issue'] ?? '-';
				$logCode = $logData['code'] ?? '-';
				$logLevelName = get_object_vars($log)['log_level'] ?? '';
				$issueEsc = htmlspecialchars($issue, ENT_QUOTES, 'UTF-8');
				$logCodeEsc = htmlspecialchars($logCode, ENT_QUOTES, 'UTF-8');
				$logLevelEsc = htmlspecialchars($logLevelName, ENT_QUOTES, 'UTF-8');
				$dateFormatted = HTMLHelper::_('date', $log->timestamp, 'd M Y H:i');

				$logsRowsHtml .= '<tr><td>' . $dateFormatted . '</td><td><span>' . strtoupper($logLevelEsc) . '</span></td>';
				$logsRowsHtml .= '<td><code class="small">' . $logCodeEsc . '</code></td>';
				$logsRowsHtml .= '<td class="text-wrap">' . $issueEsc . '</td></tr>';
			}

			$logsListSectionHtml = '<div class="mo_ldap_mini_section mo_boot_col-sm-12 mo_boot_row">';
			$logsListSectionHtml .= '<table class="mo_ldap_logs_table mo_boot_col-sm-12" id="logsList">';
			$logsListSectionHtml .= '<caption class="visually-hidden">' . Text::_('COM_MINIORANGE_LOGGER_TABLE_CAPTION') . '</caption>';
			$logsListSectionHtml .= '<thead><tr><th scope="col" style="width: 20%;">' . Text::_('COM_MINIORANGE_LOGGER_DATE') . '</th>';
			$logsListSectionHtml .= '<th scope="col" style="width: 20%;">' . Text::_('COM_MINIORANGE_LOGGER_LEVEL') . '</th>';
			$logsListSectionHtml .= '<th scope="col" style="width: 15%;">' . Text::_('COM_MINIORANGE_LOGGER_CODE') . '</th>';
			$logsListSectionHtml .= '<th scope="col" style="width: 45%;">' . Text::_('COM_MINIORANGE_LOGGER_MESSAGE') . '</th></tr></thead><tbody>';
			$logsListSectionHtml .= $logsRowsHtml . '</tbody></table></div>';

			if ($totalPages > 1)
			{
				$baseUrl = 'index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=moLoggers';

				if (!empty($searchTerm))
				{
					$baseUrl .= '&search=' . urlencode($searchTerm);
				}

				if (!empty($levelFilter))
				{
					$baseUrl .= '&level=' . urlencode($levelFilter);
				}

				if (!empty($dateFrom))
				{
					$baseUrl .= '&date_from=' . urlencode($dateFrom);
				}

				if (!empty($dateTo))
				{
					$baseUrl .= '&date_to=' . urlencode($dateTo);
				}

				if (!empty($codeFilter))
				{
					$baseUrl .= '&code=' . urlencode($codeFilter);
				}

				if ($limit != 25)
				{
					$baseUrl .= '&limit=' . $limit;
				}

				$logsPaginationHtml = '<div class="mo_boot_d-flex justify-content-center mo_boot_col-sm-12">';
				$logsPaginationHtml .= '<nav aria-label="Logs pagination"><ul class="pagination pagination-sm">';

				if ($currentPage > 1)
				{
					$logsPaginationHtml .= '<li class="page-item"><a class="page-link" href="';
					$logsPaginationHtml .= htmlspecialchars(Route::_($baseUrl . '&page=' . ($currentPage - 1)), ENT_QUOTES, 'UTF-8');
					$logsPaginationHtml .= '"><i class="fa fa-chevron-left"></i></a></li>';
				}

				for ($page = 1; $page <= $totalPages; $page++)
				{
					$activeClass = ($page == $currentPage) ? ' active' : '';
					$logsPaginationHtml .= '<li class="page-item' . $activeClass . '"><a class="page-link" href="';
					$logsPaginationHtml .= htmlspecialchars(Route::_($baseUrl . '&page=' . $page), ENT_QUOTES, 'UTF-8');
					$logsPaginationHtml .= '">' . $page . '</a></li>';
				}

				if ($currentPage < $totalPages)
				{
					$logsPaginationHtml .= '<li class="page-item"><a class="page-link" href="';
					$logsPaginationHtml .= htmlspecialchars(Route::_($baseUrl . '&page=' . ($currentPage + 1)), ENT_QUOTES, 'UTF-8');
					$logsPaginationHtml .= '"><i class="fa fa-chevron-right"></i></a></li>';
				}

				$logsPaginationHtml .= '</ul></nav></div>';
			}
		}
	}

	$loggerEnabledBodyHtml = '';

	if ($enableLoggers)
	{
		$listIsEmpty = (count($list) == 0);
		$listDisabledClass = $listIsEmpty ? 'mo_ldap_disabled_input' : '';
		$listDisabledStyle = $listIsEmpty ? ' style="pointer-events: none; opacity: 0.6;"' : '';
		$searchTermEsc = htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8');
		$dateFromEsc = htmlspecialchars($dateFrom, ENT_QUOTES, 'UTF-8');
		$dateToEsc = htmlspecialchars($dateTo, ENT_QUOTES, 'UTF-8');
		$resetConfirm = htmlspecialchars(Text::_('COM_MINIORANGE_LOGGER_RESET_CONFIRMATION'), ENT_QUOTES, 'UTF-8');
		$formToken = HTMLHelper::_('form.token');
		$downloadDisabled = $listIsEmpty ? ' style="pointer-events: none; opacity: 0.6;" title="No logs to download"' : '';
		$resetDisabledClass = $listIsEmpty ? 'mo_ldap_disabled_input' : '';
		$resetDisabledAttrs = $listIsEmpty ? 'disabled title="No logs to reset"' : 'onclick="return confirm(\'' . $resetConfirm . '\');"';

		$loggerEnabledBodyHtml = '<div class="mo_boot_col-sm-12"><div>';
		$loggerEnabledBodyHtml .= '<div class="mo_boot_col-sm-12 ' . $listDisabledClass . '">';
		$loggerEnabledBodyHtml .= '<form method="get" action="" id="logger-filter-form"' . $listDisabledStyle . '>';
		$loggerEnabledBodyHtml .= '<input type="hidden" name="option" value="com_miniorange_dirsync">';
		$loggerEnabledBodyHtml .= '<input type="hidden" name="view" value="accountsetup">';
		$loggerEnabledBodyHtml .= '<input type="hidden" name="tab-panel" value="moLoggers">';
		$loggerEnabledBodyHtml .= '<div class="mo_boot_row mo_boot_g-2">';
		$loggerEnabledBodyHtml .= '<div class="mo_boot_col-md-4"><label for="search" class="form-label small fw-bold">';
		$loggerEnabledBodyHtml .= '<i class="fa fa-search me-1"></i>' . Text::_('COM_MINIORANGE_LOGGER_SEARCH') . '</label>';
		$loggerEnabledBodyHtml .= '<input type="text" class="form-control form-control-sm" id="search" name="search" value="' . $searchTermEsc . '"';
		$loggerEnabledBodyHtml .= ' placeholder="' . htmlspecialchars(Text::_('COM_MINIORANGE_LOGGER_SEARCH_PLACEHOLDER'), ENT_QUOTES, 'UTF-8') . '"></div>';
		$loggerEnabledBodyHtml .= '<div class="mo_boot_col-md-2"><label for="level" class="form-label small fw-bold">';
		$loggerEnabledBodyHtml .= '<i class="fas fa-layer-group me-1"></i>' . Text::_('COM_MINIORANGE_LOGGER_LEVEL_FILTER') . '</label>';
		$loggerEnabledBodyHtml .= '<select class="form-select form-select-sm" id="level" name="level">';
		$loggerEnabledBodyHtml .= '<option value="">' . Text::_('COM_MINIORANGE_LOGGER_LEVEL_ALL') . '</option>';
		$loggerEnabledBodyHtml .= '<option value="info"' . ($levelFilter === 'info' ? ' selected' : '') . '>' . Text::_('COM_MINIORANGE_LOGGER_LEVEL_INFO') . '</option>';
		$loggerEnabledBodyHtml .= '<option value="warning"' . ($levelFilter === 'warning' ? ' selected' : '') . '>' . Text::_('COM_MINIORANGE_LOGGER_LEVEL_WARNING') . '</option>';
		$loggerEnabledBodyHtml .= '<option value="error"' . ($levelFilter === 'error' ? ' selected' : '') . '>' . Text::_('COM_MINIORANGE_LOGGER_LEVEL_ERROR') . '</option>';
		$loggerEnabledBodyHtml .= '<option value="error"' . ($levelFilter === 'notice' ? ' selected' : '') . '>' . Text::_('COM_MINIORANGE_LOGGER_LEVEL_NOTICE') . '</option></select></div>';
		$loggerEnabledBodyHtml .= '<div class="mo_boot_col-md-2"><label for="date_from" class="form-label small fw-bold">';
		$loggerEnabledBodyHtml .= '<i class="fa fa-calendar me-1"></i>' . Text::_('COM_MINIORANGE_LOGGER_DATE_FROM') . '</label>';
		$loggerEnabledBodyHtml .= '<input type="date" class="form-control form-control-sm" id="date_from" name="date_from" value="' . $dateFromEsc . '"></div>';
		$loggerEnabledBodyHtml .= '<div class="mo_boot_col-md-2"><label for="date_to" class="form-label small fw-bold">';
		$loggerEnabledBodyHtml .= '<i class="fa fa-calendar me-1"></i>' . Text::_('COM_MINIORANGE_LOGGER_DATE_TO') . '</label>';
		$loggerEnabledBodyHtml .= '<input type="date" class="form-control form-control-sm" id="date_to" name="date_to" value="' . $dateToEsc . '"></div>';
		$loggerEnabledBodyHtml .= '<div class="mo_boot_col-md-2"><label for="limit" class="form-label small fw-bold">';
		$loggerEnabledBodyHtml .= '<i class="fa fa-list-ol me-1"></i>' . Text::_('COM_MINIORANGE_LOGGER_LIMIT') . '</label>';
		$loggerEnabledBodyHtml .= '<select class="form-select form-select-sm" id="limit" name="limit">';
		$loggerEnabledBodyHtml .= '<option value="10"' . ($limit == 10 ? ' selected' : '') . '>10</option>';
		$loggerEnabledBodyHtml .= '<option value="25"' . ($limit == 25 ? ' selected' : '') . '>25</option>';
		$loggerEnabledBodyHtml .= '<option value="50"' . ($limit == 50 ? ' selected' : '') . '>50</option>';
		$loggerEnabledBodyHtml .= '<option value="100"' . ($limit == 100 ? ' selected' : '') . '>100</option></select><br>';
		$loggerEnabledBodyHtml .= '<div class="mo_boot_row"><div class="mo_boot_col-sm-12">';
		$loggerEnabledBodyHtml .= '<div class="mo_boot_d-flex mo_boot_align-items-center mo_boot_justify-content-center mo_boot_gap-2">';
		$loggerEnabledBodyHtml .= '<button type="submit" class="mo_boot_btn mo_boot_btn-outline-primary">';
		$loggerEnabledBodyHtml .= '<i class="fas fa-filter mo_boot_me-2"></i>' . Text::_('COM_MINIORANGE_LOGGER_APPLY_FILTERS') . '</button>';
		$loggerEnabledBodyHtml .= '<a href="index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=moLoggers" class="mo_boot_btn mo_boot_btn-outline-primary btn-clear-filters">';
		$loggerEnabledBodyHtml .= '<i class="fas fa-times mo_boot_me-2"></i>' . Text::_('COM_MINIORANGE_LOGGER_CLEAR_FILTERS') . '</a>';
		$loggerEnabledBodyHtml .= '</div></div></div></div></div></form></div></div></div></div>';
		$loggerEnabledBodyHtml .= $logsListSectionHtml . $logsPaginationHtml;
		$loggerEnabledBodyHtml .= '<div class="mo_boot_row mo_boot_col-sm-12 mo_boot_justify-content-between mo_boot_align-items-center mo_boot_mt-4">';
		$loggerEnabledBodyHtml .= '<div class="mo_boot_col-sm-6">';
		$loggerEnabledBodyHtml .= '<a href="index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=moLoggers" class="mo_boot_btn mo_boot_btn-success mo_boot_text-decoration-none mo_boot_ms-2"';
		$loggerEnabledBodyHtml .= ' title="' . htmlspecialchars(Text::_('COM_MINIORANGE_FETCH_LATEST_LOGS'), ENT_QUOTES, 'UTF-8') . '">';
		$loggerEnabledBodyHtml .= '<i class="fa fa-refresh mo_boot_me-1"></i>' . Text::_('COM_MINIORANGE_FETCH_LATEST') . '</a>';
		$loggerEnabledBodyHtml .= '<a href="index.php?option=com_miniorange_dirsync&view=accountsetup&task=accountsetup.downloadLogs" class="mo_boot_btn mo_boot_btn-primary mo_boot_text-decoration-none"' . $downloadDisabled . '>';
		$loggerEnabledBodyHtml .= '<i class="fa fa-download mo_boot_me-1"></i>Download</a>';
		$loggerEnabledBodyHtml .= '<form method="post" action="index.php?option=com_miniorange_dirsync&view=accountsetup&task=accountsetup.resetLogs" style="display: inline;">';
		$loggerEnabledBodyHtml .= '<button type="submit" class="mo_boot_btn mo_boot_btn-primary ' . $resetDisabledClass . '" ' . $resetDisabledAttrs . '>';
		$loggerEnabledBodyHtml .= '<i class="fas fa-xmark mo_boot_me-1"></i>Reset</button>' . $formToken . '</form></div>';
		$loggerEnabledBodyHtml .= '<div class="mo_boot_col-sm-6 mo_boot_d-flex mo_boot_justify-content-end mo_boot_align-items-center mo_boot_gap-3">';
		$loggerEnabledBodyHtml .= '<span style="font-weight: bold;">Insights:</span>';
		$loggerEnabledBodyHtml .= '<span>Info: ' . (int) $infoCount . ' total</span>';
		$loggerEnabledBodyHtml .= '<span>Notice: ' . (int) $noticeCount . ' total</span>';
		$loggerEnabledBodyHtml .= '<span>Error: ' . (int) $errorCount . ' total</span>';
		$loggerEnabledBodyHtml .= '<span>Warning: ' . (int) $warningCount . ' total</span></div></div>';
	}

	?>
	<div class="mo_boot_container-fluid mo_main_ldap_section">
		<div id="mo_ldap_server_config_wrapper" class="mo_boot_col-sm-12">
			<div class="mo_boot_col-sm-12">
				<div class="mo_boot_col-sm-12 mo_boot_row">
					<div class="mo_boot_col-sm-12">
						<h3 class="mo_ldap_sub_heading mo_boot_mb-4">
							<?php echo Text::_('COM_MINIORANGE_LOGGER_TITLE'); ?>
						</h3>
					</div>
				</div>

					<!-- Logger Control Section -->
					<div class="mo_boot_col-sm-12 mo_boot_row mo_boot_align-items-center mo_boot_mt-5">
						<div class="mo_boot_col-sm-3 mo_boot_ms-5">
							<label class="form-label fw-medium">
								<?php echo Text::_('COM_MINIORANGE_ENABLE_LOGGERS'); ?>
							</label>
						</div>
						<div class="mo_boot_col-sm-2 mo_boot_d-flex mo_boot_align-items-center">
							<form method="post"
								  action="index.php?option=com_miniorange_dirsync&task=accountsetup.toggleLogger"
								  id="logger-toggle-form">
								<?php
								echo FormRenderer::renderSwitcher(array(
									'id'             => 'mo_ldap_logger_toggle',
									'name'           => 'mo_ldap_logger_toggle',
									'label'          => strip_tags(Text::_('COM_MINIORANGE_ENABLE_LOGGERS')),
									'value'          => $enableLoggers == 1 ? '1' : '0',
									'disabled'       => false,
									'required'       => false,
									'options'        => array(
										(object) array('value' => '0', 'text' => Text::_('JDISABLED')),
										(object) array('value' => '1', 'text' => Text::_('JENABLED')),
									),
									'autocomplete'   => false,
									'autofocus'      => false,
									'class'          => '',
									'description'    => '',
									'group'          => '',
									'hidden'         => false,
									'hint'           => '',
									'labelclass'     => '',
									'multiple'       => false,
									'onchange'       => '',
									'onclick'        => '',
									'pattern'        => '',
									'readonly'       => false,
									'repeat'         => false,
									'size'           => '',
									'spellcheck'     => false,
									'validate'       => '',
									'dataAttribute'  => '',
									'dataAttributes' => array(),
									)
								);
								?>
								<?php echo HTMLHelper::_('form.token'); ?>
							</form>
						</div>
					</div>

					<?php echo $loggerDisabledWarningHtml; ?>
					<?php echo $loggerEnabledBodyHtml; ?>
			</div>
		</div>
	</div>

	<input type="hidden" id="logger_list_data"
		   value='<?php echo htmlspecialchars(json_encode($list), ENT_QUOTES, 'UTF-8'); ?>'>
	<?php

}


// LDAP Provisioning Features
function moLdapProvisioning()
{
	?>
	<div class="mo_boot_container-fluid mo_main_ldap_section">
		<div class="mo_boot_col-sm-12 mo_boot_d-flex mo_boot_justify-content-between mo_boot_align-items-center">
			<h3 class="mo_ldap_sub_heading">
				<?php echo Text::_('COM_MINIORANGE_LDAP_PROVISIONING_FEATURES'); ?>
				<sup>
					<img class="crown_img_small"
							src="<?php echo Uri::base(); ?>/components/com_miniorange_dirsync/assets/images/crown.webp"
							title="<?php echo Text::_('COM_MINIORANGE_UPGRADE_TO_PREMIUM'); ?>">
				</sup>
			</h3>
		</div>

		<!-- Import User Feature -->
		<div class="mo_ldap_mini_section mo_boot_col-sm-12">
			<div class="mo_boot_row mo_boot_d-flex mo_boot_justify-content-between mo_boot_align-items-center mo_boot_col-sm-12">
				<h4 class="mo_boot_mb-3 mo_boot_sub_heading">
					<?php echo Text::_('COM_MINIORANGE_IMPORT_USER_FEATURE'); ?>
				</h4>
				<a href="<?php echo MoConstants::IMPORT_EXPORT_DOCS; ?>"
					target="_blank"
					class="mo_boot_text-dark">
					<i class="fa fa-book mo_boot_me-1"></i>
					<?php echo Text::_('COM_MINIORANGE_SETUP_GUIDE'); ?>
				</a>
			</div>

			<?php
				// Import Scope dropdown
				$importScopeConfig = (new FormFieldConfig('importScope', Text::_('COM_MINIORANGE_IMPORT_SCOPE')))
					->setType('dropdown')
					->setOptions(array('Select' => 'Select', 'All Users' => 'All Users', 'Specific OU' => 'Specific OU', 'Specific Group' => 'Specific Group'))
					->setPlaceholder('COM_MINIORANGE_SELECT_IMPORT_SCOPE')
					->setLayout(7, 2, 3);

				echo FormRenderer::renderField($importScopeConfig);

				// Import Frequency dropdown
				$importFrequencyConfig = (new FormFieldConfig('importFrequency', Text::_('COM_MINIORANGE_IMPORT_FREQUENCY')))
					->setType('dropdown')
					->setOptions(array('Select' => 'Select', 'Daily' => 'Daily', 'Weekly' => 'Weekly', 'Monthly' => 'Monthly'))
					->setPlaceholder('COM_MINIORANGE_SELECT_FREQUENCY')
					->setLayout(7, 2, 3);

				echo FormRenderer::renderField($importFrequencyConfig);

				// Start Import Button
				$startImportButton = (new FormFieldConfig('save_import', Text::_('COM_MINIORANGE_START_IMPORT')))
					->setType('button')
					->setButtonType('submit')
					->setBtnClass('primary')
					->setLayout(3, 5, 0)
					->setIcon('fa fa-download')
					->setDisabled(true);

				echo FormRenderer::renderField($startImportButton);
			?>
		</div>

		<!-- Directory Sync Feature -->
		<div class="mo_ldap_mini_section mo_boot_col-sm-12">
			<div class="mo_boot_row mo_boot_d-flex mo_boot_col-sm-12 mo_boot_justify-content-between mo_boot_align-items-center">
				<h4 class="mo_boot_sub_heading mo_boot_mb-5">
					<?php echo Text::_('COM_MINIORANGE_DIRECTORY_SYNC_FEATURE'); ?>
				</h4>
				<a href="<?php echo MoConstants::PASSWORD_SYNC_ADDON_DOCS; ?>"
					target="_blank"
					class="mo_boot_text-dark">
					<i class="fa fa-book mo_boot_me-1"></i>
					<?php echo Text::_('COM_MINIORANGE_SETUP_GUIDE'); ?>
				</a>
			</div>

			<?php
				// User Base DN
				$userBaseDnConfig = (new FormFieldConfig('ldapUserBaseDn', Text::_('COM_MINIORANGE_LDAP_ATTRIBUTE_FOR_USER_BASE_DN')))
					->setType('text')
					->setValue(isset($moRedirectUrl) ? htmlspecialchars($moRedirectUrl) : '')
					->setPlaceholder('cn')
					->setDisabled(true)
					->setHelpTitle(Text::_('COM_MINIORANGE_LDAP_ATTRIBUTE_FOR_USER_BASE_DN_DESC'))
					->setIsPremium(true)
					->setLayout(7, 2, 3);

				echo FormRenderer::renderField($userBaseDnConfig);

				// Create User on LDAP
				$createUserConfig = (new FormFieldConfig('createUserOnLdap', Text::_('COM_MINIORANGE_CREATE_USER_ON_LDAP')))
					->setType('checkbox')
					->setChecked(false)
					->setDisabled(true)
					->setIsPremium(true)
					->setLayout(7, 2, 3);

				echo FormRenderer::renderField($createUserConfig);

				// Update User Info on LDAP
				$updateUserConfig = (new FormFieldConfig('updateUserInfoOnLdap', Text::_('COM_MINIORANGE_UPDATE_USER_INFO_ON_LDAP')))
					->setType('checkbox')
					->setChecked(false)
					->setDisabled(true)
					->setIsPremium(true)
					->setLayout(7, 2, 3);

				echo FormRenderer::renderField($updateUserConfig);
			?>

			<div class="mb-4 ms-4">
				<small class="text-muted"><b><?php echo Text::_('COM_MINIORANGE_LDAPS_NOTE'); ?></b></small>
			</div>
			<?php
				// Save Sync Configuration Button
				$saveSyncButton = (new FormFieldConfig('save_sync', Text::_('COM_MINIORANGE_SAVE_CONFIGURATION')))
					->setType('button')
					->setButtonType('submit')
					->setBtnClass('primary')
					->setLayout(3, 5, 0)
					->setIcon('fa fa-check')
					->setDisabled(true);

				echo FormRenderer::renderField($saveSyncButton);
			?>

		</div>

	</div>
	<?php
}


function moLdapSupportTab()
{
	$app  = Factory::getApplication();
	$input = MoLdapUtility::moLdapGetApplicationInput($app);
	$currentUser = $app->getIdentity();
	$customerDetails = MoLdapUtility::moLdapFetchData('#__miniorange_ldap_customer', array('id' => '1'), 'loadAssoc');

	$adminEmail = isset($customerDetails['email']) ? htmlspecialchars($customerDetails['email'], ENT_QUOTES, 'UTF-8') : '';

	if ($adminEmail == '')
	{
		$adminEmail = htmlspecialchars($currentUser->email, ENT_QUOTES, 'UTF-8');
	}

	// Check both GET and POST parameters for query_type
	$getParams = $input->get->getArray();
	$postParams = $input->post->getArray();

	$typeOfQuery = '';

	if (isset($getParams['query_type']) && !empty($getParams['query_type']))
	{
		$typeOfQuery = htmlspecialchars(trim($getParams['query_type']), ENT_QUOTES, 'UTF-8');
	}
	elseif (isset($postParams['query_type']) && !empty($postParams['query_type']))
	{
		$typeOfQuery = htmlspecialchars(trim($postParams['query_type']), ENT_QUOTES, 'UTF-8');
	}

	if ($typeOfQuery == 'trial')
	{
		$headerText = Text::_('COM_MINIORANGE_FREE_TRIAL_REQUEST');
		$descriptionText = Text::_('COM_MINIORANGE_FREE_TRIAL_DESCRIPTION');
	}
	elseif ($typeOfQuery == 'configuration')
	{
		$headerText = Text::_('COM_MINIORANGE_SUPPORT_REQUEST');
		$descriptionText = Text::_('COM_MINIORANGE_SUPPORT_DESCRIPTION');
	}
	else
	{
		$headerText = Text::_('COM_MINIORANGE_SUPPORT_FEATURES');
		$descriptionText = Text::_('COM_MINIORANGE_SUPPORT_GENERAL_DESCRIPTION');
	}

	$supportDescriptionHtml = '';

	if ($typeOfQuery == 'trial' || $typeOfQuery == 'configuration')
	{
		$supportDescriptionHtml = '<div><div class="mo_boot_d-flex mo_boot_align-items-center"><div>';
		$supportDescriptionHtml .= '<p class="mo_boot_mt-1">' . $descriptionText . '</p></div></div></div>';
	}
	else
	{
		$supportDescriptionHtml = '<p class="mb-0">' . $descriptionText . '</p>';
	}

	$trialHiddenInputHtml = '';

	if ($typeOfQuery === 'trial')
	{
		$trialHiddenInputHtml = '<input type="hidden" name="mo_ldap_setup_call_issue" value="';
		$trialHiddenInputHtml .= htmlspecialchars(Text::_('COM_MINIORANGE_TRIAL_REQUEST'), ENT_QUOTES, 'UTF-8') . '">';
	}

	$issueTypeFieldHtml = '';

	if ($typeOfQuery !== 'trial')
	{
		$issueTypeFieldHtml = '<div class="mo_boot_mb-4">';
		$issueTypeFieldHtml .= '<label for="mo_ldap_setup_call_issue" class="form-label fw-bold">';
		$issueTypeFieldHtml .= Text::_('COM_MINIORANGE_ISSUE') . ' <span class="text-danger">*</span></label>';
		$issueTypeFieldHtml .= '<select class="form-select" name="mo_ldap_setup_call_issue" id="mo_ldap_setup_call_issue" required>';
		$issueTypeFieldHtml .= '<option value="" disabled>' . Text::_('COM_MINIORANGE_SELECT_ISSUE_TYPE') . '</option>';
		$issueTypeFieldHtml .= '<option id="mo_ldap_sso_setup_issue"' . ($typeOfQuery == 'setup_issue' ? ' selected' : '') . '>';
		$issueTypeFieldHtml .= Text::_('COM_MINIORANGE_SSO_SETUP_ISSUE') . '</option>';
		$issueTypeFieldHtml .= '<option id="mo_ldap_configuration_issues"' . ($typeOfQuery == 'configuration' ? ' selected' : '') . '>';
		$issueTypeFieldHtml .= Text::_('COM_MINIORANGE_CONFIGURATION_ISSUES') . '</option>';
		$issueTypeFieldHtml .= '<option id="mo_ldap_use_case_discussion"' . ($typeOfQuery == 'user_case' ? ' selected' : '') . '>';
		$issueTypeFieldHtml .= Text::_('COM_MINIORANGE_USECASE_DISCUSSION') . '</option>';
		$issueTypeFieldHtml .= '<option id="mo_ldap_trial_request"' . ($typeOfQuery == 'trial' ? ' selected' : '') . '>';
		$issueTypeFieldHtml .= Text::_('COM_MINIORANGE_TRIAL_REQUEST') . '</option>';
		$issueTypeFieldHtml .= '<option id="mo_ldap_contact_us"' . ($typeOfQuery == 'contact_us' ? ' selected' : '') . '>';
		$issueTypeFieldHtml .= Text::_('COM_MINIORANGE_CONTACT_US') . '</option>';
		$issueTypeFieldHtml .= '<option id="mo_ldap_get_quote"' . ($typeOfQuery == 'get_quote' ? ' selected' : '') . '>';
		$issueTypeFieldHtml .= Text::_('COM_MINIORANGE_GET_QUOTE') . '</option>';
		$issueTypeFieldHtml .= '<option id="mo_ldap_other_issue"' . ($typeOfQuery == 'other' ? ' selected' : '') . '>';
		$issueTypeFieldHtml .= Text::_('COM_MINIORANGE_OTHER') . '</option></select></div>';
	}

	$defaultQuery = '';

	if ($typeOfQuery == 'trial')
	{
		$defaultQuery = Text::_('COM_MINIORANGE_FREE_TRIAL_DEFAULT_QUERY');
	}
	elseif ($typeOfQuery == 'configuration')
	{
		$defaultQuery = Text::_('COM_MINIORANGE_SUPPORT_DEFAULT_QUERY');
	}

	$queryPlaceholder = !empty($defaultQuery) ? htmlspecialchars($defaultQuery, ENT_QUOTES, 'UTF-8') : Text::_('COM_MINIORANGE_SUPPORT_QUERY');

	?>
	<div class="mo_boot_container-fluid mo_main_ldap_section">
		<div class="mo_boot_row ">
			<!-- Header Section -->
			<div class="mo_boot_col-sm-12">
				<div class="mo_boot_row">
					<div class="mo_boot_col-sm-12 mo_boot_row mo_boot_py-3">
						<div class="mo_boot_col-sm-12 mo_boot_row mo_boot_d-flex mo_boot_justify-content-between mo_boot_align-items-center">
							<h3 class="mo_ldap_sub_heading mo_boot_mb-3">
								<?php echo $headerText; ?>
							</h3>
						</div>
					</div>
					<!-- Description Alert -->
					<div class="mo_boot_row mo_boot_mx-4">
						<div class="mo_boot_col-sm-12">
							<?php echo $supportDescriptionHtml; ?>
						</div>
					</div>
				</div>
			</div>

			<!-- Main Form Section -->
			<div class="mo_boot_col-sm-12">
				<div class="mo_boot_row mo_boot_justify-content-center">
					<!-- Centered Form Panel -->
					<div class="mo_boot_col-sm-8 mo_boot_col-md-7 mo_boot_col-lg-9">
						<div class="mo_ldap_mini_section">
							<div class="card-body">
								<form id="mo_ldap_contact_us" name="mo_ldap_contact_us"
									  method="post" action="<?php echo Route::_(MoConstants::SUPPORT_CONTACT_URL); ?>"
									  novalidate>
									<input type="hidden" name="mo_ldap_query_timezone" id="mo_ldap_query_timezone" value="">
									<?php echo $trialHiddenInputHtml; ?>
									<input type="hidden" name="query_type" value="<?php echo htmlspecialchars($typeOfQuery, ENT_QUOTES, 'UTF-8'); ?>">

									<!-- Email Field -->
									<div class="mb-4">
										<label for="mo_ldap_query_email" class="form-label fw-bold">
											<?php echo Text::_('COM_MINIORANGE_EMAIL');?> <span class="text-danger">*</span>
										</label>
										<input type="email"
											   class="form-control"
											   id="mo_ldap_query_email"
											   name="mo_ldap_query_email"
											   value="<?php echo $adminEmail; ?>"
											   placeholder="<?php echo Text::_('COM_MINIORANGE_SUPPORT_EMAIL');?>"
											   minlength="5"
											   maxlength="100"
											   required />
									</div>

									<!-- Issue Type Field -->
									<?php echo $issueTypeFieldHtml; ?>

									<!-- Query Field -->
									<div class="mb-4">
										<label for="mo_ldap_query" class="form-label fw-bold">
											<?php echo Text::_('COM_MINIORANGE_QUERY');?> <span class="text-danger">*</span>
										</label>
										<textarea id="mo_ldap_query"
												  class="form-control mo_boot_form-control"
												  name="mo_ldap_query"
												  placeholder="<?php echo $queryPlaceholder; ?>"
												  rows="3"
												  minlength="10"
												  maxlength="2000"
												  style="resize: vertical; height: auto; min-height: 6rem;"
												  required></textarea>
									</div>

									<!-- Configuration Checkbox -->
									<div class="mo_boot_mb-2">
										<div class="form-check form-switch">
											<input id="mo_ldap_query_withconfig"
												   class="form-check-input"
												   type="checkbox"
												   name="mo_ldap_query_withconfig"
												   value="1">
											<label class="form-check-label" for="mo_ldap_query_withconfig">
												<?php echo Text::_('COM_MINIORANGE_SEND_CONFIGURATION');?>
											</label>
										</div>
									</div>

									<!-- Submit Button -->
									<div class="text-center">
										<button type="submit" class="mo_boot_btn mo_boot_btn-primary btn-lg px-5 py-2">
											<i class="fa fa-paper-plane me-2"></i>
											<?php echo Text::_('COM_MINIORANGE_SUBMIT_QUERY'); ?>
										</button>
									</div>
								</form>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
}
