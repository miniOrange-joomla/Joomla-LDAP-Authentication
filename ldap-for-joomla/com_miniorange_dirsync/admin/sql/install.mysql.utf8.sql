CREATE TABLE IF NOT EXISTS `#__miniorange_ldap_customer` (
`id` int(11) UNSIGNED NOT NULL ,
`email` VARCHAR(255)  NOT NULL ,
`password` VARCHAR(64)  NOT NULL ,
`admin_phone` VARCHAR(15)  NOT NULL ,
`customer_key` VARCHAR(255)  NOT NULL ,
`customer_token` VARCHAR(255) NOT NULL,
`api_key` VARCHAR(255)  NOT NULL,
`login_status` tinyint(1) DEFAULT FALSE,
`registration_status` VARCHAR(40) NOT NULL,
`transaction_id` VARCHAR(255) NOT NULL,
`email_count` int(11),
`sms_count` int(11),
`uninstall_feedback` int(2) NOT NULL,
`sso_var` VARCHAR(255) NOT NULL,
`sso_test` VARCHAR(255) NOT NULL,
`mo_cron_period` VARCHAR(255) NOT NULL,
`contact_admin_email` VARCHAR(255) NOT NULL,
PRIMARY KEY (`id`)
) DEFAULT COLLATE=utf8_general_ci;


CREATE TABLE IF NOT EXISTS `#__miniorange_dirsync_config` (
`id` int(11) UNSIGNED NOT NULL ,
`ldap_server_url` VARCHAR(255) NOT NULL ,
`service_account_dn` VARCHAR(255)  NOT NULL ,
`service_account_password` VARCHAR(64) NOT NULL ,
`search_base` VARCHAR(255)  NOT NULL ,
`search_filter` VARCHAR(255)  NOT NULL ,
`enable_dirsync_scheduler` VARCHAR(255)  NOT NULL ,
`sync_interval` VARCHAR(255) NOT NULL,
`delete_on_sync` VARCHAR(255)  NOT NULL,
`ldap_login` VARCHAR(2)  NOT NULL DEFAULT '1',
`username` VARCHAR(255),
`email` VARCHAR(255),
`name` VARCHAR(255),
`user_profile_attributes` text,
`user_field_attributes` text,
`uninstall_feedback` int(2)  NOT NULL,
`mo_ldap_directory_server_type` VARCHAR(255),
`proxy_server_url` VARCHAR(255),
`proxy_server_port` int(5) DEFAULT 0,
`proxy_username` VARCHAR(255),
`proxy_password` VARCHAR(255),
`proxy_set` VARCHAR(20),
`enable_tls` VARCHAR(10),
`ad_attribute_list` text,
`ldap_test_username` text,
`test_config_details` text,
`mo_ldap_enable_logger` TINYINT(1) NOT NULL DEFAULT 0,
PRIMARY KEY (`id`)
) DEFAULT COLLATE=utf8_general_ci;


CREATE TABLE IF NOT EXISTS `#__miniorange_ntlm` (
`id` int(11) UNSIGNED NOT NULL ,
`enable_ntlm` int(2) UNSIGNED NOT NULL ,
PRIMARY KEY (`id`)

) DEFAULT COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS `#__mo_ldap_logs`(
`id` INT AUTO_INCREMENT PRIMARY KEY,
`timestamp` DATETIME NOT NULL,
`log_level` VARCHAR(10) NOT NULL,
`message` TEXT NOT NULL, `file` VARCHAR (255),
`line_number` INT,
`function_call`  VARCHAR(255)
) DEFAULT COLLATE=utf8_general_ci;


CREATE TABLE IF NOT EXISTS `#__miniorange_ldap_role_mapping` (
`id` int(11) UNSIGNED NOT NULL ,
`default_role` VARCHAR(255)  NOT NULL ,
`mapping_value_default` VARCHAR(255)  NOT NULL ,
`role_mapping_count` int(11) UNSIGNED NOT NULL ,
`mapping_memberof_attribute` VARCHAR(255)  NOT NULL ,
`role_mapping_key_value` text,
`role_mapping_groupvalue` text,
`params` VARCHAR(255)  NOT NULL,
`enable_ldap_role_mapping` int(11) UNSIGNED NOT NULL ,
PRIMARY KEY (`id`)
) DEFAULT COLLATE=utf8_general_ci;


-- todo Need to add this table into update file
-- Table to track login attempts for brute-force protection.
CREATE TABLE IF NOT EXISTS `#__mo_ldap_login_attempts` (
`id` INT(11) NOT NULL AUTO_INCREMENT,
`username` VARCHAR(255) NOT NULL,
`ip_address` VARCHAR(100) NOT NULL,
`user_login_attempts` INT(11) NOT NULL DEFAULT 0,
`blocked_until` DATETIME DEFAULT NULL,
`last_attempt` DATETIME DEFAULT CURRENT_TIMESTAMP,
PRIMARY KEY (`id`)
) DEFAULT COLLATE=utf8_general_ci;



INSERT IGNORE INTO `#__miniorange_ntlm`(`id`) values (1);
INSERT IGNORE INTO `#__miniorange_ldap_customer`(`id`,`login_status`,`sso_var`) values (1,false,'MzA=') ;
INSERT IGNORE INTO `#__miniorange_dirsync_config`(`id`, `mo_ldap_directory_server_type`, `username`, `name`, `email`) values (1, 'msad', 'userprincipalname', 'cn', 'mail');
INSERT IGNORE INTO`#__miniorange_ldap_role_mapping`(`id`,`mapping_value_default`) values (1,'memberOf');
