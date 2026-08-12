<?php

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;


/**
 * @package     Joomla.Package
 *
 * @author      miniOrange Security Software Pvt. Ltd.
 * @copyright   Copyright (C) 2015 miniOrange (https://www.miniorange.com)
 * @license     GNU General Public License version 3; see LICENSE.txt
 * @contact     info@xecurify.com
 */
class PkgLdapforjoomlaInstallerScript
{
	/**
	 * Runs after package install/update.
	 *
	 * @param   string  $type    The type of change (install, update or discover-install).
	 * @param   object  $parent  The class calling this method.
	 *
	 * @return  boolean
	 */
	public function postflight($type, $parent)
	{
		if ($type == 'uninstall')
		{
			return true;
		}

		$this->enableExtensions();
		$this->addUserColumn();
		$this->pluginEfficiencyCheck();
		$this->showInstallMessage('');

		return true;
	}

	/**
	 * Enable the bundled plugins after install/update. Joomla installs plugins
	 * in a disabled state by default, so the system and authentication plugins
	 * are explicitly enabled here. This runs after every sub-extension in the
	 * package has been installed, so it works on both fresh installs and
	 * updates and does not depend on sub-install ordering.
	 *
	 * @return  void
	 */
	private function enableExtensions(): void
	{
		$extensions = array(
			array('element' => 'moldap',            'type' => 'plugin',    'folder' => 'authentication'),
			array('element' => 'miniorangedirsync', 'type' => 'plugin',    'folder' => 'system'),
			array('element' => 'com_miniorange_dirsync', 'type' => 'component', 'folder' => ''),
		);

		try
		{
			$db = $this->getDb();

			foreach ($extensions as $extension)
			{
				$query = $db->getQuery(true)
					->update($db->quoteName('#__extensions'))
					->set($db->quoteName('enabled') . ' = 1')
					->where($db->quoteName('element') . ' = ' . $db->quote($extension['element']))
					->where($db->quoteName('type') . ' = ' . $db->quote($extension['type']));

				if ($extension['folder'] !== '')
				{
					$query->where($db->quoteName('folder') . ' = ' . $db->quote($extension['folder']));
				}

				$db->setQuery($query);
				$db->execute();
			}
		}
		catch (\Throwable $e)
		{
			// Never fail installation because a plugin could not be auto-enabled.
			error_log('miniOrange LDAP: failed to enable extensions: ' . $e->getMessage());
		}
	}

	/**
	 * Runs when the package is uninstalled. The `user_already_exist` column is
	 * removed here (not in the SQL uninstall file) so the removal is portable
	 * across every MySQL/MariaDB, PHP and Joomla version.
	 *
	 * @param   object  $parent  The class calling this method.
	 *
	 * @return  boolean
	 */
	public function uninstall($parent)
	{
		$this->removeUserColumn();

		return true;
	}

	/**
	 * Resolves the database driver in a way that works on Joomla 3, 4, 5 and 6.
	 *
	 * @return  object
	 */
	private function getDb()
	{
		if (method_exists(Factory::class, 'getContainer'))
		{
			try
			{
				return Factory::getContainer()->get(DatabaseInterface::class);
			}
			catch (\Throwable $e)
			{
				// Container not available on this version; fall back below.
			}
		}

		return Factory::getDbo();
	}

	/**
	 * Portable column existence check. Avoids the MariaDB-only
	 * `IF EXISTS`/`IF NOT EXISTS` column syntax that MySQL rejects.
	 *
	 * @param   object  $db      Database driver.
	 * @param   string  $table   Table name.
	 * @param   string  $column  Column name.
	 *
	 * @return  boolean
	 */
	private function columnExists($db, string $table, string $column): bool
	{
		try
		{
			$columns = $db->getTableColumns($table, false);

			return is_array($columns) && array_key_exists($column, $columns);
		}
		catch (\Throwable $e)
		{
			return false;
		}
	}

	/**
	 * Add the user_already_exist column if it does not exist.
	 *
	 * @return  void
	 */
	private function addUserColumn(): void
	{
		try
		{
			$db = $this->getDb();

			if ($this->columnExists($db, '#__users', 'user_already_exist'))
			{
				return;
			}

			$db->setQuery(
				'ALTER TABLE ' . $db->quoteName('#__users')
				. ' ADD COLUMN ' . $db->quoteName('user_already_exist') . ' INT DEFAULT 0'
			);
			$db->execute();
		}
		catch (\Throwable $e)
		{
			// Never fail installation because of this optional column.
			error_log('miniOrange LDAP: failed to add user_already_exist column: ' . $e->getMessage());
		}
	}

	/**
	 * Remove the user_already_exist column if it exists.
	 *
	 * @return  void
	 */
	private function removeUserColumn(): void
	{
		try
		{
			$db = $this->getDb();

			if (!$this->columnExists($db, '#__users', 'user_already_exist'))
			{
				// Nothing to drop; avoids the warning the user was seeing.
				return;
			}

			$db->setQuery(
				'ALTER TABLE ' . $db->quoteName('#__users')
				. ' DROP COLUMN ' . $db->quoteName('user_already_exist')
			);
			$db->execute();
		}
		catch (\Throwable $e)
		{
			// Never fail uninstallation because of this optional column.
			error_log('miniOrange LDAP: failed to remove user_already_exist column: ' . $e->getMessage());
		}
	}

	/**
	 * Submit plugin installation feedback.
	 *
	 * @return  void
	 */
	private function pluginEfficiencyCheck(): void
	{
		$app = Factory::getApplication();
		$user = $app->getIdentity();
		$email = isset($user->email) ? $user->email : 'admin@unknown.com';

		$helperPath = JPATH_BASE . '/components/com_miniorange_dirsync/helpers/mo_customer_setup.php';

		if (file_exists($helperPath))
		{
			require_once $helperPath;
			MoLdapCustomer::moLdapSubmitFeedbackForm("Plugin Installed", $email, true);
		}
	}

	/**
	 * Display the post-install message.
	 *
	 * @param   array  $messages  Optional messages to display.
	 *
	 * @return  void
	 */
	protected function showInstallMessage($messages = array())
	{
		?>
		<style>

		.mo-row {
			width: 100%;
			display: block;
			margin-bottom: 2%;
		}

		.mo-row:after {
			clear: both;
			display: block;
			content: "";
		}

		.mo-column-2 {
			width: 19%;
			margin-right: 1%;
			float: left;
		}

		.mo-column-10 {
			width: 80%;
			float: left;
		}
		.mo_ldap_btn {
			display: inline-block;
			font-weight: 300;
			text-align: center;
			vertical-align: middle;
			user-select: none;
			background-color: transparent;
			border: 1px solid transparent;
			padding: 4px 12px;
			font-size: 0.85rem;
			line-height: 1.5;
			border-radius: 0.25rem;
			transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
		}

		.mo_ldap_btn-cstm {
			background: #001b4c;
			border: none;
			font-size: 1.1rem;
			padding: 0.3rem 1.5rem;
			color: #fff !important;
			cursor: pointer;
		}

		:root[data-color-scheme=dark] {
			.mo_ldap_btn-cstm {
				color: white;
				background-color: #000000;
				border-color:1px solid #ffffff;
			}

			.mo_ldap_btn-cstm:hover {
				background-color: #000000;
				border-color: #ffffff;
			}
		}
		</style>
		<h4>LDAP Integration with Joomla</h4>
		<p>The plugin package for LDAP Integration with Active Directory and OpenLDAP - NTLM & Kerberos Login for Joomla is now compatible with Joomla 6.x.</p>
		<h5>Steps to use the LDAP plugin:</h5>
		<ul>
			<li>Click on <b>Components</b></li>
			<li>Click on <b>miniOrange LDAP</b> and select the <b>LDAP Configuration</b> tab</li>
			<li>You can start configuring.</li>
		</ul>
		<div class="mo-row">
			<a class="mo_ldap_btn mo_ldap_btn-cstm" href="index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration">Get Started!</a>
			<a class="mo_ldap_btn mo_ldap_btn-cstm" href="https://plugins.miniorange.com/joomla-sso-ldap-mfa-solutions?section=ldap" target="_blank">Setup Guide!</a>
			<a class="mo_ldap_btn mo_ldap_btn-cstm" href="https://plugins.miniorange.com/joomla-ldap-changelog" target="_blank">Change Log!</a>
			<a class="mo_ldap_btn mo_ldap_btn-cstm" href="https://www.miniorange.com/contact" target="_blank">Get Support!</a>

		</div>
		<?php
	}
}

// Joomla package installer script class name compatibility.
class_alias(PkgLdapforjoomlaInstallerScript::class, 'pkg_LdapforJoomlaInstallerScript');
class_alias(PkgLdapforjoomlaInstallerScript::class, 'pkg_LDAPFORJOOMLAInstallerScript');
