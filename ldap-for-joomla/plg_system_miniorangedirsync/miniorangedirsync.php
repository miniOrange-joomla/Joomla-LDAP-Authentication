<?php
defined('_JEXEC') or die;

/**
 * @package     Joomla.Plugin
 * @subpackage  plg_system_miniorangedirsync
 *
 * @author      miniOrange Security Software Pvt. Ltd.
 * @copyright   Copyright (C) 2015 miniOrange (https://www.miniorange.com)
 * @license     GNU General Public License version 3; see LICENSE.txt
 * @contact     info@xecurify.com
 */

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;

jimport('joomla.plugin.plugin');

if (!class_exists('PlgSystemMiniorangedirsync', false))
{
	class PlgSystemMiniorangedirsync extends CMSPlugin
	{
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
				}
			}

			return Factory::getDbo();
		}

		public function onAfterInitialise()
		{
			$app = Factory::getApplication();
			$input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
			$post = ($input && $input->post) ? $input->post->getArray() : array();

			if (!isset($post['ldap_feedback']) && !isset($post['ldap_skip_feedback']))
			{
				return;
			}

			try
			{
				$tables = $this->getDb()->getTableList();
			}
			catch (\Throwable $e)
			{
				return;
			}

			if (!$this->hasConfigTable($tables))
			{
				return;
			}

			$user = Factory::getUser();
			$isAdmin = method_exists($app, 'isClient') ? $app->isClient('administrator') : $app->isAdmin();

			if (!$isAdmin || !$user->authorise('core.manage', 'com_installer') || !Session::checkToken())
			{
				return;
			}

			$radio = isset($post['deactivate_plugin']) ? $post['deactivate_plugin'] : '';
			$data = isset($post['query_feedback']) ? $post['query_feedback'] : '';
			$feedbackEmail = isset($post['feedback_email']) ? $post['feedback_email'] : '';

			try
			{
				$db = $this->getDb();
				$query = $db->getQuery(true);
				$query->update('#__miniorange_dirsync_config')
					->set($db->quoteName('uninstall_feedback') . ' = 1')
					->where($db->quoteName('id') . ' = 1');
				$db->setQuery($query);
				$db->execute();
			}
			catch (\Exception $e)
			{
			}

			$data1 = $radio . ' : ' . $data . '  <br><br><strong>Email:</strong>  ' . $feedbackEmail;

			if (!isset($post['ldap_skip_feedback'])
				&& file_exists(JPATH_ADMINISTRATOR . '/components/com_miniorange_dirsync/helpers/mo_customer_setup.php'))
			{
				require_once JPATH_ADMINISTRATOR . '/components/com_miniorange_dirsync/helpers/mo_customer_setup.php';

				try
				{
					MoLdapCustomer::submitUninstallFeedbackForm($feedbackEmail, '', $data1, '', $feedbackEmail);
				}
				catch (\Exception $e)
				{
				}
			}

			$allowed = array('com_miniorange_dirsync', 'miniorangedirsync', 'moldap', 'pkg_LdapforJoomla', 'pkg_ldapforjoomla');

			if (isset($post['result']) && is_array($post['result']))
			{
				foreach ($post['result'] as $fbkey)
				{
					$fbkey = (int) $fbkey;
					$db = $this->getDb();
					$query = $db->getQuery(true);
					$query->select('type, element')
						->from($db->quoteName('#__extensions'))
						->where($db->quoteName('extension_id') . ' = ' . $fbkey);
					$db->setQuery($query);
					$extension = $db->loadObject();

					if ($extension && in_array($extension->element, $allowed, true))
					{
						try
						{
							$installer = null;

							if (method_exists(Factory::class, 'getContainer'))
							{
								try
								{
									$container = Factory::getContainer();

									if ($container && method_exists($container, 'get'))
									{
										$installer = $container->get(Installer::class);
									}
								}
								catch (\Exception $e)
								{
								}
							}

							if (!$installer)
							{
								$installer = new Installer;

								if (method_exists($installer, 'setDatabase'))
								{
									$installer->setDatabase($this->getDb());
								}
							}

							$installer->uninstall($extension->type, $fbkey, 0);
						}
						catch (\Exception $e)
						{
						}
					}
				}
			}
		}

		public function onExtensionBeforeUninstall($event)
		{
			self::triggerUninstallFeedback($this->resolveExtensionId($event));
		}

		/**
		 * Entry for installer scripts (package / component / plugins) and legacy plugin event.
		 *
		 * @param   integer  $eid  Extension id being uninstalled.
		 *
		 * @return  void
		 */
		public static function triggerUninstallFeedback($eid)
		{
			$app = Factory::getApplication();
			// CMSPlugin expects an event dispatcher (attach/detach); the application object has no attach() on Joomla 3.
			if (method_exists($app, 'getDispatcher'))
			{
				$subject = $app->getDispatcher();
			}
			elseif (class_exists('\JEventDispatcher'))
			{
				$subject = \JEventDispatcher::getInstance();
			}
			else
			{
				$subject = $app;
			}

			$plugin = new self($subject, array());
			$plugin->maybeShowFeedbackFormByExtensionId((int) $eid);
		}

		private function maybeShowFeedbackFormByExtensionId($eid)
		{
			try
			{
				$db = $this->getDb();
				$query = $db->getQuery(true)
					->select($db->quoteName('enabled'))
					->from($db->quoteName('#__extensions'))
					->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
					->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
					->where($db->quoteName('element') . ' = ' . $db->quote('miniorangedirsync'));
				$db->setQuery($query);

				if ((int) $db->loadResult() !== 1)
				{
					return;
				}
			}
			catch (\Throwable $e)
			{
				return;
			}

			try
			{
				$tables = $this->getDb()->getTableList();
			}
			catch (\Throwable $e)
			{
				return;
			}

			if (!$this->hasConfigTable($tables))
			{
				return;
			}

			if ($eid <= 0 || !$this->isLdapExtensionId($eid))
			{
				return;
			}

			try
			{
				$db = $this->getDb();
				$query = $db->getQuery(true)
					->select($db->quoteName('uninstall_feedback'))
					->from($db->quoteName('#__miniorange_dirsync_config'))
					->where($db->quoteName('id') . ' = 1');
				$db->setQuery($query);
				$fid = (int) $db->loadResult();
			}
			catch (\Exception $e)
			{
				return;
			}

			if ($fid !== 0)
			{
				return;
			}

			$app = Factory::getApplication();
			$input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
			$post = ($input && $input->post) ? $input->post->getArray() : array();
			$cids = isset($post['cid']) && is_array($post['cid']) ? $post['cid'] : $input->get('cid', array(), 'array');

			if (empty($cids))
			{
				$cids = array($eid);
			}

			$post['cid'] = array_map('intval', (array) $cids);
			$this->showFeedbackForm($post, $this->getFeedbackEmail());
			exit;
		}

		private function hasConfigTable($tables)
		{
			foreach ($tables as $table)
			{
				if (strpos($table, 'miniorange_dirsync_config') !== false)
				{
					return true;
				}
			}

			return false;
		}

		private function resolveExtensionId($event)
		{
			if (is_int($event) || (is_string($event) && ctype_digit($event)))
			{
				return (int) $event;
			}

			if (is_object($event))
			{
				if (method_exists($event, 'getEid'))
				{
					return (int) $event->getEid();
				}

				if (method_exists($event, 'getArgument'))
				{
					return (int) $event->getArgument('eid', 0);
				}
			}

			return 0;
		}

		private function isLdapExtensionId($eid)
		{
			$db = $this->getDb();
			$query = $db->getQuery(true)
				->select($db->quoteName('extension_id'))
				->from($db->quoteName('#__extensions'))
				->where(
					'(' . $db->quoteName('type') . ' = ' . $db->quote('component')
					. ' AND ' . $db->quoteName('element') . ' = ' . $db->quote('com_miniorange_dirsync') . ')'
					. ' OR (' . $db->quoteName('type') . ' = ' . $db->quote('plugin')
					. ' AND ' . $db->quoteName('element') . ' = ' . $db->quote('moldap')
					. ' AND ' . $db->quoteName('folder') . ' = ' . $db->quote('authentication') . ')'
					. ' OR (' . $db->quoteName('type') . ' = ' . $db->quote('plugin')
					. ' AND ' . $db->quoteName('element') . ' = ' . $db->quote('miniorangedirsync') . ')'
					. ' OR (' . $db->quoteName('type') . ' = ' . $db->quote('package')
					. ' AND (' . $db->quoteName('element') . ' = ' . $db->quote('pkg_LdapforJoomla')
					. ' OR ' . $db->quoteName('element') . ' = ' . $db->quote('pkg_ldapforjoomla') . '))'
				);
			$db->setQuery($query);
			$ids = array_map('intval', (array) $db->loadColumn());

			return in_array((int) $eid, $ids, true);
		}

		private function getFeedbackEmail()
		{
			try
			{
				$db = $this->getDb();
				$query = $db->getQuery(true)
					->select(array($db->quoteName('email'), $db->quoteName('contact_admin_email')))
					->from($db->quoteName('#__miniorange_ldap_customer'))
					->where($db->quoteName('id') . ' = 1');
				$db->setQuery($query);
				$row = $db->loadAssoc();

				if (!empty($row['email']))
				{
					return (string) $row['email'];
				}

				if (!empty($row['contact_admin_email']))
				{
					return (string) $row['contact_admin_email'];
				}
			}
			catch (\Exception $e)
			{
			}

			return Factory::getUser()->email ?? '';
		}

		private function showFeedbackForm($tpostData, $feedbackEmail)
		{
			$deactivateReasons = array(
				"Does not have the features I'm looking for",
				'Confusing Interface',
				'Redirecting back to login page after Authentication',
				'Not Working',
				'Bugs in the plugin',
				'Not able to Configure',
				'Other Reasons:',
			);

			$deactivateReasonsHtml = '';

			foreach ($deactivateReasons as $index => $deactivateReason)
			{
				$reason = htmlspecialchars($deactivateReason, ENT_QUOTES, 'UTF-8');
				$inputId = 'mo_ldap_fb_reason_' . (int) $index;
				$deactivateReasonsHtml .= '<label class="mo_ldap_feedback_radio" for="' . $inputId . '">';
				$deactivateReasonsHtml .= '<input type="radio" name="deactivate_plugin" id="' . $inputId . '" value="' . $reason . '" required>';
				$deactivateReasonsHtml .= '<span>' . $reason . '</span></label>';
			}

			$hiddenCidInputs = '';

			foreach ((array) ($tpostData['cid'] ?? array()) as $key)
			{
				$hiddenCidInputs .= '<input type="hidden" name="result[]" value="' . (int) $key . '">';
			}

			$feedbackEmailEsc = htmlspecialchars((string) $feedbackEmail, ENT_QUOTES, 'UTF-8');
			$cssUrl = htmlspecialchars(Uri::base() . 'components/com_miniorange_dirsync/assets/css/miniorange_license.css', ENT_QUOTES, 'UTF-8');

			?>
			<link rel="stylesheet" type="text/css" href="<?php echo $cssUrl; ?>" />
			<div class="mo_ldap_feedback_page">
				<div class="mo_ldap_feedback_card">
					<div class="mo_ldap_feedback_header">
						<span class="mo_ldap_feedback_title">Feedback Form for LDAP</span>
						<button type="button" class="mo_ldap_feedback_close" onclick="skipLdapForm()" title="Skip Feedback" aria-label="Close">&times;</button>
					</div>
					<form method="post" action="" id="ldap_feedback" class="mo_ldap_feedback_body">
						<?php echo HTMLHelper::_('form.token'); ?>
						<input type="hidden" name="ldap_feedback" value="ldap_feedback"/>
						<h3>What Happened?</h3>
						<div class="mo_ldap_feedback_radios"><?php echo $deactivateReasonsHtml; ?></div>
						<textarea id="query_feedback" name="query_feedback" class="mo_ldap_feedback_textarea" rows="4" placeholder="Write your query here"></textarea>
						<label class="mo_ldap_feedback_email_label" for="feedback_email">Email <span class="mo_ldap_required">*</span></label>
						<input type="email" id="feedback_email" name="feedback_email" class="mo_ldap_feedback_email" required value="<?php echo $feedbackEmailEsc; ?>" placeholder="Enter email to contact." />
						<?php echo $hiddenCidInputs; ?>
						<button type="submit" name="miniorange_feedback_submit" class="mo_ldap_feedback_submit">Submit</button>
					</form>
					<form method="post" action="" id="ldap_feedback_form_close" style="display:none;">
						<?php echo HTMLHelper::_('form.token'); ?>
						<input type="hidden" name="ldap_skip_feedback" value="ldap_skip_feedback"/>
						<?php echo $hiddenCidInputs; ?>
					</form>
				</div>
			</div>
			<script src="https://code.jquery.com/jquery-3.6.3.js"></script>
			<script>
				document.body.classList.add('mo_ldap_feedback_page');
				jQuery('input:radio[name="deactivate_plugin"]').on('change', function () {
					var reason = jQuery(this).val(), $q = jQuery('#query_feedback');
					$q.removeAttr('required').attr('placeholder', 'Write your query here');
					if (reason === 'Other Reasons:') { $q.prop('required', true); }
				});
				function skipLdapForm() { jQuery('#ldap_feedback_form_close').submit(); }
			</script>
			<?php
		}
	}
}
