<?php
defined('_JEXEC') or die;

    /**
    *
    * @package     Joomla.Component
    * @subpackage  com_miniorange_dirsync
    *
    * @author      miniOrange Security Software Pvt. Ltd.
    * @copyright   Copyright (C) 2015 miniOrange (https://www.miniorange.com)
    * @license     GNU General Public License version 3; see LICENSE.txt
    * @contact     info@xecurify.com
    *
    *This class use for constants
    *
    **/
    
    use Joomla\CMS\Document\HtmlDocument;
    use Joomla\CMS\Uri\Uri;
    
    /**
     * Constants Helper Class
     * Centralizes all constant values used throughout the component
     */
    class MoConstants
    {
        // ========================================
        // COMPONENT PATHS
        // ========================================
        
        /**
         * Component base path
         */
        const COMPONENT_PATH = 'components/com_miniorange_dirsync';
        
        /**
         * Assets directory path
         */
        const ASSETS_PATH = self::COMPONENT_PATH . '/assets';
        
        /**
         * Images directory path
         */
        const IMAGES_PATH = self::ASSETS_PATH . '/images';
        
        /**
         * CSS directory path
         */
        const CSS_PATH = self::ASSETS_PATH . '/css';
        
        /**
         * JavaScript directory path
         */
        const JS_PATH = self::ASSETS_PATH . '/js';
        
        /**
         * JSON directory path
         */
        const JSON_PATH = self::ASSETS_PATH . '/json';
        
        const HELPER_PATH = JPATH_ADMINISTRATOR . '/components/com_miniorange_dirsync/helpers/';
        /**
         * Crown image for premium features
         */
        const CROWN_IMAGE = self::IMAGES_PATH . '/crown.webp';
        
        /**
         * Import users icon
         */
        const IMPORT_USERS_ICON = self::IMAGES_PATH . '/import-users.svg';
        
        
        // ========================================
        // ASSET FILES
        // ========================================
        /**
         * Main CSS file
         */
        const MAIN_CSS = self::CSS_PATH . '/miniorange_boot.css';
        /**
         * LDAP specific CSS
         */
        const LDAP_CSS = self::CSS_PATH . '/MoLdapStyle.css';
        /**
         * License CSS for Joomla 3
         */
        const LICENSE_CSS_3 = self::CSS_PATH . '/miniorange_license3.css';
        /**
         * License CSS for Joomla 4+
         */
        const LICENSE_CSS_4 = self::CSS_PATH . '/miniorange_license.css';
        /**
         * Main JavaScript file
         */
        const MAIN_JS = self::JS_PATH . '/utilityjs.js';
        /**
         * jQuery file
         */
        const JQUERY_JS = self::JS_PATH . '/jquery.1.11.0.min.js';
        /**
         * Bootstrap JavaScript
         */
        const BOOTSTRAP_JS = self::JS_PATH . '/bootstrap.min.js';
        /**
         * Tabs configuration JSON
         */
        const TABS_JSON = self::JSON_PATH . '/tabs.json';
        /**
         * miniOrange Documentation URLs
         */
        const MINIORANGE_DOCS_BASE = 'https://plugins.miniorange.com';
        
        // ========================================
        // EXTERNAL URLs & DOCUMENTATION
        // ========================================
        /**
         * Joomla LDAP Configuration Guide
         */
        const LDAP_CONFIGURATION_GUIDE = self::MINIORANGE_DOCS_BASE . '/joomla-ldap-configuration';
        /**
         * Joomla LDAP Video Setup Guide
         */
        const LDAP_VIDEO_SETUP_GUIDE = 'https://www.youtube.com/watch?v=OjpZMRleRn0';
        /**
         * NTLM SSO Setup Guide
         */
        const NTLM_SSO_SETUP_GUIDE = self::MINIORANGE_DOCS_BASE . '/joomla-ntlm-sso-for-apache-on-windows';
        /**
         * NTLM Authentication Documentation
         */
        const NTLM_AUTH_DOCS = 'https://developers.miniorange.com/docs/joomla_ldap/ntlm-authentication';
        /**
         * Import/Export Users Documentation
         */
        const IMPORT_EXPORT_DOCS = 'https://developers.miniorange.com/docs/joomla_ldap/import-export-users';
        
        // ========================================
        // IMPORT/EXPORT URLs
        // ========================================
        /**
         * LDAP Configuration Export URL
         */
        const LDAP_EXPORT_URL = 'index.php?option=com_miniorange_dirsync&task=accountsetup.exportConfiguration';
        
        /**
         * LDAP Configuration Import URL
         */
        const LDAP_IMPORT_URL = 'index.php?option=com_miniorange_dirsync&task=importConfiguration';
        
        // ========================================
        // ADDON DOCUMENTATION URLs
        // ========================================
        /**
         * NTLM/Kerberos Authentication Documentation
         */
        const NTLM_ADDON_DOCS = 'https://developers.miniorange.com/docs/joomla_ldap/ntlm-authentication';
        
        /**
         * Import Users from AD Documentation
         */
        const IMPORT_USERS_ADDON_DOCS = 'https://developers.miniorange.com/docs/joomla_ldap/import-export-users';
        
        /**
         * LDAP Password Sync Plan Documentation
         */
        const PASSWORD_SYNC_ADDON_DOCS = 'https://plugins.miniorange.com/ldap-password-sync-for-joomla';
        
        /**
         * User Profile Sync Contact Page
         */
        const PROFILE_SYNC_CONTACT_URL = 'https://www.miniorange.com/contact';
        
        // ========================================
        // PAYMENT PORTAL URLs
        // ========================================
        /**
         * miniOrange Payment Portal Base URL
         */
        const PAYMENT_PORTAL_BASE = 'https://portal.miniorange.com/initializePayment?requestOrigin=';
        
        /**
         * LDAP Plan Payment URLs
         */
        const LDAP_BASIC_PLAN_URL = self::PAYMENT_PORTAL_BASE . 'joomla_ldap_basic_plan';
        const LDAP_PREMIUM_PLAN_URL = self::PAYMENT_PORTAL_BASE . 'joomla_ldap_premium_plan';
        const LDAP_ENTERPRISE_PLAN_URL = self::PAYMENT_PORTAL_BASE . 'joomla_ldap_enterprise_plan';
        /**
         * Font Awesome CDN
         */
        const FONT_AWESOME_CDN = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css';
        /**
         * jQuery CDN
         */
        const JQUERY_CDN = '//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js';
        /**
         * Bootstrap CDN
         */
        const BOOTSTRAP_CDN = 'https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/js/bootstrap.min.js';
        /**
         * Common LDAP attributes
         */
        const LDAP_ATTRIBUTES = ['samaccountname' => 'COM_MINIORANGE_SAMACCOUNTNAME', 'cn' => 'COM_MINIORANGE_CN', 'sn' => 'COM_MINIORANGE_SN', 'uid' => 'COM_MINIORANGE_UID', 'userprincipalname' => 'COM_MINIORANGE_USERPRINCIPALNAME', 'mail' => 'COM_MINIORANGE_MAIL', 'gidnumber' => 'COM_MINIORANGE_GIDNUMBER', 'uidnumber' => 'COM_MINIORANGE_UIDNUMBER'];
        
        // ========================================
        // LDAP ATTRIBUTES
        // ========================================
        /**
         * LDAP server types
         */
        const LDAP_SERVER_TYPES = ['msad' => 'COM_MINIORANGE_MICROSOFT_AD', 'openldap' => 'COM_MINIORANGE_OPENLDAP', 'freeipa' => 'COM_MINIORANGE_FREEIPA', 'jumpcloud' => 'COM_MINIORANGE_JUMPCLOUD', 'other' => 'COM_MINIORANGE_OTHER'];
        /**
         * LDAP connection types
         */
        const LDAP_CONNECTION_TYPES = ['ldap' => 'COM_MINIORANGE_LDAP', 'ldaps' => 'COM_MINIORANGE_LDAPS'];
        /**
         * Default LDAP port
         */
        const DEFAULT_LDAP_PORT = '389';
        
        const LDAP_SERVER_ATTRIBUTES = [
            'samaccountname' => 'samaccountname',
            'cn' => 'cn',
            'sn' => 'sn',
            'uid' => 'uid',
            'userprincipalname' => 'userprincipalname',
            'mail' => 'mail',
            'gidnumber' => 'gidnumber',
            'uidnumber' => 'uidnumber'
        ];
        
        /**
         * Base Account Setup URL
         */
        const ACCOUNT_SETUP_BASE_URL = 'index.php?option=com_miniorange_dirsync&view=accountsetup&task=accountsetup';
        
        // ========================================
        // SPECIFIC TASK URLs
        // ========================================
        /**
         * LDAP Configuration URLs
         */
        const LDAP_SAVE_CONFIG_URL = self::ACCOUNT_SETUP_BASE_URL . '.moLdapSaveConfig';
        const LDAP_SAVE_USER_MAPPING_URL = self::ACCOUNT_SETUP_BASE_URL . '.moLdapSaveUserMappingConfig';
        const LDAP_TEST_CONFIG_URL = self::ACCOUNT_SETUP_BASE_URL . '.testConfigurations';
        const LDAP_RESET_SETTINGS_URL = self::ACCOUNT_SETUP_BASE_URL . '.resetLdapSettings';
        
        /**
         * Login Settings URLs
         */
        const LOGIN_SAVE_URL = self::ACCOUNT_SETUP_BASE_URL . '.moLdapSavelogin';
        
        /**
         * Attribute Mapping URLs
         */
        const ATTRIBUTE_MAPPING_URL = self::ACCOUNT_SETUP_BASE_URL . '.moLdapAttributeMapping';
        const ROLE_MAPPING_SAVE_URL = self::ACCOUNT_SETUP_BASE_URL . '.moLdapSaveRolemapping';
        
        /**
         * Import/Export URLs
         */
        const EXPORT_CONFIG_URL = 'index.php?option=com_miniorange_dirsync&task=accountsetup.exportConfiguration';
        const IMPORT_CONFIG_URL = 'index.php?option=com_miniorange_dirsync&task=accountsetup.importConfiguration';
        
        /**
         * Logger URLs
         */
        const LOGGER_TOGGLE_URL = 'index.php?option=com_miniorange_dirsync&task=accountsetup.toggleLogger';
        const LOGGER_DOWNLOAD_URL = 'index.php?option=com_miniorange_dirsync&task=accountsetup.downloadLogs';
        const LOGGER_RESET_URL = 'index.php?option=com_miniorange_dirsync&task=accountsetup.resetLogs';
        
        /**
         * Support URLs
         */
        const SUPPORT_CONTACT_URL = self::ACCOUNT_SETUP_BASE_URL . '.moLdapContactUs';
        
        // ========================================
        // DEFAULT VALUES
        // ========================================
        /**
         * Default LDAPS port
         */
        const DEFAULT_LDAPS_PORT = '636';
        /**
         * Default search filter
         */
        const DEFAULT_SEARCH_FILTER = '(&(objectClass=*)(cn=?))';
        
        public static function includeHelpers()
        {
            require_once self::HELPER_PATH . 'MoLdapLogger.php';
            require_once self::HELPER_PATH . 'FormRenderer.php';
        }
        
        // ========================================
        // HELPER METHODS
        // ========================================
        
        /**
         * Get full URL for a component asset
         *
         * @param string $path Relative path from component root
         * @return string Full URL
         */
        public static function getAssetUrl($path)
        {
            return Uri::base() . self::COMPONENT_PATH . '/' . $path;
        }
        
        /**
         * Get full URL for an image
         *
         * @param string $imageName Image filename
         * @return string Full URL
         */
        public static function getImageUrl($imageName)
        {
            return Uri::base() . self::IMAGES_PATH . '/' . $imageName;
        }
        
        /**
         * Get LDAP attributes as array of keys
         *
         * @return array Array of LDAP attribute keys
         */
        public static function getLdapAttributeKeys()
        {
            return array_keys(self::LDAP_ATTRIBUTES);
        }
        
        /**
         * Get LDAP server type options for dropdown
         *
         * @return array Array of server type options
         */
        public static function getLdapServerTypeOptions()
        {
            return array_keys(self::LDAP_SERVER_TYPES);
        }
        
        /**
         * Get LDAP connection type options for dropdown
         *
         * @return array Array of connection type options
         */
        public static function getLdapConnectionTypeOptions()
        {
            return array_keys(self::LDAP_CONNECTION_TYPES);
        }
        
        /**
         * Loads all required JavaScript and CSS assets for the component.
         *
         * This method automatically includes:
         * - Core JavaScript dependencies (jQuery, Bootstrap, etc.)
         * - Component-specific JavaScript files
         * - Font Awesome CDN
         * - Component-specific CSS files
         *
         * @param HtmlDocument $document The Joomla document object used to attach scripts and styles.
         *
         * @return void
         */
        public static function loadAssets($document): void
        {
            // JS Files
            $jsFiles = ['utilityjs.js','support-form-validation.js', 'import-export.js', 'expandable-sections.js'];
            
            foreach ($jsFiles as $js) {
                $document->addScript(self::getJsUrl($js));
            }
            
            // CSS Files
            $cssFiles = ['miniorange_boot.css', 'miniorange_license.css'];
            
            // External CDN
            $document->addStyleSheet(self::FONT_AWESOME_CDN);
            
            foreach ($cssFiles as $css) {
                $document->addStyleSheet(self::getCssUrl($css));
            }
        }
        
        /**
         * Get full URL for a JavaScript file
         *
         * @param string $jsName JavaScript filename
         * @return string Full URL
         */
        public static function getJsUrl($jsName)
        {
            return Uri::base() . self::JS_PATH . '/' . $jsName;
        }
        
        // ========================================
        // Adding Assets
        // ========================================

        /**
         * Get full URL for a CSS file
         *
         * @param string $cssName CSS filename
         * @return string Full URL
         */
        public static function getCssUrl($cssName)
        {
            return Uri::base() . self::CSS_PATH . '/' . $cssName;
        }
        
    }