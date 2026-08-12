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
 **/

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

/**
 * Form Field Configuration Class
 * Encapsulates all form field properties for better maintainability
 */
class FormFieldConfig
{
	/**
	 * The field identifier.
	 *
	 * @var string
	 */
	public $id;

	/**
	 * The field label.
	 *
	 * @var string
	 */
	public $label;

	/**
	 * The field type.
	 *
	 * @var string
	 */
	public $type = 'text';

	/**
	 * The field value.
	 *
	 * @var string
	 */
	public $value = '';

	/**
	 * The field placeholder text.
	 *
	 * @var string
	 */
	public $placeholder = '';

	/**
	 * Whether the field is disabled.
	 *
	 * @var boolean
	 */
	public $disabled = false;

	/**
	 * Whether the field is required.
	 *
	 * @var boolean
	 */
	public $required = false;

	/**
	 * The help text for the field.
	 *
	 * @var string
	 */
	public $helpText = '';

	/**
	 * The help title for the field.
	 *
	 * @var string
	 */
	public $helpTitle = '';

	/**
	 * Whether the field is a premium feature.
	 *
	 * @var boolean
	 */
	public $isPremium = false;

	/**
	 * The available options for select fields.
	 *
	 * @var array
	 */
	public $options = array();

	/**
	 * The currently selected value.
	 *
	 * @var string
	 */
	public $selectedValue = '';

	/**
	 * Whether the field is checked.
	 *
	 * @var boolean
	 */
	public $checked = false;

	/**
	 * The layout column configuration.
	 *
	 * @var array
	 */
	public $layout = array('label' => 3, 'field' => 8, 'right' => 1);

	/**
	 * Additional HTML attributes for the field.
	 *
	 * @var array
	 */
	public $attributes = array();

	/**
	 * The onclick handler.
	 *
	 * @var string
	 */
	public $onclick = '';

	/**
	 * The field title.
	 *
	 * @var string
	 */
	public $title = '';

	/**
	 * The icon for the field or button.
	 *
	 * @var string
	 */
	public $icon = '';

	/**
	 * The button CSS class.
	 *
	 * @var string
	 */
	public $btnClass = 'primary';

	/**
	 * The button type attribute.
	 *
	 * @var string
	 */
	public $buttonType = 'button';

	public function __construct($id = '', $label = '')
	{
		$this->id = $id;
		$this->label = $label;
	}

	public function setId($id)
	{
		$this->id = $id;

		return $this;
	}

	public function setLabel($label)
	{
		$this->label = $label;

		return $this;
	}

	public function setType($type)
	{
		$this->type = $type;

		return $this;
	}

	public function setValue($value)
	{
		$this->value = $value;

		return $this;
	}

	public function setPlaceholder($placeholder)
	{
		$this->placeholder = $placeholder;

		return $this;
	}

	public function setDisabled($disabled)
	{
		$this->disabled = $disabled;

		return $this;
	}

	public function setRequired($required)
	{
		$this->required = $required;

		return $this;
	}

	public function setHelpText($helpText)
	{
		$this->helpText = $helpText;

		return $this;
	}

	public function setHelpTitle($helpTitle)
	{
		$this->helpTitle = $helpTitle;

		return $this;
	}

	public function setIsPremium($isPremium)
	{
		$this->isPremium = $isPremium;

		return $this;
	}

	public function setOptions($options)
	{
		$this->options = $options;

		return $this;
	}

	public function setSelectedValue($selectedValue)
	{
		$this->selectedValue = $selectedValue;

		return $this;
	}

	public function setChecked($checked)
	{
		$this->checked = $checked;

		return $this;
	}

	public function setLayout($labelCols, $fieldCols, $rightCols = 1)
	{
		$this->layout = array('label' => $labelCols, 'field' => $fieldCols, 'right' => $rightCols);

		return $this;
	}

	public function setAttributes($attributes)
	{
		$this->attributes = $attributes;

		return $this;
	}

	public function setOnclick($onclick)
	{
		$this->onclick = $onclick;

		return $this;
	}

	public function setTitle($title)
	{
		$this->title = $title;

		return $this;
	}

	public function setIcon($icon)
	{
		$this->icon = $icon;

		return $this;
	}

	public function setBtnClass($btnClass)
	{
		$this->btnClass = $btnClass;

		return $this;
	}

	public function setButtonType($buttonType)
	{
		$this->buttonType = $buttonType;

		return $this;
	}
}

/**
 * Form Renderer Helper Class
 * Provides consistent form element rendering across the component
 * Optimized with configuration objects and better architecture
 */
class FormRenderer
{
	/**
	 * Render a form field based on configuration
	 *
	 * @param FormFieldConfig $config Field configuration
	 * @return string HTML output
	 */
	public static function renderField(FormFieldConfig $config): string
	{
		self::validateConfig($config);

		switch ($config->type)
		{
			case 'dropdown':
			case 'select':
				return self::renderDropdown($config);
			case 'checkbox':
				return self::renderCheckbox($config);
			case 'toggle':
			case 'switch':
				return self::renderToggle($config);
			case 'button':
			case 'submit':
				return self::renderButton($config);
			default:
				return self::renderInput($config);
		}
	}

	/**
	 * Render a dropdown/select field
	 */
	public static function renderDropdown(FormFieldConfig $config): string
	{
		$disabledAttr = $config->disabled ? 'disabled' : '';
		$disabledClass = $config->disabled ? 'mo_ldap_disabled_input' : '';
		$requiredAttr = $config->required ? 'required' : '';

		$html = self::buildRowStart($config);
		$html .= self::buildLabel($config);
		$html .= '<div class="mo_boot_col-12 mo_boot_col-md-' . $config->layout['field'] . '">';
		$html .= '<select name="' . $config->id . '" id="' . $config->id . '" class="form-select ' . $disabledClass . '" ' . $disabledAttr . ' ' . $requiredAttr . '>';

		if (!empty($config->placeholder))
		{
			$html .= '<option value="" disabled>' . Text::_($config->placeholder) . '</option>';
		}

		foreach ($config->options as $value => $text)
		{
			$selected = ($config->selectedValue == $value) ? 'selected' : '';
			$html .= '<option value="' . htmlspecialchars($value) . '" ' . $selected . '>';
			$html .= Text::_($text);
			$html .= '</option>';
		}

		$html .= '</select>';

		if ($config->helpText)
		{
			$html .= '<small class="form-text text-muted">' . $config->helpText . '</small>';
		}

		$html .= '</div>';
		$html .= self::buildRightColumn($config);
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render an input field
	 */
	public static function renderInput(FormFieldConfig $config): string
	{
		$disabledAttr = $config->disabled ? 'disabled' : '';
		$disabledClass = $config->disabled ? 'mo_ldap_disabled_input' : '';
		$requiredAttr = $config->required ? 'required' : '';

		$html = self::buildRowStart($config);
		$html .= self::buildLabel($config);
		$html .= '<div class="mo_boot_col-12 mo_boot_col-md-' . $config->layout['field'] . '">';

		if ($config->type === 'password')
		{
			$html .= '<div class="mo_boot_position-relative">';
			$html .= '<input class="form-control mo_password_input mo_ldap_input_field ' . $disabledClass . '" ';
			$html .= 'id="' . $config->id . '" name="' . $config->id . '" type="' . $config->type . '" ';
			$html .= 'placeholder="' . htmlspecialchars($config->placeholder) . '" ';
			$html .= 'value="' . htmlspecialchars($config->value) . '" ';
			$html .= $disabledAttr . ' ' . $requiredAttr . '>';
			$html .= '<button type="button" class="mo_boot_btn mo_boot_btn-outline-secondary mo_password_toggle_btn" onclick="togglePassword(\'' . $config->id . '\')">';
			$html .= '<i class="fa fa-eye-slash"></i>';
			$html .= '</button>';
			$html .= '</div>';
		}
		else
		{
			$html .= '<input class="form-control mo_ldap_input_field" ';
			$html .= 'id="' . $config->id . '" name="' . $config->id . '" type="' . $config->type . '" ';
			$html .= 'placeholder="' . htmlspecialchars($config->placeholder) . '" ';
			$html .= 'value="' . htmlspecialchars($config->value) . '" ';
			$html .= $disabledAttr . ' ' . $requiredAttr . '>';
		}

		if ($config->helpText)
		{
			$html .= '<small class="form-text text-muted">' . $config->helpText . '</small>';
		}

		$html .= '</div>';
		$html .= self::buildRightColumn($config);
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render Yes/No btn-group toggle (Joomla admin style).
	 */
	public static function renderSwitcher(array $displayData): string
	{
		$id = $displayData['id'];
		$name = $displayData['name'];
		$value = (string) $displayData['value'];
		$disabled = !empty($displayData['disabled']);
		$options = $displayData['options'];
		$btnClasses = array(
			'0' => 'mo_boot_btn mo_boot_btn-danger',
			'1' => 'mo_boot_btn mo_boot_btn-success',
		);

		$html = '<fieldset id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '" class="mo_boot_btn-group mo_boot_btn-group-toggle btn-group-yesno radio" role="group"';

		if ($disabled)
		{
			$html .= ' disabled="disabled"';
		}

		$html .= '>';

		foreach ($options as $option)
		{
			$optionValue = (string) $option->value;
			$optionId = $id . $optionValue;
			$checked = ($value === $optionValue) ? ' checked="checked"' : '';
			$disabledAttr = $disabled ? ' disabled="disabled"' : '';
			$labelClass = isset($btnClasses[$optionValue]) ? $btnClasses[$optionValue] : 'mo_boot_btn';

			$html .= '<input type="radio" id="' . htmlspecialchars($optionId, ENT_QUOTES, 'UTF-8') . '"';
			$html .= ' name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '"';
			$html .= ' value="' . htmlspecialchars($optionValue, ENT_QUOTES, 'UTF-8') . '"' . $checked . $disabledAttr . ' />';
			$html .= '<label for="' . htmlspecialchars($optionId, ENT_QUOTES, 'UTF-8') . '" class="' . $labelClass . '">';
			$html .= htmlspecialchars($option->text, ENT_QUOTES, 'UTF-8') . '</label>';
		}

		$html .= '</fieldset>';

		return $html;
	}

	/**
	 * Render a yes/no toggle.
	 */
	public static function renderToggle(FormFieldConfig $config): string
	{
		$value = $config->checked ? '1' : '0';
		$legendLabel = trim(strip_tags($config->label)) !== ''
			? trim(strip_tags($config->label))
			: $config->id;

		$displayData = array(
			'id'             => $config->id,
			'name'           => $config->id,
			'label'          => $legendLabel,
			'value'          => $value,
			'disabled'       => (bool) $config->disabled,
			'required'       => (bool) $config->required,
			'options'        => array(
				(object) array('value' => '0', 'text' => Text::_('JNO')),
				(object) array('value' => '1', 'text' => Text::_('JYES')),
			),
			'autocomplete'   => false,
			'autofocus'      => false,
			'class'          => '',
			'description'    => '',
			'group'          => '',
			'hidden'         => false,
			'hint'           => '',
			'labelclass'     => '',
			'multiple'       => false,
			'onchange'       => '',
			'onclick'        => '',
			'pattern'        => '',
			'readonly'       => false,
			'repeat'         => false,
			'size'           => '',
			'spellcheck'     => false,
			'validate'       => '',
			'dataAttribute'  => '',
			'dataAttributes' => array(),
		);

		$html = self::buildRowStart($config);
		$html .= self::buildLabel($config, false);
		$html .= '<div class="mo_boot_col-12 mo_boot_col-md-' . $config->layout['field'] . ($config->disabled ? ' mo_ldap_disabled_toggle_wrap' : '') . '">';
		$html .= self::renderSwitcher($displayData);

		if ($config->helpText)
		{
			$html .= '<div class="small text-muted mt-1">' . $config->helpText . '</div>';
		}

		$html .= '</div>';
		$html .= self::buildRightColumn($config);
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render a checkbox field
	 */
	public static function renderCheckbox(FormFieldConfig $config): string
	{
		$checkedAttr = $config->checked ? 'checked' : '';
		$disabledAttr = $config->disabled ? 'disabled' : '';
		$disabledClass = $config->disabled ? 'mo_ldap_disabled_input' : '';
		$requiredAttr = $config->required ? 'required' : '';

		$html = self::buildRowStart($config);
		$html .= self::buildLabel($config, false);
		$html .= '<div class="mo_boot_col-12 mo_boot_col-md-' . $config->layout['field'] . '">';
		$html .= '<div class="form-check">';
		$html .= '<input type="checkbox" class="form-check-input ' . $disabledClass . '" ';
		$html .= 'id="' . $config->id . '" name="' . $config->id . '" value="1" ';
		$html .= $checkedAttr . ' ' . $disabledAttr . ' ' . $requiredAttr . '>';

		if ($config->helpText)
		{
			$html .= '<label class="form-check-label" for="' . $config->id . '">' . $config->helpText . '</label>';
		}

		$html .= '</div>';
		$html .= '</div>';
		$html .= self::buildRightColumn($config);
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render a button
	 */
	public static function renderButton(FormFieldConfig $config): string
	{
		$attrString = '';

		if (is_array($config->attributes))
		{
			foreach ($config->attributes as $key => $value)
			{
				$attrString .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
			}
		}

		if ($config->onclick)
		{
			$attrString .= ' onclick="' . htmlspecialchars($config->onclick) . '"';
		}

		if ($config->title)
		{
			$attrString .= ' title="' . htmlspecialchars($config->title) . '"';
		}

		$rowClasses = 'mo_boot_row mo_boot_mb-3';

		if (is_array($config->attributes) && isset($config->attributes['mo_boot_col-sm-12']))
		{
			$rowClasses = $config->attributes['mo_boot_col-sm-12'];
		}

		$html = '<div class="' . $rowClasses . '">';

		if ($config->layout['label'] > 0)
		{
			$html .= '<div class="mo_boot_col-12 mo_boot_col-md-' . $config->layout['label'] . ' mo_boot_mb-2 mo_boot_mb-md-0">';
			$html .= '<!-- Empty div for alignment -->';
			$html .= '</div>';
		}

		$html .= '<div class="mo_boot_col-12 mo_boot_col-md-' . $config->layout['field'] . ' mo_boot_d-flex mo_boot_justify-content-center">';

		$disabledClass = $config->disabled ? 'mo_ldap_disabled_input' : '';

		$html .= '<button type="' . $config->buttonType . '" ';
		$html .= 'id="' . $config->id . '" ';
		$html .= 'class="mo_boot_btn mo_boot_btn-' . $config->btnClass . ' mo_boot_px-3 mo_boot_py-2 ' . $disabledClass . '"' . $attrString;

		if ($config->disabled)
		{
			$html .= ' disabled';
		}

		$html .= '>';

		if ($config->icon)
		{
			if (preg_match('/\.svg$/', $config->icon))
			{
				$html .= '<img src="' . htmlspecialchars($config->icon) . '" alt="" class="mo_icon">';
			}
			else
			{
				$html .= '<i class="' . htmlspecialchars($config->icon) . ' mo_boot_me-2"></i> ';
			}
		}

		$html .= htmlspecialchars($config->label);
		$html .= '</button>';
		$html .= '</div>';

		if ($config->layout['right'] > 0)
		{
			$html .= '<div class="mo_boot_col-12 mo_boot_col-md-' . $config->layout['right'] . '">';
			$html .= '<!-- Empty div for alignment -->';
			$html .= '</div>';
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Build row start HTML
	 */
	private static function buildRowStart(FormFieldConfig $config): string
	{
		return '<div class="mo_boot_row mo_boot_col-sm-12 mo_boot_mb-3">';
	}

	/**
	 * Build label HTML
	 */
	private static function buildLabel(FormFieldConfig $config, $includeColon = true): string
	{
		$html = '<div class="mo_boot_col-12 mo_boot_col-md-' . $config->layout['label'] . ' mo_boot_mb-2 mo_boot_mb-md-0">';
		$html .= '<label for="' . $config->id . '" class="form-label fw-medium">';
		$labelText = rtrim(trim(strip_tags($config->label)), ':');

		$html .= $labelText;

		if ($includeColon)
		{
			$html .= ': ';
		}

		if ($config->required)
		{
			$html .= '<span class="mo_ldap_highlight">*</span>';
		}

		if ($config->helpTitle)
		{
			$html .= ' <i class="icon-info-circle mo_boot_ms-1" title="' . htmlspecialchars($config->helpTitle) . '"></i>';
		}

		if ($config->isPremium)
		{
			$html .= '<sup>';
			$html .= '<img class="crown_img_small mo_boot_ml-2 mo_ldap_cursor-type" ';
			$html .= 'src="' . MoConstants::getImageUrl('crown.webp') . '" ';
			$html .= 'alt="Premium" ';
			$html .= 'title="' . htmlspecialchars(Text::_('COM_MINIORANGE_UPGRADE_TO_PREMIUM')) . '">';
			$html .= '</sup>';
		}

		$html .= '</label>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Build right column HTML
	 */
	private static function buildRightColumn(FormFieldConfig $config): string
	{
		if ($config->layout['right'] > 0)
		{
			return '<div class="mo_boot_col-12 mo_boot_col-md-' . $config->layout['right'] . '"><!-- Empty div for alignment --></div>';
		}

		return '';
	}

	/**
	 * Validate configuration
	 */
	private static function validateConfig(FormFieldConfig $config): void
	{
		if (empty($config->id))
		{
			throw new InvalidArgumentException('Field ID is required');
		}

		if (empty($config->label))
		{
			throw new InvalidArgumentException('Field label is required');
		}
	}

	// Legacy methods for backward compatibility
	public static function renderDropdownRow($id, $label, $options = array(), $disabled = false, $selectedValue = '', $placeholder = 'COM_MINIORANGE_SELECT_ATTRIBUTE', $required = false, $helpText = '', $helpTitle = '', $labelCols = 3, $dropdownCols = 8, $rightCols = 1)
	{
		$config = (new FormFieldConfig($id, $label))
			->setType('dropdown')
			->setOptions($options)
			->setDisabled($disabled)
			->setSelectedValue($selectedValue)
			->setPlaceholder($placeholder)
			->setRequired($required)
			->setHelpText($helpText)
			->setHelpTitle($helpTitle)
			->setLayout($labelCols, $dropdownCols, $rightCols);

		return self::renderDropdown($config);
	}

	public static function renderInputRow($id, $label, $type = 'text', $value = '', $placeholder = '', $disabled = false, $required = false, $helpText = '', $helpTitle = '', $isPremium = false, $labelCols = 4, $inputCols = 7, $rightCols = 1)
	{
		$config = (new FormFieldConfig($id, $label))
			->setType($type)
			->setValue($value)
			->setPlaceholder($placeholder)
			->setDisabled($disabled)
			->setRequired($required)
			->setHelpText($helpText)
			->setHelpTitle($helpTitle)
			->setIsPremium($isPremium)
			->setLayout($labelCols, $inputCols, $rightCols);

		return self::renderInput($config);
	}

	public static function renderToggleRow($id, $label, $checked = false, $disabled = false, $required = false, $helpTitle = '', $helpText = '', $isPremium = false, $labelCols = 3, $toggleCols = 8, $rightCols = 1)
	{
		$config = (new FormFieldConfig($id, $label))
			->setType('toggle')
			->setChecked($checked)
			->setDisabled($disabled)
			->setRequired($required)
			->setHelpTitle($helpTitle)
			->setHelpText($helpText)
			->setIsPremium($isPremium)
			->setLayout($labelCols, $toggleCols, $rightCols);

		return self::renderToggle($config);
	}

	public static function renderCheckboxRow($id, $label, $checked = false, $disabled = false, $required = false, $helpTitle = '', $helpText = '', $isPremium = false, $labelCols = 3, $checkboxCols = 8, $rightCols = 1)
	{
		$config = (new FormFieldConfig($id, $label))
			->setType('checkbox')
			->setChecked($checked)
			->setDisabled($disabled)
			->setRequired($required)
			->setHelpTitle($helpTitle)
			->setHelpText($helpText)
			->setIsPremium($isPremium)
			->setLayout($labelCols, $checkboxCols, $rightCols);

		return self::renderCheckbox($config);
	}

	public static function renderButtonRow(string $id, string $text, string $type = 'button', string $btnClass = 'primary', int $labelCols = 3, int $buttonCols = 8, int $rightCols = 1, array $attributes = array(), string $onclick = '', string $icon = '', bool $disabled = false): string
	{
		$config = (new FormFieldConfig($id, $text))
			->setType('button')
			->setButtonType($type)
			->setBtnClass($btnClass)
			->setLayout($labelCols, $buttonCols, $rightCols)
			->setAttributes($attributes)
			->setOnclick($onclick)
			->setIcon($icon)
			->setDisabled($disabled);

		return self::renderButton($config);
	}

	// Specialized rendering methods
	public static function renderPlan($id, $title, $price, $buttonText, $buttonType, $includedFeatures, $notIncludedFeatures, $highlight = false, $buttonUrl = null)
	{
		$highlightClass = $highlight ? 'mo_boot_border-warning' : '';

		$html = '<div class="mo_boot_col-sm-12 mo_boot_col-md-6 mo_boot_col-lg-3 mo_boot_px-2 mo_boot_mb-3">';
		$html .= '<div class="mo_ldap_mini_section mo_boot_shadow-sm ' . $highlightClass . '">';
		$html .= '<div class="mo_boot_text-left">';
		$html .= '<h5 class="mo_boot_fw-medium mo_boot_mb-3">' . htmlspecialchars($title) . '</h5>';
		$html .= '<h1 class="mo_boot_mb-3">' . htmlspecialchars($price) . '</h1>';

		if ($buttonType === 'link' && !empty($buttonUrl))
		{
			$html .= '<a href="' . htmlspecialchars($buttonUrl) . '" target="_blank" class="mo_boot_btn mo_boot_btn-warning mo_boot_w-100 mo_boot_mb-3 mo_boot_text-decoration-none">' . htmlspecialchars($buttonText) . '</a>';
		}
		else
		{
			$html .= '<button class="mo_boot_btn mo_boot_btn-warning mo_boot_w-100 mo_boot_mb-3">' . htmlspecialchars($buttonText) . '</button>';
		}

		$html .= self::renderFeatureBlock($id . '-included', 'Included Features', $includedFeatures, true);

		if ($notIncludedFeatures != array())
		{
			$html .= self::renderFeatureBlock($id . '-not-included', 'Not-Included Features', $notIncludedFeatures, false);
		}

		$html .= '</div></div></div>';

		return $html;
	}

	private static function renderFeatureBlock($collapseId, $label, $features, $included = true)
	{
		$icon = $included ? 'fa fa-check mo_boot_text-success' : 'fa fa-times mo_boot_text-danger';
		$btnClass = $included ? 'mo_boot_btn-success' : 'mo_boot_btn-danger';

		$html = '<div class="mo_boot_mb-3">';
		$html .= "<button class=\"mo_boot_btn {$btnClass} mo_boot_w-100 mo_boot_d-flex mo_boot_justify-content-between mo_boot_align-items-center\" type=\"button\" data-bs-toggle=\"collapse\" data-bs-target=\"#{$collapseId}\">";
		$html .= "<span><i class=\"{$icon} mo_boot_me-2\"></i>" . htmlspecialchars($label) . '</span>';
		$html .= '<i class="fa fa-chevron-down mo_ldap_feature_chevron"></i>';
		$html .= '</button>';
		$html .= '<div class="collapse' . ($included ? ' show' : '') . ' mo_boot_mt-2" id="' . $collapseId . '">';
		$html .= '<div class="">';

		$html .= '<ul class="mo_boot_mb-0" style="list-style: none;">';

		foreach ($features as $feature)
		{
			$html .= '<li class="mo_boot_d-flex mo_boot_align-items-start mo_boot_mb-2" style="text-align: left; justify-content: flex-start;"><i class="' . $icon . ' mo_boot_me-2 mo_boot_mt-1" style="flex-shrink: 0;"></i><span style="text-align: left;">' . htmlspecialchars($feature) . '</span></li>';
		}

		$html .= '</ul>';

		$html .= '</div></div></div>';

		return $html;
	}

	public static function renderAddonBlock(string $title, string $description, string $buttonText = 'Interested', string $icon = 'fa fa-thumbs-up', string $buttonUrl = null): string
	{
		$html = '<div class="mo_boot_col-sm-6 mo_boot_col-lg-6 mo_boot_mb-4">';
		$html .= '<div class="mo_boot_h-50 mo_ldap_mini_section">';
		$html .= '<h6 class="mo_boot_fw-medium mo_boot_mb-3">' . htmlspecialchars($title) . '</h6>';
		$html .= '<p class="mo_boot_text-muted mo_boot_mb-4" style="min-height: 60px;">' . htmlspecialchars($description) . '</p>';

		if (!empty($buttonUrl))
		{
			$html .= '<a href="' . htmlspecialchars($buttonUrl) . '" target="_blank" class="mo_boot_btn mo_boot_btn-primary mo_boot_w-100 mo_boot_d-flex mo_boot_align-items-center mo_boot_justify-content-center mo_boot_text-decoration-none">';
			$html .= '<i class="' . htmlspecialchars($icon) . ' mo_boot_me-2"></i>' . htmlspecialchars($buttonText);
			$html .= '</a>';
		}
		else
		{
			$html .= '<button class="mo_boot_btn mo_boot_btn-primary mo_boot_w-100 mo_boot_d-flex mo_boot_align-items-center mo_boot_justify-content-center">';
			$html .= '<i class="' . htmlspecialchars($icon) . ' mo_boot_me-2"></i>' . htmlspecialchars($buttonText);
			$html .= '</button>';
		}

		$html .= '</div></div>';

		return $html;
	}
}
