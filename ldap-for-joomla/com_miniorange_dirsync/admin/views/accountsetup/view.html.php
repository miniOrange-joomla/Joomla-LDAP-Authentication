<?php
defined('_JEXEC') or die;
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
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Version;
use Joomla\CMS\Toolbar\Toolbar;

/**
* @package     Joomla.Component
* @subpackage  com_miniorange_dirsync
*
* @author      miniOrange Security Software Pvt. Ltd.
* @copyright   Copyright (C) 2015 miniOrange (https://www.miniorange.com)
* @license     GNU General Public License version 3; see LICENSE.txt
* @contact     info@xecurify.com
*/
class miniorangedirsyncViewAccountSetup extends HtmlView
{
	function display($tpl=null)
	{
		// Get data from the model
		$this->lists=$this->get('List');
		//$this->pagination=$this->get('Pagination');			

		// Check for errors.
		if (count($errors=$this->get('Errors')))
		{
			Factory::getApplication()->enqueueMessage(500, implode('<br />', $errors));

			return false;
		}
		$this->setLayout('accountsetup');
		// Set the toolbar
		$this->addToolBar();

		// Display the template
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 *
	 */
	protected function addToolBar()
	{
		
		ToolBarHelper::title(Text::_('COM_MINIORANGE_DIRSYNC_PLUGIN_TITLE') , 'mo_ldap_logo mo_ldap_logo');
		
		// Check Joomla version - only add toolbar buttons for Joomla 4+
		$version = new Version();
		if (version_compare($version->getShortVersion(), '4.0', '>=')) {
			try {
                $toolbar = Toolbar::getInstance('toolbar');
                
                // Create a container div for top-right positioning
				$buttonsContainerHtml = '<div style="position: absolute; top: 4px; right: 20px; z-index: 1000; display: flex; gap: 8px;">
					<a href="index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=mo_ldap_trial_demo&query_type=trial" 
					   class="mo_boot_btn mo_boot_px-4 mo_boot_py-1 mo_boot_btn-primary"
					   title="Need Premium features? Contact us">
					   ' . Text::_('COM_MINIORANGE_LDAP_FREE_TRIAL') . '
					</a>
					<a href="index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=mo_ldap_trial_demo&query_type=configuration" 
					   class="mo_boot_btn mo_boot_px-4 mo_boot_py-1 mo_boot_btn-warning"
					   title="Need help? Contact us">
					   ' . Text::_('COM_MINIORANGE_SUPPORT') . '
					</a>
				</div>';
				
				// Add the buttons container as a custom toolbar element positioned to top-right
				$toolbar->customButton('custom-buttons')->html($buttonsContainerHtml);
				
				// Add CSS to ensure proper positioning within the toolbar
				$document = Factory::getApplication()->getDocument();
				$document->addStyleDeclaration('
					.subhead { position: relative; }
					.toolbar .custom-buttons { 
						position: absolute !important; 
						top: 10px !important;
						right: 20px !important; 
						z-index: 1000 !important;
					}
					.toolbar-list { position: relative; }
				');
				
			} catch (Exception $e) {
				// If toolbar fails, buttons will still be available in the template
			}
		}
	}


}