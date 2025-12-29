-- Create the LDAP logs table if it doesn't exist
CREATE TABLE IF NOT EXISTS `#__mo_ldap_logs` (
`id` INT AUTO_INCREMENT PRIMARY KEY,
`timestamp` DATETIME NOT NULL,
`log_level` VARCHAR(10) NOT NULL,
`message` TEXT NOT NULL,
`file` VARCHAR(255),
`line_number` INT,
`function_call` VARCHAR(255)
) DEFAULT COLLATE=utf8_general_ci;

-- Add the logging enable flag to the config table
ALTER TABLE `#__miniorange_dirsync_config`
ADD COLUMN `mo_ldap_enable_logger` TINYINT(1) NOT NULL DEFAULT 0;

-- Set ldap_login to default to enabled (true) for existing installations
ALTER TABLE `#__miniorange_dirsync_config`
MODIFY COLUMN `ldap_login` VARCHAR(2) NOT NULL DEFAULT '1';

-- Added table for brute-force login protection
CREATE TABLE IF NOT EXISTS `#__mo_ldap_login_attempts` (
`id` INT(11) NOT NULL AUTO_INCREMENT,
`username` VARCHAR(255) NOT NULL,
`ip_address` VARCHAR(100) NOT NULL,
`user_login_attempts` INT(11) NOT NULL DEFAULT 0,
`blocked_until` DATETIME DEFAULT NULL,
`last_attempt` DATETIME DEFAULT CURRENT_TIMESTAMP,
PRIMARY KEY (`id`)
) DEFAULT COLLATE=utf8_general_ci;