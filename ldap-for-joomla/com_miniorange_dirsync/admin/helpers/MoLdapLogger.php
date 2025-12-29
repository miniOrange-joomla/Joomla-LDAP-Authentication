<?php
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
*This class use for loggers
*
**/
defined('_JEXEC') or die;

use Joomla\CMS\Log\Log;
use Joomla\CMS\Factory;

class MoLdapLogger
{
    public static function addLog($message, $type = 'INFO', $category = 'mo_ldap'): void
    {
        // Check if logging is enabled
        if (!self::isLoggingEnabled()) {
            return;
        }

        // Ensure logger is initialized only once
        static $loggerInitialized = false;
        if (!$loggerInitialized) {
            Log::addLogger(array('text_file' => 'mo_ldap.log', 'text_entry_format' => '{DATE} {TIME} {CATEGORY} [{PRIORITY}] {MESSAGE}'), Log::ALL, array($category));
            $loggerInitialized = true;
        }

        $priorityMap = [
            'INFO'     => Log::INFO,
            'NOTICE'   => Log::NOTICE,
            'WARNING'  => Log::WARNING,
            'ERROR'    => Log::ERROR,
            'ALERT'    => Log::ALERT,
            'CRITICAL' => Log::CRITICAL,
        ];


        $priority = $priorityMap[$type] ?? Log::INFO;

        // Get detailed debug trace
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $caller = $trace[1] ?? $trace[0];

        $file = $caller['file'] ?? 'Unknown file';
        $function = $caller['function'] ?? 'Unknown function';
        $line = $caller['line'] ?? 'Unknown line';

        $maxMessageLength = 1000;

        // Truncate message if it exceeds the limit
        if (strlen($message) > $maxMessageLength) {
            $message = substr($message, 0, $maxMessageLength) . '... [truncated]';
        }

        // Format the message with extra context
        $formattedMessage = sprintf("[%s:%s] [%s] - %s", basename($file), $line, $function, $message);

        // Add the log entry
        Log::add($formattedMessage, $priority, $category);

        self::saveLogToDatabase($message, $type, basename($file), $line, $function);

    }

    /**
     * Check if logging is enabled from the database.
     * @return bool
     */
    public static function isLoggingEnabled(): bool
    {
        try {
            $db = Factory::getDbo();
            $query = $db->getQuery(true)->select($db->quoteName('mo_ldap_enable_logger'))->from($db->quoteName('#__miniorange_dirsync_config'))->where($db->quoteName('id') . ' = 1');

            $db->setQuery($query);
            return (bool)$db->loadResult();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Saves a log entry to the Joomla database while ensuring log rotation.
     *
     * This function inserts a new log entry into the `#__mo_ldap_logs` table.
     * If the total number of logs exceeds the defined limit, it deletes the oldest logs
     * to prevent excessive database growth.
     *
     * @param string $message The log message to be stored.
     * @param string $type The log level (e.g., INFO, ERROR, NOTICE).
     * @param string $file The file where the log was generated.
     * @param int $line The line number where the log was generated.
     * @param string $function The function name where the log was generated.
     *
     * @return void
     */
    private static function saveLogToDatabase(string $message, string $type, string $file, int $line, string $function): void
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true);

        // Define maximum log entries allowed
        $maxLogs = 10000;

        $logCode = self::getLogCode($message);

        // Insert the new log entry
        $columns = ['timestamp', 'log_level', 'message', 'file', 'line_number', 'function_call'];
        $values = [$db->quote(date('Y-m-d H:i:s')), $db->quote($type), $db->quote(json_encode($logCode)), $db->quote($file), $db->quote($line), $db->quote($function),];

        $query->insert($db->quoteName('#__mo_ldap_logs'))->columns($db->quoteName($columns))->values(implode(',', $values));

        $db->setQuery($query);
        $db->execute();

        // Check if log entries exceed the limit
        $query = $db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__mo_ldap_logs'));

        $db->setQuery($query);
        $totalLogs = (int)$db->loadResult();

        // Delete the oldest logs if the limit is exceeded
        if ($totalLogs > $maxLogs) {
            $logsToDelete = $totalLogs - $maxLogs;
            $query = $db->getQuery(true)->delete($db->quoteName('#__mo_ldap_logs'))->order($db->quoteName('timestamp') . ' ASC')->setLimit($logsToDelete);

            $db->setQuery($query);
            $db->execute();
        }
    }

    /**
     * Retrieves the log code for a given log message.
     *
     * This function maps predefined log messages to corresponding log codes.
     * If a message does not have a predefined code, a generic log code is returned.
     *
     * @param string $message The log message to map.
     */
    private static function getLogCode(string $message): array
    {
        $logDetails = [
            // Authentication Errors
            'Password not provided' => ['code' => 'MOLDAP-A15', 'issue' => 'User did not enter a password.'],
            'Authentication successful.' => ['code' => 'MOLDAP-A09', 'issue' => 'User successfully authenticated.'],
            'LDAP Authentication failed.' => ['code' => 'MOLDAP-A08', 'issue' => 'Invalid credentials or LDAP issue.'],
            
            // Connection & Configuration Errors
            'LDAP Authentication enable status - false' => ['code' => 'MOLDAP-007', 'issue' => 'LDAP authentication is disabled.'],
            'LDAP bind failed.' => ['code' => 'MOLDAP-A01', 'issue' => 'Failed to bind to the LDAP server.'],
            'LDAP search failed.' => ['code' => 'MOLDAP-020', 'issue' => 'LDAP search operation failed.'],
            'LDAP connection failed.' => ['code' => 'MOLDAP-A02', 'issue' => 'Cannot connect to the LDAP server.'],
            'LDAP extensions disabled.' => ['code' => 'MOLDAP-A22', 'issue' => 'PHP LDAP extension is disabled.'],
            
            // User Attribute Errors
            'User email not retrieved.' => ['code' => 'MOLDAP-A03', 'issue' => 'Email attribute missing from LDAP response.'],
            'User email attribute that you have contacted is incorrect.' => ['code' => 'MOLDAP-A03', 'issue' => 'Incorrect email attribute configured.'],
            'Username not retrieved. Not getting user\'s username.' => ['code' => 'MOLDAP-A04', 'issue' => 'Username attribute is missing.'],
            'User\'s name not retrieved. Not getting user\'s name.' => ['code' => 'MOLDAP-A05', 'issue' => 'Name attribute is missing.'],
            'Username and Email mismatch.' => ['code' => 'MOLDAP-A10', 'issue' => 'Username and Email mismatch.'],
            
            // Licensing & Limits
            'Free Login Limit reached.' => ['code' => 'LIC-021', 'issue' => 'Maximum allowed free logins reached.']
        ];
        
        if (isset($logDetails[$message])) {
            return $logDetails[$message];
        }
        
        // Otherwise, return a generated code with the original message as the issue
        return [
            'code' => '_',
            'issue' => $message
        ];
    }

    /**
     * Retrieves all log entries from the database.
     *
     * Fetches logs from the `#__mo_ldap_logs` table, ordered by the most recent timestamp.
     *
     * @return array An array of log objects, each containing:
     *               - timestamp (string): The date and time of the log entry.
     *               - log_level (string): The severity level of the log (INFO, ERROR, etc.).
     *               - message (string): The log message.
     *               - file (string): The file where the log was generated.
     *               - line_number (int): The line number where the log was generated.
     *               - function_call (string): The function call where the log was triggered.
     */
    public static function getAllLogs(): array
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true)->select($db->quoteName(['timestamp', 'log_level', 'message', 'file', 'line_number', 'function_call']))->from($db->quoteName('#__mo_ldap_logs'))->order($db->quoteName('timestamp') . ' DESC');

        return $db->setQuery($query)->loadObjectList() ?: [];
    }
}
