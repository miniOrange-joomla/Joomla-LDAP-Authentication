DROP TABLE IF EXISTS `#__miniorange_dirsync_config`;
DROP TABLE IF EXISTS `#__miniorange_ntlm`;
DROP TABLE IF EXISTS `#__miniorange_ldap_customer`;
DROP TABLE IF EXISTS `#__miniorange_ldap_role_mapping`;
DROP TABLE IF EXISTS `#__mo_ldap_logs`;
DROP TABLE IF EXISTS `#__mo_ldap_login_attempts`;

ALTER TABLE `#__users` DROP COLUMN `user_already_exist`;