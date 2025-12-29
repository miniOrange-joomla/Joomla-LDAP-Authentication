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

Use Joomla\CMS\Plugin\PluginHelper;
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

// JSON File Path
$jsonFile = MoConstants::getAssetUrl('assets/json/tabs.json');

$context = stream_context_create([
"ssl" => [
  "verify_peer" => false,
  "verify_peer_name" => false,
]
]);

$tabsJsonString = file_get_contents($jsonFile, false, $context);

if ($tabsJsonString === false) {
    Factory::getApplication()->enqueueMessage('Failed to load JSON file.', 'warning');
    $tabs = [];
} else {
    $tabs = json_decode($tabsJsonString, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
      Factory::getApplication()->enqueueMessage('Failed to decode JSON: ' . json_last_error_msg(), 'warning');
      $tabs = [];
    }
}

if(MoLdapUtility::mo_ldap_is_extension_installed('curl')==0){ ?>
    <div class="mo_boot_alert mo_boot_alert-warning mo_boot_border mo_boot_border-3 mo_boot_border-primary mo_boot_bg-light mo_boot_p-3 mo_boot_rounded mo_boot_mb-3" >
        <p class="mo_ldap_highlight mo_boot_mb-0">
            <?php echo Text::_('COM_MINIORANGE_WARNING');?>:
            <?php echo sprintf(
                Text::_('COM_MINIORANGE_CURL_EXTENSION_DISABLED'),
                '<a href="http://php.net/manual/en/curl.installation.php" target="_blank">' . Text::_('COM_MINIORANGE_CURL_EXTENSION') . '</a>'
            ); ?>
        </p>
    </div>
<?php }
if (MoLdapUtility::mo_ldap_is_extension_installed('ldap') == 0) { ?>
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

$isSystemEnabled = PluginHelper::isEnabled('system','miniorangedirsync');
if(!$isSystemEnabled)
{?>
    <div id="system-message-container">
        <div class="alert alert-error">
            <h4 class="alert-heading"><?php echo Text::_('COM_MINIORANGE_WARNING');?></h4>
            <div class="alert-message">
                <?php echo Text::_('COM_MINIORANGE_ACTIVATE_SYSTEM_EXTENSION');?>
            </div>
            </h4>
        </div>
    </div>
<?php }

$dirsync_active_tab = 'ldapconfiguration';
$app  = Factory::getApplication();

// Check for tab-panel in GET parameters first (URL navigation)
$get_params = $app->input->get->getArray();
    
// Check for tab-panel in POST parameters (form submission)
$post_params = $app->input->post->getArray();

// Prioritize GET parameter (URL navigation), then POST parameter (form submission)
if(isset($get_params['tab-panel']) && !empty($get_params['tab-panel'])){
    $dirsync_active_tab = $get_params['tab-panel'];
} elseif(isset($post_params['tab-panel']) && !empty($post_params['tab-panel'])){
    $dirsync_active_tab = $post_params['tab-panel'];
}

function getDirectoryDetails(){
	return(MoLdapUtility::moLdapFetchData('#__miniorange_dirsync_config',array('id'=>'1'),'loadAssoc'));
}

function getCustomerDetails() {
	return(MoLdapUtility::moLdapFetchData('#__miniorange_ldap_customer',array('id'=>'1'),'loadAssoc'));
}

$j_version = new Version;
$jcms_version = $j_version->getShortVersion();


$version = new Version();
if (version_compare($version->getShortVersion(), '4.0', '<=')) {
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
    ?>

<div class="mo_boot_container-fluid">
    <div class="mo_boot_row mo_ldap_navbar">
        <?php foreach ($tabs as $key => $tab):
            $tabUrl = 'index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=' . $key;
        ?>
            <a id="<?php echo $tab['id']; ?>"
            class="mo_boot_col mo_ldap_nav-tab <?php echo $dirsync_active_tab == $key ? 'mo_nav_tab_active' : ''; ?>"
            href="<?php echo Route::_($tabUrl); ?>">
            <span><i class="fa fa-solid <?php echo $tab['icon']; ?>"></i></span>
            <span class="tab-label"><?php echo Text::_($tab['text']); ?></span>
            <?php if ($key === 'ntlmsso' || $key === 'addons'): ?>
                <sup><img class="crown_img_small mo_boot_ml-1 mo_ldap_cursor-type" src="<?php echo MoConstants::getImageUrl('crown.webp'); ?>" style="width: 16px; height: 16px;" onclick="mo_ldap_upgrade()" title="<?php echo Text::_('COM_MINIORANGE_UPGRADE_TO_PREMIUM'); ?>"></sup>
            <?php endif; ?>
            </a>
        <?php endforeach; ?>


    </div>
</div>
    <div class="tab-content mo_ldap_tab-content">
        <div id="ldapconfiguration" class="tab-pane <?php echo $dirsync_active_tab == 'ldapconfiguration' ? 'active' : ''; ?>">
            <div class="mo_boot_row">
                <div class="mo_boot_col-sm-12">
                    <div id="ldapConfigurationContent">
                        <?php moLdapConfiguration($dirsync_active_tab);?>
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
                                                <p class="mo_boot_text-muted mo_boot_mb-3">
                                                    <?php echo Text::_('COM_MINIORANGE_EXPORT_CONFIGURATION_DESCRIPTION'); ?>
                                                </p>
                                                
                                                <form id="exportConfigurationForm" method="post" action="<?php echo Route::_(MoConstants::LDAP_EXPORT_URL); ?>">
                                                    <button type="submit" class="mo_boot_btn mo_boot_btn-primary" id="exportBtn">
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

                                            <!-- Import Section -->
                                            <div class="mo_boot_mb-4 mo_ldap_mini_section">
                                                <h5 class="mo_boot_mb-3" style="font-weight: bold; color: #333;">
                                                    <?php echo Text::_('COM_MINIORANGE_IMPORT_CONFIGURATION'); ?>
                                                </h5>
                                                
                                                <form id="importConfigurationForm" method="post" action="<?php echo Route::_(MoConstants::LDAP_IMPORT_URL); ?>" enctype="multipart/form-data">   
                                                    <button type="submit" class="mo_boot_btn mo_boot_btn-primary mo_ldap_disabled_input" id="importBtn">
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
                                            
                                            <!-- Note Section -->
                                            <div>
                                                <strong>Note:</strong> <?php echo Text::_('COM_MINIORANGE_IMPORT_EXPORT_NOTE'); ?>
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

        <div id="signinsettings" class="tab-pane <?php echo $dirsync_active_tab == 'signinsettings' ? 'active' : ''; ?>">
            <div class="mo_boot_row">
                <div class="mo_boot_col-sm-12" >
                    <?php moLdapLoginSettings();?>
                </div>
            </div>
        </div>

        <div id="attributerolemapping" class="tab-pane <?php echo $dirsync_active_tab == 'attributerolemapping' ? 'active' : ''; ?>">
            <div class="mo_boot_row">
                <div class="mo_boot_col-sm-12" >
                    <?php moLdapAttributeMapping();?>
                </div>
            </div>
        </div>

        <div id="ntlmsso" class="tab-pane <?php echo $dirsync_active_tab == 'ntlmsso' ? 'active' : ''; ?>">
            <div class="mo_boot_row">
                <div class="mo_boot_col-12">
                    <?php moLdapNtlmSso();?>
                </div>
            </div>
        </div>

        <div id="addons" class="tab-pane <?php echo $dirsync_active_tab == 'addons' ? 'active' : ''; ?>">
            <div class="mo_boot_row">
                <div class="mo_boot_col-sm-12">
                    <?php moLdapProvisioning();?>
                </div>
            </div>
        </div>

        <div id="mo_ldap_trial_demo" class="tab-pane <?php echo $dirsync_active_tab == 'mo_ldap_trial_demo' ? 'active' : ''; ?>">
            <div class="mo_boot_row">
                <div class="mo_boot_col-sm-12" >
                    <?php moLdapSupportTab();?>
                </div>
            </div>
        </div>

        <div id="licensing" class="tab-pane <?php echo $dirsync_active_tab == 'licensing' ? 'active' : ''; ?>">
            <div class="mo_boot_row">
                <div class="mo_boot_col-sm-12" >
                    <?php moLdapLicensingPlan();?>
                </div>
            </div>
        </div>

        <div id="loggers" class="tab-pane <?php echo $dirsync_active_tab == 'moLoggers' ? 'active' : ''; ?>">
            <div class="mo_boot_row">
                <div class="mo_boot_col-sm-12" >
                    <?php moLoggers();?>
                </div>
            </div>
        </div>
    </div>
<?php


function moLdapConfiguration($dirsync_active_tab = 'ldapconfiguration')
{
    $moLdapServerConfig = new MoLdapConstants;
    $ldap_type = "";
    $ldap_port = "";
    $ldapServerUrl = "";
    $searchBase = "";
    $serviceAccountDn = "";
    $serviceAccountPassword = "";
    $serverType = $moLdapServerConfig->getServerType();
    $ignoreLdaps = "";
    $enableTls = "";
    $searchFilter = "";
    $testUsername = "";
    $isTestServer =false;
    $ActiveDirectoryAttributes = $moLdapServerConfig->getActiveDirectoryAttributes();
    if(!empty($moLdapServerConfig->getServerURL()))
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

        $ldap_type = strtok($ldapServerUrl, '://');
        $ldap_port = substr($ldapServerUrl, strrpos($ldapServerUrl, ':' )+1);
        $ldapServerUrlSub = substr($ldapServerUrl, strrpos($ldapServerUrl, '://' )+3);
        $ldapServerUrl =strtok($ldapServerUrlSub, ':');
        $isTestServer = (strpos($ldapServerUrl, 'ldap.forumsys.com') !== false);

    }

    if(empty($ldap_port))
    {
        $ldap_port = "389";
    }
    ?>
    <div class="mo_boot_container-fluid mo_main_ldap_section">
        <div id="mo_ldap_server_config" class="mo_boot_col-sm-12">
            <div class="mo_boot_col-sm-12" id="mo_ldap_server_configuration">
                <div class="mo_boot_col-sm-12 mo_boot_d-flex mo_boot_justify-content-between mo_boot_align-items-center">
                    <h3 class="mo_ldap_sub_heading"><?php echo Text::_('COM_MINIORANGE_LDAP_CONNECTION'); ?></h3>
                    <div class="mo_boot_d-flex mo_boot_justify-content-end mo_boot_gap-3">
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
                            <input type="hidden" id="current_tab_ldap_config" name="current_tab" value="<?php echo $dirsync_active_tab; ?>">

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
                                        <?php echo Text::_('COM_MINIORANGE_LDAP_SERVER_URL'); ?> : <span
                                                class="mo_ldap_highlight">*</span>
                                        <i class="fa fa-info-circle mo_boot_ms-1"
                                           title="<?php echo Text::_('COM_MINIORANGE_LDAP_SERVER_URL_HELP'); ?>"></i>
                                    </label>
                                </div>
                                <div class="mo_boot_col-12 mo_boot_col-md-7">
                                    <div class="mo_boot_d-flex mo_boot_flex-column mo_boot_flex-md-row mo_boot_gap-2">
                                        <select class="form-select" id="mo_ldap_type" name="mo_ldap_type"
                                                style="min-width: 120px; max-width: 200px;">
                                            <option value="ldap" <?php if ($ldap_type == 'ldap') echo 'selected'; ?>><?php echo Text::_('COM_MINIORANGE_LDAP'); ?></option>
                                            <option value="ldaps" <?php if ($ldap_type == 'ldaps') echo 'selected'; ?>><?php echo Text::_('COM_MINIORANGE_LDAPS'); ?></option>
                                        </select>
                                        <input class="form-control" id="mo_ldap_server_url" name="mo_ldap_server_url"
                                               type="text"
                                               placeholder="<?php echo Text::_('COM_MINIORANGE_LDAP_SERVER_URL_PLACEHOLDER'); ?>"
                                               value='<?php echo $ldapServerUrl; ?>' required>
                                        <input class="form-control" id="mo_ldap_port" name="mo_ldap_port" type="text"
                                               placeholder="<?php echo Text::_('COM_MINIORANGE_PORT_NO_PLACEHOLDER'); ?>"
                                               value='<?php echo $ldap_port; ?>' style="min-width: 80px; max-width: 120px;">
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
                                    ->setAttributes(['mo_boot_col-sm-12' => 'mo_boot_col-sm-12 mo_boot_row mo_boot_justify-content-center']);
                                
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
                            <input type="hidden" id="current_tab_mapping" name="current_tab" value="<?php echo $dirsync_active_tab; ?>">
                            
                            <div class="mo_ldap_mapping_config">
                                <!-- Search Base -->
                                <div class="mo_boot_row">
                                    <div class="mo_boot_col-12 mo_boot_col-md-4">
                                        <label for="search_base" class="form-label fw-medium">
                                            <?php echo Text::_('COM_MINIORANGE_SEARCH_BASE'); ?>: <span
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
                                        <button type="button" class="mo_boot_btn mo_boot_btn-outline-secondary mo_boot_w-100"
                                                onclick="mo_ldap_possible_search_bases()"><?php echo Text::_('COM_MINIORANGE_POSSIBLE_SEARCH_BASES'); ?></button>
                                    </div>
                                </div>

                                <!-- Search Filter -->
                                <div class="mo_boot_row">
                                    <div class="mo_boot_col-12 mo_boot_col-md-4">
                                        <label for="search_filter" class="form-label fw-medium">
                                            <?php echo Text::_('COM_MINIORANGE_SEARCH_FILTER'); ?>: <span
                                                    class="mo_ldap_highlight">*</span>
                                            <i class="icon-info-circle mo_boot_ms-1"
                                               title="<?php echo Text::_('COM_MINIORANGE_SEARCH_FILTER_HELP'); ?>"></i>
                                        </label>
                                    </div>
                                    <div class="mo_boot_col-12 mo_boot_col-md-7">
                                        <select name="search_filter" id="search_filter" class="form-select" required>
                                            <option value="" disabled><?php echo Text::_('COM_MINIORANGE_SELECT_USERNAME'); ?></option>
                                            <?php foreach (MoConstants::LDAP_SERVER_ATTRIBUTES as $value => $text): ?>
                                                <option value="<?php echo htmlspecialchars($value); ?>" <?php echo (isset($searchFilter) && $searchFilter == $value) ? 'selected' : ''; ?>>
                                                    <?php echo Text::_($text); ?>
                                                </option>
                                            <?php endforeach; ?>
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
                                    <button type="submit" class="mo_boot_btn mo_boot_btn-primary mo_boot_px-4 mo_boot_py-2 mo_boot_me-2">
                                        <i class="fa fa-cog mo_boot_me-1"></i>
                                        <?php echo Text::_('COM_MINIORANGE_TEST_AUTHENTICATION_AND_SAVE'); ?>
                                    </button>
                                    <button type="submit" class="mo_boot_btn mo_boot_btn-primary mo_boot_px-4 mo_boot_py-2" onclick="checkLdapAttributes()">
                                        <i class="fa fa-search mo_boot_me-1"></i>
                                        <?php echo Text::_('COM_MINIORANGE_CHECK_ATTRIBUTES_RECEIVING'); ?>
                                    </button>
                                    </div>
                                <div class="mo_boot_col-sm-1"><!-- right spacer --></div>
                                </div>
                            <?php
                        ?>
                        </form>
                </div>

                <!-- Configuration Management Buttons -->
                <div class="mo_boot_row mo_boot_col-sm-12 mo_boot_d-flex mo_boot_gap-3 mo_boot_mt-5">
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
    $enableLdap =	$moLdapServerConfig->getEnableLdap();
    $searchFilter = $moLdapServerConfig->getSearchFilter();
    $mo_ldap_configuration = MoLdapUtility::mo_ldap_get_details('#__miniorange_dirsync_config');
    $mo_redirect_url = isset($mo_ldap_configuration['redirect_url']) ? $mo_ldap_configuration['redirect_url'] : "";

    $groups = MoLdapUtility::mo_ldap_get_joomla_groups();
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
                                
                                echo FormRenderer::renderField($enableLoginConfig);
                                echo Text::_('COM_MINIOARNGE_ENABLE_LOGIN_DETAILS_INFO');
                            ?>
                        </div>
                        
                        <!--  Redirect URL Configuration -->
                        <div class="mo_ldap_mini_section mo_boot_col-sm-12">
                            <?php
                                $redirectUrlConfig = (new FormFieldConfig('mo_ldap_redirect_url', Text::_('COM_MINIORANGE_REDIRECT_URL')))
                                    ->setType('text')
                                    ->setValue(isset($mo_redirect_url) ? htmlspecialchars($mo_redirect_url) : '')
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
                                <?php foreach ($groups as $group) {
                                    if ($group[0] != '8') { ?>
                                        <div class="mo_boot_col-sm-3">
                                            <div class="mo_boot_form-check">
                                                <input type="checkbox" class="form-check-input"
                                                    name="selected_groups[]" value="<?php echo $group[0]; ?>"
                                                    id="group_<?php echo $group[0]; ?>" checked disabled/>
                                                <label for="group_<?php echo $group[0]; ?>"
                                                    class="form-check-label"> <?php echo $group[4]; ?> </label>
                                            </div>
                                        </div>
                                    <?php }
                                } ?>
                            </div>
                        </div>

                        <!-- Save Settings Button -->
                        <?php
                            $saveLoginButton = (new FormFieldConfig('save_login_seeting_btn', Text::_('COM_MINIORANGE_SAVE_CONFIGURATION')))
                                ->setType('button')
                                ->setButtonType('submit')
                                ->setBtnClass('primary')
                                ->setLayout(0, 12, 0)
                                ->setAttributes(['mo_boot_col-sm-12' => 'mo_boot_col-sm-12 mo_boot_row mo_boot_justify-content-center'])
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

function moLdapAttributeMapping(){

    $moLdapServerConfig = new MoLdapConstants;
    $username = $moLdapServerConfig->getSearchFilter();
    $email = $moLdapServerConfig->getEmailAttribute();
    $name = $moLdapServerConfig->getNameAttribute();
    $searchFilter = $moLdapServerConfig->getSearchFilter();
    $ActiveDirectoryUserAttributes = $moLdapServerConfig->getTestConfigDetails();
    $UserName = $moLdapServerConfig->getTestConfigUsername();
    ?>
<!--    No need to again select the username-->
    <input type="hidden"
           name="username"
           value="<?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>" />
    <div class="mo_boot_container-fluid mo_main_ldap_section">
        <div id="mo_ldap_server_config_wrapper" class="mo_boot_col-sm-12">
            <div class="mo_boot_col-sm-12 mo_boot_d-flex mo_boot_justify-content-between mo_boot_align-items-center" id="mo_ldap_server_configuration">
                <h3 class="mo_ldap_sub_heading">
                    <?php echo Text::_('COM_MINIORANGE_ATTRIBUTE_AND_GROUP_MAPPING'); ?>
                </h3>
            </div>

            <!-- Main Content and Sidebar Layout -->
            <div class="mo_boot_col-sm-12 mo_boot_row">
                <div class="mo_boot_col-sm-7">
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
                                    ->setOptions(MoConstants::LDAP_SERVER_ATTRIBUTES)
                                    ->setSelectedValue(isset($email) ? $email : '')
                                    ->setPlaceholder('COM_MINIORANGE_SELECT_USERNAME')
                                    ->setRequired(true)
                                    ->setLayout(4, 7, 1);
                                
                                echo FormRenderer::renderField($emailMappingConfig);
                                // Name Mapping
                                $nameMappingConfig = (new FormFieldConfig('name_attr', Text::_('COM_MINIORANGE_NAME')))
                                    ->setType('dropdown')
                                    ->setOptions(MoConstants::LDAP_SERVER_ATTRIBUTES)
                                    ->setSelectedValue(isset($name) ? $name : '')
                                    ->setPlaceholder('COM_MINIORANGE_SELECT_NAME')
                                    ->setRequired(true)
                                    ->setLayout(4, 7, 1);
                                
                                echo FormRenderer::renderField($nameMappingConfig);
                            ?>
                            
                            <?php
                                $saveAttrButton = (new FormFieldConfig('save_attr_mapping_btn', Text::_('COM_MINIORANGE_SAVE_CONFIGURATION')))
                                    ->setType('button')
                                    ->setButtonType('submit')
                                    ->setBtnClass('primary')
                                    ->setLayout(0, 12, 0)
                                    ->setAttributes(['mo_boot_col-sm-12' => 'mo_boot_col-sm-12 mo_boot_row mo_boot_justify-content-center'])
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
                                ->setOptions(['select' => 'Select', 'streetAddress' => 'Street Address', 'postalAddress' => 'Postal Address', 'homePostalAddress' => 'Home Postal Address'])
                                ->setSelectedValue(isset($name) ? $name : '')
                                ->setPlaceholder('COM_MINIORANGE_SELECT_ATTRIBUTE')
                                ->setLayout(4, 7, 1);
                            
                            echo FormRenderer::renderField($addressMappingConfig);
                            // City Mapping
                            $cityMappingConfig = (new FormFieldConfig('city_mapping', Text::_('COM_MINIORANGE_CITY')))
                                ->setType('dropdown')
                                ->setOptions(['select' => 'Select', 'l' => 'L', 'localityName' => 'Locality Name'])
                                ->setSelectedValue(isset($name) ? $name : '')
                                ->setPlaceholder(Text::_('COM_MINIORANGE_SELECT_ATTRIBUTE'))
                                ->setLayout(4, 7, 1);
                            
                            echo FormRenderer::renderField($cityMappingConfig);
                            // Phone Mapping
                            $phoneMappingConfig = (new FormFieldConfig('phone_mapping', Text::_('COM_MINIORANGE_PHONE')))
                                ->setType('dropdown')
                                ->setOptions(['select' => 'Select', 'telephoneNumber' => 'Telephone Number', 'mobile' => 'Mobile', 'homePhone' => 'Home Phone'])
                                ->setSelectedValue(isset($name) ? $name : '')
                                ->setPlaceholder(Text::_('COM_MINIORANGE_SELECT_ATTRIBUTE'))
                                ->setLayout(4, 7, 1);
                            
                            echo FormRenderer::renderField($phoneMappingConfig);
                            
                            $saveProfileButton = (new FormFieldConfig('save_profile_mapping_btn', Text::_('COM_MINIORANGE_ADD_MORE_PROFILE_ATTRIBUTES')))
                                ->setType('button')
                                ->setButtonType('submit')
                                ->setBtnClass('primary')
                                ->setLayout(0, 12, 0)
                                ->setAttributes(['mo_boot_col-sm-12' => 'mo_boot_col-sm-12 mo_boot_row mo_boot_justify-content-center'])
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
                                ->setOptions(['select' => 'Select', 'department' => 'Department', 'division' => 'Division', 'company' => 'Company'])
                                ->setSelectedValue(isset($name) ? $name : '')
                                ->setPlaceholder('COM_MINIORANGE_SELECT_ATTRIBUTE')
                                ->setLayout(4, 7, 1);
                            
                            echo FormRenderer::renderField($deptMappingConfig);
                            // Job Mapping
                            $jobMappingConfig = (new FormFieldConfig('job_mapping', Text::_('COM_MINIORANGE_JOB_TITLE')))
                                ->setType('dropdown')
                                ->setOptions(['select' => 'Select', 'title' => 'Title', 'jobTitle' => 'Job Title', 'businessCategory' => 'Business Category'])
                                ->setSelectedValue(isset($name) ? $name : '')
                                ->setPlaceholder(Text::_('COM_MINIORANGE_SELECT_ATTRIBUTE'))
                                ->setLayout(4, 7, 1);
                            
                            echo FormRenderer::renderField($jobMappingConfig);
                            // Manager Mapping
                            $managerMappingConfig = (new FormFieldConfig('manager_mapping', Text::_('COM_MINIORANGE_MANAGER')))
                                ->setType('dropdown')
                                ->setOptions(['select' => 'Select', 'manager' => 'Manager', 'supervisor' => 'Supervisor'])
                                ->setSelectedValue(isset($name) ? $name : '')
                                ->setPlaceholder(Text::_('COM_MINIORANGE_SELECT_ATTRIBUTE'))
                                ->setLayout(4, 7, 1);
                            
                            echo FormRenderer::renderField($managerMappingConfig);
                            
                            $saveFieldButton = (new FormFieldConfig('save_field_mapping_btn', Text::_('COM_MINIORANGE_ADD_MORE_FIELD_ATTRIBUTES')))
                                ->setType('button')
                                ->setButtonType('submit')
                                ->setBtnClass('primary')
                                ->setLayout(0, 12, 0)
                                ->setAttributes(['mo_boot_col-sm-12' => 'mo_boot_col-sm-12 mo_boot_row mo_boot_justify-content-center'])
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
                        $mapping_value_default = $moLdapServerConfig->getMappingValueDefault();
                        $enable_role_mapping = $moLdapServerConfig->getEnableRoleMapping();
                        $groups = MoLdapUtility::mo_ldap_get_joomla_groups();
                    ?>
                    <form action="<?php echo Route::_(MoConstants::ACCOUNT_SETUP_BASE_URL . '.moLdapSaveRolemapping'); ?>" method="post" name="adminForm">
                        <div class="mo_ldap_mini_section mo_boot_col-sm-12">
                            <?php
                                // Enable Role Mapping Toggle
                                $enableRoleMappingConfig = (new FormFieldConfig('enable_role_mapping', Text::_('COM_MINIORANGE_ENABLE_GROUP_MAPPING')))
                                    ->setType('toggle')
                                    ->setChecked($enable_role_mapping == 1)
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
                                $groupOptions = [];
                                foreach ($groups as $group) {
                                    if (!in_array($group[4], ['Super Users'])) {
                                        $groupOptions[$group[0]] = $group[4]; // value => text
                                    }
                                }
                                // Select Default Group Mapping Dropdown
                                $defaultGroupConfig = (new FormFieldConfig('mapping_value_default', Text::_('COM_MINIORANGE_SELECT_DEFAULT_GROUPS')))
                                    ->setType('dropdown')
                                    ->setOptions($groupOptions)
                                    ->setSelectedValue($mapping_value_default ?? '')
                                    ->setPlaceholder('COM_MINIORANGE_SELECT_DEFAULT_GROUPS')
                                    ->setLayout(7, 5, );
                                
                                echo FormRenderer::renderField($defaultGroupConfig);
                                
                                // Save Group Mapping Button
                                $saveGroupButton = (new FormFieldConfig('save_group_mapping_btn', Text::_('COM_MINIORANGE_SAVE_CONFIGURATION')))
                                    ->setType('button')
                                    ->setButtonType('submit')
                                    ->setBtnClass('primary')
                                    ->setLayout(0, 12, 0)
                                    ->setAttributes(['mo_boot_col-sm-12' => 'mo_boot_col-sm-12 mo_boot_row mo_boot_justify-content-center'])
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
                                    <?php foreach ($groups as $group): ?>
                                        <?php if (!in_array($group[4], ['Super Users'])): ?>
                                            <option value="<?php echo $group[0]; ?>"><?php echo $group[4]; ?></option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
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
                                    <?php foreach ($groups as $group): ?>
                                        <?php if (!in_array($group[4], ['Super Users'])): ?>
                                            <option value="<?php echo $group[0]; ?>"><?php echo $group[4]; ?></option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
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
                                ->setAttributes(['mo_boot_col-sm-12' => 'mo_boot_col-sm-12 mo_boot_row mo_boot_justify-content-center'])
                                ->setIcon('fa fa-plus')
                                ->setDisabled(true);
                            
                            echo FormRenderer::renderField($addGroupButton);
                        ?>
                    </div>
                </div>

                <!-- User Details Sidebar -->
                <div class="mo_boot_col-sm-5">
                    <div class="mo_ldap_mini_section mo_boot_col-sm-12">
                        <h6 class="mo_boot_mb-3">
                            <i class="fa fa-user-circle mo_boot_me-2"></i>
                            <?php echo Text::_('COM_MINIORANGE_USER_DETAILS'); ?>
                        </h6>
                        
                            <?php if ($UserName == "JOHN DOE"): ?>
                            <div class="alert alert-info mo_boot_mb-3">
                                    <i class="fa fa-info-circle mo_boot_me-2"></i>
                                    <?php echo Text::_("COM_MINIORANGE_LDAP_GET_USER_DETAILS_1") . '<strong><em>' . Text::_("COM_MINIORANGE_LDAP_GET_USER_DETAILS_2") . '</em></strong>'; ?>
                                </div>
                            <?php endif; ?>

                        <div class="mo_boot_mb-3">
                                <strong><?php echo Text::_('COM_MINIORANGE_LDAP_USERNAME'); ?>:</strong>
                                <span class="text-primary"><?php echo htmlspecialchars($UserName); ?></span>
                            </div>

                        <div class="mo_ldap_attributes_container mo_ldap_user_details" style="max-height: 500px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 0.375rem; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);">
                            <table class="table mb-0" style="border-collapse: collapse; width: 100%;">
                                <thead class="table-light" style="position: sticky; top: 0; z-index: 10;">
                                    <tr>
                                        <th style="width: 30%; border: 1px solid #dee2e6; padding: 0.75rem; background-color: #f8f9fa; font-weight: 600;"><?php echo Text::_('COM_MINIORANGE_ATTRIBUTE_NAME'); ?></th>
                                        <th style="width: 70%; border: 1px solid #dee2e6; padding: 0.75rem; background-color: #f8f9fa; font-weight: 600;"><?php echo Text::_('COM_MINIORANGE_ATTRIBUTE_VALUE'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                        foreach ($ActiveDirectoryUserAttributes as $mo_ldap_attribute => $mo_ldap_key):
                                    ?>
                                    <tr style="border-bottom: 1px solid #dee2e6; background-color: #ffffff;">
                                        <td class="mo_ldap_user_details_styles" style="border: 1px solid #dee2e6; padding: 0.75rem; vertical-align: top; background-color: #ffffff;">
                                            <strong><?php echo htmlspecialchars($mo_ldap_attribute); ?></strong>
                                        </td>
                                        <td class="mo_ldap_user_details_styles" style="border: 1px solid #dee2e6; padding: 0.75rem; vertical-align: top; word-wrap: break-word; max-width: 0; background-color: #ffffff;">
                                            <?php
                                            if ($mo_ldap_attribute == 'thumbnailphoto' && !empty($mo_ldap_key)) {
                                                echo '<img src="' . htmlspecialchars($mo_ldap_key) . '" style="max-width: 60px; max-height: 60px; border-radius: 50%;" alt="User thumbnail">';
                                            } elseif ($mo_ldap_attribute == 'memberOf') {
                                                if ($mo_ldap_key != "not available" && is_array($mo_ldap_key)) {
                                                    echo '<strong>Group Memberships (' . count($mo_ldap_key) . '):</strong><br><br>';
                                                    foreach ($mo_ldap_key as $mo_ldap_keyname => $mo_ldap_keyvalue) {
                                                        echo '<strong>' . htmlspecialchars($mo_ldap_keyname) . ':</strong> ';
                                                        echo htmlspecialchars($mo_ldap_keyvalue) . '<br>';
                                                    }
                                                } else {
                                                    echo htmlspecialchars($mo_ldap_key);
                                                }
                                            } else {
                                                if (is_array($mo_ldap_key)) {
                                                    if (count($mo_ldap_key) > 1) {
                                                        echo '<strong>Multiple Values (' . count($mo_ldap_key) . '):</strong><br>';
                                                        foreach ($mo_ldap_key as $index => $val) {
                                                            echo '<strong>Value ' . ($index + 1) . ':</strong> ' . htmlspecialchars(is_array($val) ? implode(', ', $val) : (string)$val) . '<br>';
                                                        }
                                                    } else {
                                                        echo htmlspecialchars(implode(', ', $mo_ldap_key));
                                                    }
                                                } else {
                                                    echo htmlspecialchars($mo_ldap_key);
                                                }
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
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
        <div class="mo_boot_col-sm-12 mo_boot_d-flex mo_boot_justify-content-between mo_boot_align-items-center">
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
                    <i class="fa fa-book mo_boot_me-1"></i>
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
        <div class="mo_boot_col-sm-12">
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
                            ->setAttributes(['mo_boot_col-sm-12' => 'mo_boot_col-sm-12 mo_boot_row mo_boot_justify-content-center'])
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

function moLdapLicensingPlan(){
    $useremail = MoLdapUtility::moLdapFetchData('#__miniorange_ldap_customer',array('id'=>'1'),'loadAssoc');
    if (isset($useremail)) $user_email = $useremail['email'];
    else $user_email = "xyz";
    ?>
    <div class="mo_boot_container-fluid mo_main_ldap_section">
        <div class="mo_boot_row mo_boot_col-sm-12">
            <div class="mo_boot_col-sm-12">
                <div class="mo_boot_col-sm-12 mo_boot_row mo_boot_d-flex mo_boot_justify-content-between mo_boot_align-items-center">
                    <h3 class="mo_ldap_sub_heading"><?php echo Text::_('COM_MINIORANGE_FEATURE_COMPARISON'); ?></h3>
                </div>
            </div>

            <!-- Feature Comparison Section -->
            <div class="mo_boot_col-sm-12">
                <div class="mo_boot_mb-5">
                    <div class="mo_boot_row mo_boot_col-sm-12">
                        <?php
                            echo FormRenderer::renderPlan(
                                'free',
                                Text::_('COM_MINIORANGE_PLAN_FREE'),
                                '$0*',
                                Text::_('COM_MINIORANGE_CURRENT_PLAN'),
                                'button',
                                [Text::_('COM_MINIORANGE_BASIC_LDAP_AUTHENTICATION'), Text::_('COM_MINIORANGE_BASIC_PROFILE_MAPPING'), Text::_('COM_MINIORANGE_EXPORT_CONFIGURATION')],
                                [Text::_('COM_MINIORANGE_AUTO_USER_CREATION'), Text::_('COM_MINIORANGE_NTLM_KERBEROS_AUTHENTICATION'), Text::_('COM_MINIORANGE_CUSTOM_REDIRECT_URL'), Text::_('COM_MINIORANGE_SUPPORT_TLS_CONNECTION'), Text::_('COM_MINIORANGE_IGNORE_LDAPS_CERTIFICATE'), Text::_('COM_MINIORANGE_ADVANCED_MAPPING'), Text::_('COM_MINIORANGE_GROUP_SYNC'), Text::_('COM_MINIORANGE_LDAP_DIRECTORY_PASSWORD_SYNC')]
                            );
                            
                            echo FormRenderer::renderPlan(
                                'basic',
                                Text::_('COM_MINIORANGE_PLAN_BASIC'),
                                '249*',
                                Text::_('COM_MINIORANGE_CONTACT_US'),
                                'link',
                                [Text::_('COM_MINIORANGE_EMAIL_SUPPORT'), Text::_('COM_MINIORANGE_EVERYTHING_IN_FREE'), Text::_('COM_MINIORANGE_AUTO_REGISTER_USERS'), Text::_('COM_MINIORANGE_ADVANCED_MAPPING'), Text::_('COM_MINIORANGE_SUPPORT_TLS_CONNECTION'), Text::_('COM_MINIORANGE_CUSTOM_REDIRECT_URL')],
                                [Text::_('COM_MINIORANGE_NTLM_KERBEROS_AUTHENTICATION'), Text::_('COM_MINIORANGE_IMPORT_USERS_FROM_AD'), Text::_('COM_MINIORANGE_USER_PASSWORD_SYNC_AD'), Text::_('COM_MINIORANGE_MULTIPLE_LDAP_SERVER_SUPPORT'), Text::_('COM_MINIORANGE_MULTIPLE_SEARCH_BASES')],
                                false,
                                MoConstants::LDAP_BASIC_PLAN_URL
                            );
                            
                            echo FormRenderer::renderPlan(
                                'premium',
                                Text::_('COM_MINIORANGE_PLAN_PREMIUM'),
                                '$449*',
                                Text::_('COM_MINIORANGE_CONTACT_US'),
                                'link',
                                [Text::_('COM_MINIORANGE_EVERYTHING_IN_BASIC'), Text::_('COM_MINIORANGE_PRIORITY_SUPPORT'), Text::_('COM_MINIORANGE_MULTIPLE_SEARCH_BASES'), Text::_('COM_MINIORANGE_CUSTOM_INTEGRATION_PAID')],
                                [Text::_('COM_MINIORANGE_DEDICATED_SUPPORT'), Text::_('COM_MINIORANGE_NTLM_KERBEROS_AUTHENTICATION'), Text::_('COM_MINIORANGE_MULTIPLE_LDAP_SERVER_SUPPORT'), Text::_('COM_MINIORANGE_IMPORT_USERS_FROM_AD'), Text::_('COM_MINIORANGE_USER_PASSWORD_SYNC_AD')],
                                true, // highlight this plan
                                MoConstants::LDAP_PREMIUM_PLAN_URL
                            );
                            
                            echo FormRenderer::renderPlan(
                                'enterprise',
                                Text::_('COM_MINIORANGE_PLAN_ENTERPRISE'),
                                '$699*',
                                Text::_('COM_MINIORANGE_CONTACT_US'),
                                'link',
                                [Text::_('COM_MINIORANGE_EVERYTHING_IN_PREMIUM'), Text::_('COM_MINIORANGE_DEDICATED_SUPPORT'), Text::_('COM_MINIORANGE_CUSTOM_DEVELOPMENT'), Text::_('COM_MINIORANGE_NTLM_KERBEROS_AUTHENTICATION'), Text::_('COM_MINIORANGE_MULTIPLE_LDAP_SERVER_SUPPORT'), Text::_('COM_MINIORANGE_USER_PASSWORD_SYNC_AD'), Text::_('COM_MINIORANGE_IMPORT_USERS_FROM_AD')],
                                [],
                                false,
                                MoConstants::LDAP_ENTERPRISE_PLAN_URL
                            );
                        ?>
                    </div>
                </div>
            </div>


            <!-- LDAP Plugin Add-ons Section -->
            <div class="mo_boot_col-sm-12">
                <div class="mo_boot_col-sm-12 mo_boot_row mo_boot_d-flex mo_boot_justify-content-between mo_boot_align-items-center">
                    <h3 class="mo_ldap_sub_heading mo_boot_mb-3"><?php echo Text::_('COM_MINIORANGE_LDAP_PLUGIN_ADDONS'); ?></h3>
                </div>
            </div>
            
            <div class="mo_boot_col-sm-12">
                <div class="mo_boot_mb-5">
                    <div class="mo_boot_row mo_boot_col-sm-12 mo_boot_g-3">
                    <?php
                        echo FormRenderer::renderAddonBlock(
                            Text::_('COM_MINIORANGE_INTEGRATE_COMMUNITY_BUILDER'),
                            Text::_('COM_MINIORANGE_INTEGRATE_COMMUNITY_BUILDER_DESC'),
                            Text::_('COM_MINIORANGE_INTERESTED'),
                            'fa fa-thumbs-up',
                            MoConstants::NTLM_ADDON_DOCS
                        );
                        
                        echo FormRenderer::renderAddonBlock(
                            Text::_('COM_MINIORANGE_USER_PROFILE_SYNC_AD'),
                            Text::_('COM_MINIORANGE_USER_PROFILE_SYNC_AD_DESC'),
                            Text::_('COM_MINIORANGE_INTERESTED'),
                            'fa fa-thumbs-up',
                            MoConstants::PROFILE_SYNC_CONTACT_URL
                        );
                        
                        echo FormRenderer::renderAddonBlock(
                            Text::_('COM_MINIORANGE_IMPORT_USERS_AD'),
                            Text::_('COM_MINIORANGE_IMPORT_USERS_AD_DESC'),
                            Text::_('COM_MINIORANGE_INTERESTED'),
                            'fa fa-thumbs-up',
                            MoConstants::IMPORT_USERS_ADDON_DOCS
                        );
                        
                        echo FormRenderer::renderAddonBlock(
                            Text::_('COM_MINIORANGE_LDAP_PASSWORD_SYNC'),
                            Text::_('COM_MINIORANGE_LDAP_PASSWORD_SYNC_DESC'),
                            Text::_('COM_MINIORANGE_INTERESTED'),
                            'fa fa-thumbs-up',
                            MoConstants::PASSWORD_SYNC_ADDON_DOCS
                        );
                    ?>
                    </div>
                </div>
            </div>


            <!-- Expandable Sections -->
            <div class="mo_boot_col-sm-12">
                <div class="mo_boot_row mo_boot_col-sm-12">
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
                    <div class="mo_ldap_mini_section mo_boot_col-sm-12">
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

function moLoggers(): void
{
    $app = Factory::getApplication();
    $moLdapServerConfig = new MoLdapConstants;
    $enable_Loggers = $moLdapServerConfig->getEnableLoggers();
    $list = MoLdapLogger::getAllLogs();

    // Get filter parameters
    $search_term = $app->input->get('search', '', 'string');
    $level_filter = $app->input->get('level', '', 'string');
    $date_from = $app->input->get('date_from', '', 'string');
    $date_to = $app->input->get('date_to', '', 'string');
    $code_filter = $app->input->get('code', '', 'string');
    $limit = $app->input->get('limit', 10, 'int');
    
    // Apply filters
    $filtered_logs = array();
    foreach ($list as $log) {
        $logData = json_decode($log->message, true);
        $issue = $logData['issue'] ?? '';
        $logCode = $logData['code'] ?? '';
        $logLevel = strtolower($log->log_level);
        $logDate = strtotime($log->timestamp);
        
        // Search term filter
        if (!empty($search_term)) {
            $searchable_text = strtolower($issue . ' ' . $logCode . ' ' . $log->log_level);
            if (strpos($searchable_text, strtolower($search_term)) === false) {
                continue;
            }
        }
        
        // Level filter
        if (!empty($level_filter) && $logLevel !== strtolower($level_filter)) {
            continue;
        }
        
        // Date range filter
        if (!empty($date_from)) {
            $from_timestamp = strtotime($date_from . ' 00:00:00');
            if ($logDate < $from_timestamp) {
                continue;
            }
        }
        
        if (!empty($date_to)) {
            $to_timestamp = strtotime($date_to . ' 23:59:59');
            if ($logDate > $to_timestamp) {
                continue;
            }
        }
        
        // Code filter
        if (!empty($code_filter) && strpos(strtolower($logCode), strtolower($code_filter)) === false) {
            continue;
        }
        
        $filtered_logs[] = $log;
    }

    // Pagination Logic
    $total_logs = count($filtered_logs);
    $total_pages = ceil($total_logs / $limit);
    $current_page = $app->input->get('page', 1, 'int');
    $current_page = max(1, min($total_pages, max(1, $current_page)));
    $start_index = ($current_page - 1) * $limit;

    // Slice the logs for the current page
    $paginated_logs = array_slice($filtered_logs, $start_index, $limit);
    
    // Calculate statistics
    $error_count = 0;
    $warning_count = 0;
    $info_count = 0;
    $notice_count = 0;
    foreach ($filtered_logs as $log) {
        $level = strtolower($log->log_level);
        switch ($level) {
            case 'error':
            case 'err':
                $error_count++;
                break;
            case 'warning':
            case 'warn':
                $warning_count++;
                break;
            case 'info':
                $info_count++;
                break;
            case 'notice':
                $notice_count++;
                break;
        }
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
                        <div class="mo_boot_col-sm-2">
                            <form method="post"
                                    action="index.php?option=com_miniorange_dirsync&task=accountsetup.toggleLogger"
                                    id="logger-toggle-form">
                                <div class="form-check form-switch">
                                    <input type="checkbox"
                                            class="form-check-input mo_boot_ms-5"
                                            id="mo_ldap_logger_toggle"
                                            name="mo_ldap_logger_toggle"
                                            value="1"
                                        <?php if ($enable_Loggers == 1) echo 'checked'; ?>
                                            onchange="this.form.submit()">
                                    <label class="form-check-label fw-bold small"
                                            for="mo_ldap_logger_toggle">
                                        <?php echo $enable_Loggers ? 'Enabled' : 'Disabled'; ?>
                                    </label>
                                </div>
                                <?php echo HTMLHelper::_('form.token'); ?>
                            </form>
                        </div>
                    </div>
    
                    <?php if (!$enable_Loggers): ?>
                    <!-- Logger Disabled Warning -->
                    <div class="mo_boot_col-sm-12 mo_boot_row">
                        <div class="mo_ldap_mini_section mo_boot_col-sm-12 mo_boot_row">
                            <i class="fa fa-exclamation-triangle text-warning"></i>
                            <div>
                                <h6><?php echo Text::_('COM_MINIORANGE_LOGGER_DISABLED_TITLE'); ?></h6>
                                <p><?php echo Text::_('COM_MINIORANGE_LOGGER_DISABLED_MESSAGE'); ?></p>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                <!-- Logs Management Section -->
                <div class="mo_boot_col-sm-12">
                    <div>
                        <!-- Filter Controls -->
                        <div class="mo_boot_col-sm-12 <?php echo (count($list) == 0) ? 'mo_ldap_disabled_input' : ''; ?>">
                            <form method="get" action=""
                                  id="logger-filter-form" <?php echo (count($list) == 0) ? 'style="pointer-events: none; opacity: 0.6;"' : ''; ?>>
                                <input type="hidden" name="option" value="com_miniorange_dirsync">
                                <input type="hidden" name="view" value="accountsetup">
                                <input type="hidden" name="tab-panel" value="moLoggers">
            
                                <div class="mo_boot_row mo_boot_g-2">
                                    <!-- Search Log -->
                                    <div class="mo_boot_col-md-4">
                                        <label for="search" class="form-label small fw-bold">
                                            <i class="fa fa-search me-1"></i>
                                            <?php echo Text::_('COM_MINIORANGE_LOGGER_SEARCH'); ?>
                                        </label>
                                        <input type="text"
                                               class="form-control form-control-sm"
                                               id="search"
                                               name="search"
                                               value="<?php echo htmlspecialchars($search_term); ?>"
                                               placeholder="<?php echo Text::_('COM_MINIORANGE_LOGGER_SEARCH_PLACEHOLDER'); ?>">
                                    </div>
            
                                    <!-- Filter by Level -->
                                    <div class="mo_boot_col-md-2">
                                        <label for="level"
                                               class="form-label small fw-bold">
                                            <i class="fas fa-layer-group me-1"></i>
                                            <?php echo Text::_('COM_MINIORANGE_LOGGER_LEVEL_FILTER'); ?>
                                        </label>
                                        <select class="form-select form-select-sm"
                                                id="level" name="level">
                                            <option value=""><?php echo Text::_('COM_MINIORANGE_LOGGER_LEVEL_ALL'); ?></option>
                                            <option value="info" <?php echo ($level_filter === 'info') ? 'selected' : ''; ?>><?php echo Text::_('COM_MINIORANGE_LOGGER_LEVEL_INFO'); ?></option>
                                            <option value="warning" <?php echo ($level_filter === 'warning') ? 'selected' : ''; ?>><?php echo Text::_('COM_MINIORANGE_LOGGER_LEVEL_WARNING'); ?></option>
                                            <option value="error" <?php echo ($level_filter === 'error') ? 'selected' : ''; ?>><?php echo Text::_('COM_MINIORANGE_LOGGER_LEVEL_ERROR'); ?></option>
                                            <option value="error" <?php echo ($level_filter === 'notice') ? 'selected' : ''; ?>><?php echo Text::_('COM_MINIORANGE_LOGGER_LEVEL_NOTICE'); ?></option>
                                        </select>
                                    </div>
            
                                    <!-- Date Range -->
                                    <div class="mo_boot_col-md-2">
                                        <label for="date_from"
                                               class="form-label small fw-bold">
                                            <i class="fa fa-calendar me-1"></i>
                                            <?php echo Text::_('COM_MINIORANGE_LOGGER_DATE_FROM'); ?>
                                        </label>
                                        <input type="date"
                                               class="form-control form-control-sm"
                                               id="date_from"
                                               name="date_from"
                                               value="<?php echo htmlspecialchars($date_from); ?>">
                                    </div>
            
                                    <div class="mo_boot_col-md-2">
                                        <label for="date_to"
                                               class="form-label small fw-bold">
                                            <i class="fa fa-calendar me-1"></i>
                                            <?php echo Text::_('COM_MINIORANGE_LOGGER_DATE_TO'); ?>
                                        </label>
                                        <input type="date"
                                               class="form-control form-control-sm"
                                               id="date_to"
                                               name="date_to"
                                               value="<?php echo htmlspecialchars($date_to); ?>">
                                    </div>
            
                                    <!-- Items per page -->
                                    <div class="mo_boot_col-md-2">
                                        <label for="limit" class="form-label small fw-bold">
                                            <i class="fa fa-list-ol me-1"></i>
                                            <?php echo Text::_('COM_MINIORANGE_LOGGER_LIMIT'); ?>
                                        </label>
                                        <select class="form-select form-select-sm" id="limit" name="limit">
                                            <option value="10" <?php echo ($limit == 10) ? 'selected' : ''; ?>>10</option>
                                            <option value="25" <?php echo ($limit == 25) ? 'selected' : ''; ?>>25</option>
                                            <option value="50" <?php echo ($limit == 50) ? 'selected' : ''; ?>>50</option>
                                            <option value="100" <?php echo ($limit == 100) ? 'selected' : ''; ?>>100</option>
                                        </select>
                                        <br>
                                        <div class="mo_boot_row">
                                            <div class="mo_boot_col-sm-12">
                                                <div class="mo_boot_d-flex mo_boot_align-items-center mo_boot_justify-content-center mo_boot_gap-2">
                                                    <button type="submit" class="mo_boot_btn mo_boot_btn-outline-primary">
                                                        <i class="fas fa-filter mo_boot_me-2"></i>
                                                        <?php echo Text::_('COM_MINIORANGE_LOGGER_APPLY_FILTERS'); ?>
                                                    </button>
                                                    <a href="index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=moLoggers"
                                                       class="mo_boot_btn mo_boot_btn-outline-primary btn-clear-filters">
                                                        <i class="fas fa-times mo_boot_me-2"></i>
                                                        <?php echo Text::_('COM_MINIORANGE_LOGGER_CLEAR_FILTERS'); ?>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Logs Table -->
            <?php if (empty($paginated_logs)): ?>
                <div class="mo_boot_col-sm-12 mo_boot_row mo_boot_d-flex mo_boot_justify-content-center mo_boot_align-items-center mo_boot_mt-4 mo_boot_mb-5">
                    <?php if (!empty($search_term) || !empty($level_filter) || !empty($date_from) || !empty($date_to) || !empty($code_filter)): ?>
                        <?php echo Text::_('COM_MINIORANGE_LOGGER_NO_FILTERED_LOGS'); ?>
                        <div class="mo_boot_col-sm-12 mo_boot_row mo_boot_d-flex mo_boot_justify-content-center mo_boot_align-items-center mo_boot_mt-4">
                        <a href="index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=moLoggers"
                           class="mo_boot_btn mo_boot_btn-primary">
                            <i class="fa fa-times me-1"></i>
                            <?php echo Text::_('COM_MINIORANGE_LOGGER_CLEAR_FILTERS'); ?>
                        </a>
                        </div>
                    <?php else: ?>
                    <?php echo Text::_('COM_MINIORANGE_LOGGER_NO_LOGS'); ?>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="mo_ldap_mini_section mo_boot_col-sm-12 mo_boot_row">
                    <table class="mo_ldap_logs_table mo_boot_col-sm-12" id="logsList">
                        <caption class="visually-hidden">
                            <?php echo Text::_('COM_MINIORANGE_LOGGER_TABLE_CAPTION'); ?>
                        </caption>
                        <thead>
                        <tr>
                            <th scope="col" style="width: 20%;">
                                <?php echo Text::_('COM_MINIORANGE_LOGGER_DATE'); ?>
                            </th>
                            <th scope="col" style="width: 20%;">
                                <?php echo Text::_('COM_MINIORANGE_LOGGER_LEVEL'); ?>
                            </th>
                            <th scope="col" style="width: 15%;">
                                <?php echo Text::_('COM_MINIORANGE_LOGGER_CODE'); ?>
                            </th>
                            <th scope="col" style="width: 45%;">
                                <?php echo Text::_('COM_MINIORANGE_LOGGER_MESSAGE'); ?>
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($paginated_logs as $i => $log): ?>
                            <?php
                            $logData = json_decode($log->message, true);
                            $issue = $logData['issue'] ?? '-';
                            $logCode = $logData['code'] ?? '-';
                            $logLevel = strtolower(htmlspecialchars($log->log_level));
                            ?>
                            <tr>
                                <td>
                                    <?php echo HTMLHelper::_('date', $log->timestamp, 'd M Y H:i'); ?>
                                </td>
                                <td>
                                <span>
                                        <?php echo strtoupper(htmlspecialchars($log->log_level)); ?>
                                    </span>
                                </td>
                                <td>
                                    <code class="small"><?php echo htmlspecialchars($logCode); ?></code>
                                </td>
                                <td class="text-wrap">
                                    <?php echo htmlspecialchars($issue); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
        
        
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="mo_boot_d-flex justify-content-center mo_boot_col-sm-12">
                        <nav aria-label="Logs pagination">
                            <ul class="pagination pagination-sm">
                                <?php
                                    // Build base URL with current filters
                                    $base_url = 'index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=moLoggers';
                                    if (!empty($search_term)) $base_url .= '&search=' . urlencode($search_term);
                                    if (!empty($level_filter)) $base_url .= '&level=' . urlencode($level_filter);
                                    if (!empty($date_from)) $base_url .= '&date_from=' . urlencode($date_from);
                                    if (!empty($date_to)) $base_url .= '&date_to=' . urlencode($date_to);
                                    if (!empty($code_filter)) $base_url .= '&code=' . urlencode($code_filter);
                                    if ($limit != 25) $base_url .= '&limit=' . $limit;
                                ?>
                                
                                <?php if ($current_page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link"
                                           href="<?php echo Route::_($base_url . '&page=' . ($current_page - 1)); ?>">
                                            <i class="fa fa-chevron-left"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($page = 1; $page <= $total_pages; $page++): ?>
                                    <li class="page-item <?php echo ($page == $current_page) ? 'active' : ''; ?>">
                                        <a class="page-link" href="<?php echo Route::_($base_url . '&page=' . $page); ?>">
                                            <?php echo $page; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($current_page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link"
                                           href="<?php echo Route::_($base_url . '&page=' . ($current_page + 1)); ?>">
                                            <i class="fa fa-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            <!--   Insights-->
            <div class="mo_boot_row mo_boot_col-sm-12 mo_boot_justify-content-between mo_boot_align-items-center mo_boot_mt-4">
                <div class="mo_boot_col-sm-6">
                    <a href="index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=moLoggers"
                       class="mo_boot_btn mo_boot_btn-success mo_boot_text-decoration-none mo_boot_ms-2"
                       title="<?php echo Text::_('COM_MINIORANGE_FETCH_LATEST_LOGS'); ?>">
                        <i class="fa fa-refresh mo_boot_me-1"></i>
                        <?php echo Text::_('COM_MINIORANGE_FETCH_LATEST'); ?>
                    </a>
                    <a href="index.php?option=com_miniorange_dirsync&view=accountsetup&task=accountsetup.downloadLogs"
                       class="mo_boot_btn mo_boot_btn-primary mo_boot_text-decoration-none"
                        <?php echo (count($list) == 0) ? 'style="pointer-events: none; opacity: 0.6;" title="No logs to download"' : ''; ?>>
                        <i class="fa fa-download mo_boot_me-1"></i>
                        Download
                    </a>
                    <form method="post"
                          action="index.php?option=com_miniorange_dirsync&view=accountsetup&task=accountsetup.resetLogs"
                          style="display: inline;">
                        <button type="submit"
                                class="mo_boot_btn mo_boot_btn-primary mo_boot_btn-sm"
                            <?php echo (count($list) == 0) ? 'disabled title="No logs to reset"' : 'onclick="return confirm(\'' . Text::_('COM_MINIORANGE_LOGGER_RESET_CONFIRMATION') . '\');"'; ?>>
                            <i class="fas fa-xmark mo_boot_me-1"></i>
                            Reset
                        </button>
                        <?php echo HTMLHelper::_('form.token'); ?>
                    </form>
                </div>
                <div class="mo_boot_col-sm-6 mo_boot_d-flex mo_boot_justify-content-end mo_boot_align-items-center mo_boot_gap-3">
                    <span style="font-weight: bold;">Insights:</span>
                    <span>Info: <?php echo $info_count; ?> total</span>
                    <span>Notice: <?php echo $notice_count; ?> total</span>
                    <span>Error: <?php echo $error_count; ?> total</span>
                    <span>Warning: <?php echo $warning_count; ?> total</span>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

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
                    ->setOptions(['Select' => 'Select', 'All Users' => 'All Users', 'Specific OU' => 'Specific OU', 'Specific Group' => 'Specific Group'])
                    ->setPlaceholder('COM_MINIORANGE_SELECT_IMPORT_SCOPE')
                    ->setLayout(7, 2, 3);
                
                echo FormRenderer::renderField($importScopeConfig);
                
                // Import Frequency dropdown
                $importFrequencyConfig = (new FormFieldConfig('importFrequency', Text::_('COM_MINIORANGE_IMPORT_FREQUENCY')))
                    ->setType('dropdown')
                    ->setOptions(['Select' => 'Select', 'Daily' => 'Daily', 'Weekly' => 'Weekly', 'Monthly' => 'Monthly'])
                    ->setPlaceholder('COM_MINIORANGE_SELECT_FREQUENCY')
                    ->setLayout(7, 2, 3);
                
                echo FormRenderer::renderField($importFrequencyConfig);
                
                // Start Import Button
                $startImportButton = (new FormFieldConfig('save_import', Text::_('COM_MINIORANGE_START_IMPORT')))
                    ->setType('button')
                    ->setButtonType('submit')
                    ->setBtnClass('primary')
                    ->setLayout(3, 7, 0)
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
                <a href="<?php echo MoConstants::IMPORT_EXPORT_DOCS; ?>" 
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
                    ->setValue(isset($mo_redirect_url) ? htmlspecialchars($mo_redirect_url) : '')
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
                    ->setLayout(3, 7, 0)
                    ->setIcon('fa fa-check')
                    ->setDisabled(true);
                
                echo FormRenderer::renderField($saveSyncButton);
            ?>
            
        </div>

    </div>
    <?php
}


function moLdapSupportTab(){
    $app  = Factory::getApplication();
    $current_user = $app->getIdentity();
    $customer_details = MoLdapUtility::moLdapFetchData('#__miniorange_ldap_customer',array('id'=>'1'),'loadAssoc')	;

    $admin_email = isset($customer_details['email']) ? htmlspecialchars($customer_details['email'], ENT_QUOTES, 'UTF-8') : '';
    if ($admin_email == '') $admin_email = htmlspecialchars($current_user->email, ENT_QUOTES, 'UTF-8');
    $admin_phone = isset($customer_details['admin_phone']) ? htmlspecialchars($customer_details['admin_phone'], ENT_QUOTES, 'UTF-8') : '';
    
    // Check both GET and POST parameters for query_type
    $get_params = $app->input->get->getArray();
    
    $post_params = $app->input->post->getArray();

    
    $type_of_query = '';
    if (isset($get_params['query_type']) && !empty($get_params['query_type'])) {
        $type_of_query = htmlspecialchars(trim($get_params['query_type']), ENT_QUOTES, 'UTF-8');
    } elseif (isset($post_params['query_type']) && !empty($post_params['query_type'])) {
        $type_of_query = htmlspecialchars(trim($post_params['query_type']), ENT_QUOTES, 'UTF-8');
    }
    
    if ($type_of_query == 'trial') {
        $header_text = Text::_('COM_MINIORANGE_FREE_TRIAL_REQUEST');
        $description_text = Text::_('COM_MINIORANGE_FREE_TRIAL_DESCRIPTION');
    } elseif ($type_of_query == 'configuration') {
        $header_text = Text::_('COM_MINIORANGE_SUPPORT_REQUEST');
        $description_text = Text::_('COM_MINIORANGE_SUPPORT_DESCRIPTION');
    } else {
        $header_text = Text::_('COM_MINIORANGE_SUPPORT_FEATURES');
        $description_text = Text::_('COM_MINIORANGE_SUPPORT_GENERAL_DESCRIPTION');
    }

    ?>
    <div class="mo_boot_container-fluid mo_main_ldap_section">
        <div class="mo_boot_row ">
            <!-- Header Section -->
            <div class="mo_boot_col-sm-12">
                <div class="mo_boot_row">
                    <div class="mo_boot_col-sm-12 mo_boot_row mo_boot_py-3">
                        <div class="mo_boot_col-sm-12 mo_boot_row mo_boot_d-flex mo_boot_justify-content-between mo_boot_align-items-center">
                            <h3 class="mo_ldap_sub_heading mo_boot_mb-3">
                                <?php echo $header_text; ?>
                            </h3>
                        </div>
                    </div>
                    <!-- Description Alert -->
                    <div class="mo_boot_row mo_boot_mx-4">
                        <div class="mo_boot_col-sm-12">
                            <?php if ($type_of_query == 'trial' || $type_of_query == 'configuration'): ?>
                                <div>
                                    <div class="mo_boot_d-flex mo_boot_align-items-center">
                                        <div>
                                            <p class="mo_boot_mt-1"><?php echo $description_text; ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <p class="mb-0"><?php echo $description_text; ?></p>
                            <?php endif; ?>
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
                                    
                                    <!-- Email Field -->
                                    <div class="mb-4">
                                        <label for="mo_ldap_query_email" class="form-label fw-bold">
                                            <?php echo Text::_('COM_MINIORANGE_EMAIL');?> <span class="text-danger">*</span>
                                        </label>
                                        <input type="email" 
                                               class="form-control" 
                                               id="mo_ldap_query_email" 
                                               name="mo_ldap_query_email" 
                                               value="<?php echo $admin_email; ?>" 
                                               placeholder="<?php echo Text::_('COM_MINIORANGE_SUPPORT_EMAIL');?>" 
                                               minlength="5"
                                               maxlength="100"
                                               required />
                                    </div>

                                    <!-- Phone Field -->
                                    <div class="mb-4">
                                        <label for="mo_ldap_query_phone" class="form-label fw-bold">
                                            <?php echo Text::_('COM_MINIORANGE_PHONE_NUMBER');?>
                                        </label>
                                        <div class="input-group">
                                            <select class="form-select" style="max-width: 120px;">
                                                <option value="+1" selected>🇺🇸 +1</option>
                                                <option value="+44">🇬🇧 +44</option>
                                                <option value="+91">🇮🇳 +91</option>
                                                <option value="+81">🇯🇵 +81</option>
                                                <option value="+49">🇩🇪 +49</option>
                                                <option value="+33">🇫🇷 +33</option>
                                            </select>
                                            <input type="tel"
                                                   class="form-control" 
                                                   name="mo_ldap_query_phone" 
                                                   id="mo_ldap_query_phone" 
                                                   value="<?php echo $admin_phone; ?>" 
                                                   placeholder="<?php echo Text::_('COM_MINIORANGE_SUPPORT_PHONE');?>"/>
                                        </div>
                                    </div>

                                    <!-- Issue Type Field -->
                                    <div class="mo_boot_mb-4">
                                        <label for="mo_ldap_setup_call_issue" class="form-label fw-bold">
                                            <?php echo Text::_('COM_MINIORANGE_ISSUE');?> <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select" name="mo_ldap_setup_call_issue" id="mo_ldap_setup_call_issue" required>
                                            <option value="" disabled><?php echo Text::_('COM_MINIORANGE_SELECT_ISSUE_TYPE');?></option>
                                            <option id="mo_ldap_sso_setup_issue" <?php if($type_of_query == 'setup_issue') echo 'selected';?>><?php echo Text::_('COM_MINIORANGE_SSO_SETUP_ISSUE');?></option>
                                            <option id="mo_ldap_configuration_issues" <?php if($type_of_query == 'configuration') echo 'selected';?>><?php echo Text::_('COM_MINIORANGE_CONFIGURATION_ISSUES');?></option>
                                            <option id="mo_ldap_use_case_discussion" <?php if($type_of_query == 'user_case') echo 'selected';?>><?php echo Text::_('COM_MINIORANGE_USECASE_DISCUSSION');?></option>
                                            <option id="mo_ldap_trial_request" <?php if($type_of_query == 'trial') echo 'selected';?>><?php echo Text::_('COM_MINIORANGE_TRIAL_REQUEST');?></option>
                                            <option id="mo_ldap_contact_us" <?php if($type_of_query == 'contact_us') echo 'selected';?>><?php echo Text::_('COM_MINIORANGE_CONTACT_US');?></option>
                                            <option id="mo_ldap_get_quote" <?php if($type_of_query == 'get_quote') echo 'selected';?>><?php echo Text::_('COM_MINIORANGE_GET_QUOTE');?></option>
                                            <option id="mo_ldap_other_issue" <?php if($type_of_query == 'other') echo 'selected';?>><?php echo Text::_('COM_MINIORANGE_OTHER');?></option>
                                        </select>
                                    </div>

                                    <!-- Query Field -->
                                    <div class="mb-4">
                                        <label for="mo_ldap_query" class="form-label fw-bold">
                                            <?php echo Text::_('COM_MINIORANGE_QUERY');?> <span class="text-danger">*</span>
                                        </label>
                                        <?php 
                                        $default_query = '';
                                        if ($type_of_query == 'trial') {
                                            $default_query = Text::_('COM_MINIORANGE_FREE_TRIAL_DEFAULT_QUERY');
                                        } elseif ($type_of_query == 'configuration') {
                                            $default_query = Text::_('COM_MINIORANGE_SUPPORT_DEFAULT_QUERY');
                                        }
                                        ?>
                                        <textarea id="mo_ldap_query" 
                                                  class="form-control mo_boot_form-control" 
                                                  name="mo_ldap_query" 
                                                  placeholder="<?php echo Text::_('COM_MINIORANGE_SUPPORT_QUERY');?>" 
                                                  rows="3"
                                                  minlength="10"
                                                  maxlength="2000"
                                                  style="resize: vertical; height: auto; min-height: 6rem;"
                                                  required><?php echo htmlspecialchars($default_query); ?></textarea>
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