<?php
/**
 * @package     Joomla.Component
 * @subpackage  com_miniorange_dirsync
 *
 * @author      miniOrange Security Software Pvt. Ltd.
 * @copyright   Copyright (C) 2015 miniOrange (https://www.miniorange.com)
 * @license     GNU General Public License version 3; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/**
 * Renders the LDAP plugin Overview tab.
 *
 * @param   boolean  $extensionEnabled  Whether required plugins and PHP extensions are active.
 * @param   string   $pluginVersion     Installed component version.
 *
 * @return  void
 */
function moLdapOverview($extensionEnabled, $pluginVersion)
{
	$logoUrl = htmlspecialchars(Uri::base() . MoConstants::IMAGES_PATH . '/miniorange_i.ico', ENT_QUOTES, 'UTF-8');
	$ldapConfigUrl = Route::_('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=ldapconfiguration');
	$licensingUrl = Route::_('index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=licensing');
	$trialUrl = htmlspecialchars(MoConstants::JOOMLA_SANDBOX_TRIAL_URL, ENT_QUOTES, 'UTF-8');
	$guideUrl = htmlspecialchars(MoConstants::LDAP_CONFIGURATION_GUIDE, ENT_QUOTES, 'UTF-8');
	$videoUrl = htmlspecialchars(MoConstants::LDAP_VIDEO_SETUP_GUIDE, ENT_QUOTES, 'UTF-8');
	$exploreUrl = htmlspecialchars('https://plugins.miniorange.com/joomla', ENT_QUOTES, 'UTF-8');
	$versionEsc = htmlspecialchars($pluginVersion, ENT_QUOTES, 'UTF-8');

	$providers = array(
		array('icon' => 'fa fa-windows', 'label' => Text::_('COM_MINIORANGE_MICROSOFT_AD')),
		array('icon' => 'fa fa-database', 'label' => Text::_('COM_MINIORANGE_OPENLDAP')),
		array('icon' => 'fa fa-server', 'label' => Text::_('COM_MINIORANGE_FREEIPA')),
		array('icon' => 'fa fa-cloud', 'label' => Text::_('COM_MINIORANGE_JUMPCLOUD')),
		array('icon' => 'fa fa-sitemap', 'label' => Text::_('COM_MINIORANGE_NOVELL_EDIRECTORY')),
		array('icon' => 'fa fa-building', 'label' => Text::_('COM_MINIORANGE_ORACLE_UNIFIED_DIRECTORY')),
		array('icon' => 'fa fa-shield', 'label' => Text::_('COM_MINIORANGE_389_DIRECTORY_SERVER')),
		array('icon' => 'fa fa-ellipsis-h', 'label' => Text::_('COM_MINIORANGE_ANY_LDAP_SERVER')),
	);

	$steps = array(
		array(
			'title' => Text::_('COM_MINIORANGE_OVERVIEW_STEP1_TITLE'),
			'desc'  => Text::_('COM_MINIORANGE_OVERVIEW_STEP1_DESC'),
		),
		array(
			'title' => Text::_('COM_MINIORANGE_OVERVIEW_STEP2_TITLE'),
			'desc'  => Text::_('COM_MINIORANGE_OVERVIEW_STEP2_DESC'),
		),
		array(
			'title' => Text::_('COM_MINIORANGE_OVERVIEW_STEP3_TITLE'),
			'desc'  => Text::_('COM_MINIORANGE_OVERVIEW_STEP3_DESC'),
		),
		array(
			'title' => Text::_('COM_MINIORANGE_OVERVIEW_STEP4_TITLE'),
			'desc'  => Text::_('COM_MINIORANGE_OVERVIEW_STEP4_DESC'),
		),
		array(
			'title' => Text::_('COM_MINIORANGE_OVERVIEW_STEP5_TITLE'),
			'desc'  => Text::_('COM_MINIORANGE_OVERVIEW_STEP5_DESC'),
		),
	);

	$resources = array(
		array(
			'icon'  => 'fa fa-file-text-o',
			'iconClass' => 'mo_ldap_overview_icon_doc',
			'title' => Text::_('COM_MINIORANGE_OVERVIEW_RESOURCE_SETUP_TITLE'),
			'desc'  => Text::_('COM_MINIORANGE_OVERVIEW_RESOURCE_SETUP_DESC'),
			'btn'   => Text::_('COM_MINIORANGE_OVERVIEW_VIEW_GUIDE'),
			'url'   => $guideUrl,
			'external' => true,
		),
		array(
			'icon'  => 'fa fa-youtube-play',
			'iconClass' => 'mo_ldap_overview_icon_video',
			'title' => Text::_('COM_MINIORANGE_OVERVIEW_RESOURCE_VIDEO_TITLE'),
			'desc'  => Text::_('COM_MINIORANGE_OVERVIEW_RESOURCE_VIDEO_DESC'),
			'btn'   => Text::_('COM_MINIORANGE_OVERVIEW_WATCH_VIDEO'),
			'url'   => $videoUrl,
			'external' => true,
		),
		array(
			'icon'  => 'fa fa-headphones',
			'iconClass' => 'mo_ldap_overview_icon_support',
			'title' => Text::_('COM_MINIORANGE_OVERVIEW_RESOURCE_TRIAL_TITLE'),
			'desc'  => Text::_('COM_MINIORANGE_OVERVIEW_RESOURCE_TRIAL_DESC'),
			'btn'   => Text::_('COM_MINIORANGE_LDAP_FREE_TRIAL'),
			'url'   => $trialUrl,
			'external' => true,
		),
		array(
			'icon'  => 'fa fa-envelope',
			'iconClass' => 'mo_ldap_overview_icon_explore',
			'title' => Text::_('COM_MINIORANGE_OVERVIEW_RESOURCE_EXPLORE_TITLE'),
			'desc'  => Text::_('COM_MINIORANGE_OVERVIEW_RESOURCE_EXPLORE_DESC'),
			'btn'   => Text::_('COM_MINIORANGE_OVERVIEW_EXPLORE'),
			'url'   => $exploreUrl,
			'external' => true,
		),
	);

	$statusClass = $extensionEnabled ? 'mo_ldap_overview_status_ok' : 'mo_ldap_overview_status_warn';
	$statusIcon = $extensionEnabled ? 'fa fa-check-circle' : 'fa fa-exclamation-circle';
	$statusLabel = $extensionEnabled
		? Text::_('COM_MINIORANGE_OVERVIEW_EXTENSION_ENABLED')
		: Text::_('COM_MINIORANGE_OVERVIEW_EXTENSION_DISABLED');

	$stepsHtml = '';

	foreach ($steps as $index => $step)
	{
		$stepsHtml .= '
							<div class="mo_ldap_overview_step">
								<span class="mo_ldap_overview_step_num">' . (int) ($index + 1) . '</span>
								<div class="mo_ldap_overview_step_content">
									<strong class="mo_ldap_overview_step_title">' . $step['title'] . '</strong>
									<p class="mo_ldap_overview_step_desc mo_boot_mb-0">' . $step['desc'] . '</p>
								</div>
							</div>';
	}

	$providersHtml = '';

	foreach ($providers as $provider)
	{
		$iconEsc = htmlspecialchars($provider['icon'], ENT_QUOTES, 'UTF-8');
		$providersHtml .= '
								<div class="mo_ldap_overview_provider">
									<i class="' . $iconEsc . '"></i>
									<span>' . $provider['label'] . '</span>
								</div>';
	}

	$resourcesHtml = '';

	foreach ($resources as $resource)
	{
		$urlEsc = htmlspecialchars($resource['url'], ENT_QUOTES, 'UTF-8');

		if ($resource['external'])
		{
			$actionHtml = '
											<a href="' . $resource['url'] . '" target="_blank" rel="noopener noreferrer" class="mo_boot_btn mo_boot_btn-outline-primary mo_boot_btn-sm">
												' . $resource['btn'] . '
											</a>';
		}
		else
		{
			$actionHtml = '
											<a href="' . $urlEsc . '" class="mo_boot_btn mo_boot_btn-outline-primary mo_boot_btn-sm">
												' . $resource['btn'] . '
											</a>';
		}

		$resourcesHtml .= '
								<div class="mo_ldap_overview_resource">
									<div class="mo_ldap_overview_resource_icon ' . $resource['iconClass'] . '">
										<i class="' . $resource['icon'] . '"></i>
									</div>
									<div class="mo_ldap_overview_resource_body">
										<strong>' . $resource['title'] . '</strong>
										<p class="mo_boot_mb-0">' . $resource['desc'] . '</p>
									</div>
									<div class="mo_ldap_overview_resource_action">' . $actionHtml . '
									</div>
								</div>';
	}
	?>
	<div class="mo_boot_container-fluid mo_main_ldap_section mo_ldap_overview">
		<div class="mo_boot_col-sm-12">
			<div class="mo_ldap_overview_header">
				<div class="mo_boot_d-flex mo_boot_align-items-start mo_boot_gap-2">
					<div class="mo_ldap_overview_logo_box">
						<img src="<?php echo $logoUrl; ?>" alt="" class="mo_ldap_overview_logo" onerror="this.parentElement.classList.add('mo_ldap_overview_logo_fallback')">
					</div>
					<div class="mo_boot_flex-grow-1 mo_ldap_overview_header_main">
						<div class="mo_ldap_overview_title_row">
							<h3 class="mo_ldap_sub_heading mo_ldap_overview_title mo_boot_mb-0"><?php echo Text::_('COM_MINIORANGE_OVERVIEW_PAGE_TITLE'); ?></h3>
							<div class="mo_ldap_overview_status <?php echo $statusClass; ?>">
								<span class="mo_ldap_overview_status_ver">v<?php echo $versionEsc; ?></span>
							</div>
						</div>
						<p class="mo_ldap_overview_lead mo_boot_mb-0"><?php echo Text::_('COM_MINIORANGE_OVERVIEW_PAGE_DESC'); ?></p>
					</div>
				</div>
			</div>

			<div class="mo_ldap_overview_columns">
				<div class="mo_ldap_mini_section mo_ldap_overview_steps_panel">
					<h4 class="mo_ldap_overview_panel_title"><?php echo Text::_('COM_MINIORANGE_OVERVIEW_GET_STARTED_TITLE'); ?></h4>
					<p class="mo_ldap_overview_panel_sub"><?php echo Text::_('COM_MINIORANGE_OVERVIEW_GET_STARTED_SUBTITLE'); ?></p>

					<div class="mo_ldap_overview_steps">
						<?php echo $stepsHtml; ?>
					</div>

					<div class="mo_ldap_overview_steps_footer">
						<a href="<?php echo $ldapConfigUrl; ?>" class="mo_boot_btn mo_boot_btn-primary mo_ldap_overview_cta mo_ldap_tab_nav_link" data-tab-key="ldapconfiguration">
							<?php echo Text::_('COM_MINIORANGE_OVERVIEW_START_SETUP'); ?>
							<i class="fa fa-arrow-right mo_boot_ms-2"></i>
						</a>
						<p class="mo_ldap_overview_plans mo_boot_mb-0">
							<?php
							echo sprintf(
								Text::_('COM_MINIORANGE_OVERVIEW_PLANS_NOTE'),
								'<a href="' . htmlspecialchars($licensingUrl, ENT_QUOTES, 'UTF-8') . '" class="mo_ldap_tab_nav_link" data-tab-key="licensing">'
									. Text::_('COM_MINIORANGE_OVERVIEW_PLANS_LINK') . '</a>'
							);
							?>
						</p>
					</div>
				</div>

				<div class="mo_ldap_overview_right_stack">
					<div class="mo_ldap_mini_section mo_ldap_overview_servers_panel">
						<h4 class="mo_ldap_overview_panel_title"><?php echo Text::_('COM_MINIORANGE_OVERVIEW_SUPPORTED_SERVERS_TITLE'); ?></h4>
						<div class="mo_ldap_overview_providers">
							<?php echo $providersHtml; ?>
						</div>
					</div>

					<div class="mo_ldap_mini_section mo_ldap_overview_resources_panel">
						<h4 class="mo_ldap_overview_panel_title"><?php echo Text::_('COM_MINIORANGE_OVERVIEW_RESOURCES_TITLE'); ?></h4>
						<div class="mo_ldap_overview_resources_list">
							<?php echo $resourcesHtml; ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
}
