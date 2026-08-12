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
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
jimport('joomla.plugin.plugin');

if (!class_exists('PlgSystemMiniorangedirsync', false))
{
	class PlgSystemMiniorangedirsync extends CMSPlugin
	{
		/**
		 * Resolve the database driver without depending on component files.
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
		 * Handle feedback form submission (runs early in page lifecycle)
		 */
		public function onAfterInitialise()
		{
			// Backward compatibility for Joomla 3/4/5/6
			$app = Factory::getApplication();
			$input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
			$post = ($input && $input->post) ? $input->post->getArray() : array();

			try
			{
				$tables = $this->getDb()->getTableList();
			}
			catch (\Throwable $e)
			{
				return;
			}

			$tab = 0;

			// Check if config table exists
			foreach ($tables as $table)
			{
				if (strpos($table, "miniorange_dirsync_config") !== false)
				{
					$tab = $table;
				}
			}

			if ($tab === 0)
			{
				return;
			}

			// Check if this is feedback submission
			if (isset($post['ldap_feedback']) || isset($post['ldap_skip_feedback']))
			{
				if ($tab)
				{
					$radio = isset($post['deactivate_plugin']) ? $post['deactivate_plugin'] : '';
					$data = isset($post['query_feedback']) ? $post['query_feedback'] : '';
					$feedbackEmail = isset($post['feedback_email']) ? $post['feedback_email'] : '';

					// Mark feedback as submitted
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
					catch (Exception $e)
					{
						// Continue
					}

					// Get admin email and phone
					$db = $this->getDb();

					try
					{
						$query = $db->getQuery(true);
						$query->select('admin_email, admin_phone')
							->from($db->quoteName('#__miniorange_ldap_customer'))
							->where($db->quoteName('id') . ' = 1');
						$db->setQuery($query);
						$customerResult = $db->loadAssoc();
						$adminEmail = isset($customerResult['admin_email']) ? $customerResult['admin_email'] : '';
						$adminPhone = isset($customerResult['admin_phone']) ? $customerResult['admin_phone'] : '';
					}
					catch (Exception $e)
					{
						$adminEmail = '';
						$adminPhone = '';
					}

					$data1 = $radio . ' : ' . $data . '  <br><br><strong>Email:</strong>  ' . $feedbackEmail;

					if (isset($post['ldap_skip_feedback']))
					{
						$data1 = 'Skipped the feedback';
					}

					// Send email if not skipped
					if (file_exists(JPATH_ADMINISTRATOR . '/components/com_miniorange_dirsync/helpers/mo_customer_setup.php'))
					{
						require_once JPATH_ADMINISTRATOR . '/components/com_miniorange_dirsync/helpers/mo_customer_setup.php';
						require_once JPATH_ADMINISTRATOR . '/components/com_miniorange_dirsync/helpers/mo_ldap_utility.php';

						try
						{
							$response = MoLdapCustomer::submitUninstallFeedbackForm($adminEmail, $adminPhone, $data1, '', $feedbackEmail);
						}
						catch (Exception $e)
						{
							echo "<h2 style='color:red;'>❌ Exception caught!</h2>";
							echo "<p>Error: " . $e->getMessage() . "</p>";
						}
					}

					// Uninstall all LDAP components
					if (isset($post['result']) && is_array($post['result']))
					{
						foreach ($post['result'] as $fbkey)
						{
							$db = $this->getDb();
							$query = $db->getQuery(true);
							$query->select('type, name')
								->from($db->quoteName('#__extensions'))
								->where($db->quoteName('extension_id') . ' = ' . (int) $fbkey);
							$db->setQuery($query);
							$extension = $db->loadObject();

							if ($extension)
							{
								$cid = 0;

								try
								{
									$installer = null;

									// Try Joomla 4+ dependency injection container first
									if (method_exists('Joomla\CMS\Factory', 'getContainer'))
									{
										try
										{
											$container = Factory::getContainer();

											if ($container && method_exists($container, 'get'))
											{
												$installer = $container->get(Installer::class);
											}
										}
										catch (Exception $e)
										{
											// Container approach failed, continue to fallback
										}
									}

									// Fallback: manual instantiation for all versions
									if (!$installer)
									{
										$installer = new Installer;

										if (method_exists($installer, 'setDatabase'))
										{
											$installer->setDatabase($this->getDb());
										}
									}

									$result = $installer->uninstall($extension->type, $fbkey, $cid);
								}
								catch (Exception $e)
								{
								}
							}
						}
					}
				}
			}
		}

		/**
		 * Event triggered before extension uninstall - displays feedback form
		 */
		public function onExtensionBeforeUninstall($id)
		{
			// Backward compatibility for Joomla 3/4/5/6
			$app = Factory::getApplication();
			$input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
			$post = ($input && $input->post) ? $input->post->getArray() : array();

			try
			{
				$tables = $this->getDb()->getTableList();
				$db = $this->getDb();
			}
			catch (\Throwable $e)
			{
				return;
			}

			// Get component extension IDs
			$query = $db->getQuery(true);
			$query->select('extension_id')
				->from($db->quoteName('#__extensions'))
				->where($db->quoteName('element') . ' = ' . $db->quote('com_miniorange_dirsync'));
			$db->setQuery($query);
			$result = $db->loadColumn();

			$tab = 0;

			foreach ($tables as $table)
			{
				if (strpos($table, "miniorange_dirsync_config") !== false)
				{
					$tab = $table;
				}
			}

			if ($tab === 0)
			{
				return;
			}

			if ($tab)
			{
				// Check if feedback has been submitted
				try
				{
					$query = $db->getQuery(true);
					$query->select('uninstall_feedback')
						->from($db->quoteName('#__miniorange_dirsync_config'))
						->where($db->quoteName('id') . ' = 1');
					$db->setQuery($query);
					$fid = $db->loadResult();
				}
				catch (Exception $e)
				{
					$fid = null;
				}

				$tpostData = $post;

				// Get customer email
				try
				{
					$query = $db->getQuery(true);
					$query->select('admin_email')
						->from($db->quoteName('#__miniorange_ldap_customer'))
						->where($db->quoteName('id') . ' = 1');
					$db->setQuery($query);
					$customerResult = $db->loadAssoc();
					$feedbackEmail = !empty($customerResult['admin_email']) ? $customerResult['admin_email'] : '';
				}
				catch (Exception $e)
				{
					$feedbackEmail = '';
				}

				if ($fid == 0)
				{
					foreach ($result as $results)
					{
						if ($results == $id)
						{
							$this->showFeedbackForm($tpostData, $feedbackEmail);
							exit;
						}
					}
				}
			}
		}

		/**
		 * Display the feedback form
		 *
		 * @param   array   $tpostData       Posted form data.
		 * @param   string  $feedbackEmail   Customer feedback email.
		 *
		 * @return  void
		 */
		private function showFeedbackForm($tpostData, $feedbackEmail)
		{
			$deactivateReasons = array(
			'Configuration Issues',
			'Require Assistance',
			'Pricing Concerns',
			'Does not fit our requirements',
			'Not Working',
			'Bugs in the Plugin',
			'Other Reasons',
			);

			$deactivateReasonsHtml = '';

			foreach ($deactivateReasons as $deactivateReason)
			{
				$reason = htmlspecialchars($deactivateReason, ENT_QUOTES, 'UTF-8');
				$deactivateReasonsHtml .= '<div class="radio" style="padding:1px;margin-left:2%">';
				$deactivateReasonsHtml .= '<label style="font-weight:normal;font-size:16.6px;" for="' . $reason . '">';
				$deactivateReasonsHtml .= '<input type="radio" name="deactivate_plugin" value="' . $reason . '" required>';
				$deactivateReasonsHtml .= $reason . '</label></div>';
			}

			$hiddenCidInputs = '';

			foreach ($tpostData['cid'] as $key)
			{
				$hiddenCidInputs .= '<input type="hidden" name="result[]" value="';
				$hiddenCidInputs .= htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8') . '">';
			}

			?>
			<link rel="stylesheet" type="text/css" href="<?php echo Uri::base();?>/components/com_miniorange_dirsync/assets/css/miniorange_license.css" />
		<div class="form-style-6" style="width:35% !important; margin-left:33%; margin-top: 4%; position: relative;">
			<span onclick="skipLdapForm()" style="position: absolute; top: 10px; right: 15px; font-size: 24px; font-weight: bold; cursor: pointer;" title="Skip Feedback">&times;</span>
			<h1>Feedback form for LDAP Free Plugin</h1>
			<form name="f" method="post" action="" id="ldap_feedback" style="background: #f3f1f1; padding: 10px;">
				<h3>What Happened? </h3>
				<input type="hidden" name="ldap_feedback" value="ldap_feedback"/>
				<div>
					<p style="margin-left:2%">
				<?php echo $deactivateReasonsHtml; ?>
						<br>

						<textarea id="query_feedback" name="query_feedback" rows="4" style="margin-left:3%;width: 100%" cols="50" placeholder="Write your query here"></textarea><br><br><br>
						<tr>
							<td width="20%"><strong>Email<span style="color: #ff0000;">*</span>:</strong></td>
							<td><input type="email" name="feedback_email" required value="<?php echo $feedbackEmail; ?>" placeholder="Enter email to contact." style="width:80%"/></td>
						</tr>

				<?php echo $hiddenCidInputs; ?>
						<br><br>
						<div class="mojsp_modal-footer" style="text-align:center">
							<input style="cursor: pointer;font-size: large;" type="submit" name="miniorange_feedback_submit" class="mo_boot_btn btn-users_sync mo_boot_p-3" value="Submit"/>
						</div>
				</div>
			</form>
			<form name="f" method="post" action="" id="ldap_feedback_form_close" style="display:none;">
				<input type="hidden" name="ldap_skip_feedback" value="ldap_skip_feedback"/>
				<?php echo $hiddenCidInputs; ?>
			</form>
		</div>
		<script src="https://code.jquery.com/jquery-3.6.3.js"></script>
		<script>
			jQuery('input:radio[name="deactivate_plugin"]').click(function () {
				var reason = jQuery(this).val();
				jQuery('#query_feedback').removeAttr('required')
				if (reason === 'Configuration Issues') {
					jQuery('#query_feedback').attr("placeholder",'Can you please describe the issue in detail?');
				}
				else if (reason === 'Does not fit our requirements') {
					jQuery('#query_feedback').attr("placeholder", 'Let us know what feature are you looking for');
				}
				else if (reason === 'Not Working' || reason === 'Bugs in the Plugin') {
					jQuery('#query_feedback').attr("placeholder", 'Can you please let us know which plugin part you find not working?');
				}
				else if (reason === 'Other Reasons:') {
					jQuery('#query_feedback').attr("placeholder", 'Can you let us know the reason for deactivation?');
					jQuery('#query_feedback').prop('required', true);
				}
				else if(reason == 'Pricing Concerns'){
				jQuery('#query_feedback').attr("placeholder",'Let us know what you feel about the pricing of the plugins');
				}
			});

			function skipLdapForm() {
				jQuery('#ldap_feedback_form_close').submit();
			}
		</script>
		<?php
		}
	}
}
