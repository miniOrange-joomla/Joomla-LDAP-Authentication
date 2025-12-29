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
use Joomla\CMS\HTML\HTMLHelper;

/**
 * Form Field Configuration Class
 * Encapsulates all form field properties for better maintainability
 */
class FormFieldConfig
{
    public $id;
    public $label;
    public $type = 'text';
    public $value = '';
    public $placeholder = '';
    public $disabled = false;
    public $required = false;
    public $helpText = '';
    public $helpTitle = '';
    public $isPremium = false;
    public $options = [];
    public $selectedValue = '';
    public $checked = false;
    public $layout = ['label' => 3, 'field' => 8, 'right' => 1];
    public $attributes = [];
    public $onclick = '';
    public $title = '';
    public $icon = '';
    public $btnClass = 'primary';
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
        $this->layout = ['label' => $labelCols, 'field' => $fieldCols, 'right' => $rightCols];
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

        switch ($config->type) {
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
        $html .= '<select name="' . $config->id . '" id="' . $config->id . '" class="form-select" ' . $disabledAttr . ' ' . $requiredAttr . '>';
        
        if (!empty($config->placeholder)) {
            $html .= '<option value="" disabled>' . Text::_($config->placeholder) . '</option>';
        }
        
        foreach ($config->options as $value => $text) {
            $selected = ($config->selectedValue == $value) ? 'selected' : '';
            $html .= '<option value="' . htmlspecialchars($value) . '" ' . $selected . '>';
            $html .= Text::_($text);
            $html .= '</option>';
        }
        
        $html .= '</select>';
        
        if ($config->helpText) {
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
        
        if ($config->type === 'password') {
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
        } else {
            $html .= '<input class="form-control mo_ldap_input_field" ';
            $html .= 'id="' . $config->id . '" name="' . $config->id . '" type="' . $config->type . '" ';
            $html .= 'placeholder="' . htmlspecialchars($config->placeholder) . '" ';
            $html .= 'value="' . htmlspecialchars($config->value) . '" ';
            $html .= $disabledAttr . ' ' . $requiredAttr . '>';
        }
        
        if ($config->helpText) {
            $html .= '<small class="form-text text-muted">' . $config->helpText . '</small>';
        }
        
        $html .= '</div>';
        $html .= self::buildRightColumn($config);
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Render a toggle/switch field
     */
    public static function renderToggle(FormFieldConfig $config): string
    {
        $checkedAttr = $config->checked ? 'checked' : '';
        $disabledAttr = $config->disabled ? 'disabled' : '';
        $disabledClass = $config->disabled ? 'mo_ldap_disabled_input' : '';
        $requiredAttr = $config->required ? 'required' : '';
        
        $html = self::buildRowStart($config);
        $html .= self::buildLabel($config, false);
        $html .= '<div class="mo_boot_col-12 mo_boot_col-md-' . $config->layout['field'] . '">';
        $html .= '<div class="form-check form-switch">';
        $html .= '<input type="checkbox" class="form-check-input ' . $disabledClass . '" ';
        $html .= 'id="' . $config->id . '" name="' . $config->id . '" value="1" ';
        $html .= $checkedAttr . ' ' . $disabledAttr . ' ' . $requiredAttr . '>';
        
        if ($config->helpText) {
            $html .= '<label class="form-label" for="' . $config->id . '">' . $config->helpText . '</label>';
        }
        
        $html .= '</div>';
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
        
        if ($config->helpText) {
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
        
        if (is_array($config->attributes)) {
            foreach ($config->attributes as $key => $value) {
                $attrString .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
            }
        }
        
        if ($config->onclick) {
            $attrString .= ' onclick="' . htmlspecialchars($config->onclick) . '"';
        }
        
        if ($config->title) {
            $attrString .= ' title="' . htmlspecialchars($config->title) . '"';
        }
        
        $rowClasses = 'mo_boot_row mo_boot_mb-3';
        if (is_array($config->attributes) && isset($config->attributes['mo_boot_col-sm-12'])) {
            $rowClasses = $config->attributes['mo_boot_col-sm-12'];
        }
        
        $html = '<div class="' . $rowClasses . '">';
        
        if ($config->layout['label'] > 0) {
            $html .= '<div class="mo_boot_col-12 mo_boot_col-md-' . $config->layout['label'] . ' mo_boot_mb-2 mo_boot_mb-md-0">';
            $html .= '<!-- Empty div for alignment -->';
            $html .= '</div>';
        }
        
        $html .= '<div class="mo_boot_col-12 mo_boot_col-md-' . $config->layout['field'] . ' mo_boot_d-flex mo_boot_justify-content-center">';
        
        $disabledClass = $config->disabled ? 'mo_ldap_disabled_input' : '';
        
        $html .= '<button type="' . $config->buttonType . '" ';
        $html .= 'id="' . $config->id . '" ';
        $html .= 'class="mo_boot_btn mo_boot_btn-' . $config->btnClass . ' mo_boot_px-3 mo_boot_py-2 ' . $disabledClass . '"' . $attrString;
        if ($config->disabled) {
            $html .= ' disabled';
        }
        $html .= '>';
        
        if ($config->icon) {
            if (preg_match('/\.svg$/', $config->icon)) {
                $html .= '<img src="' . htmlspecialchars($config->icon) . '" alt="" class="mo_icon">';
            } else {
                $html .= '<i class="' . htmlspecialchars($config->icon) . ' mo_boot_me-2"></i> ';
            }
        }
        
        $html .= htmlspecialchars($config->label);
        $html .= '</button>';
        $html .= '</div>';
        
        if ($config->layout['right'] > 0) {
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
        $html .= $config->label;
        if ($includeColon) {
            $html .= ': ';
        }
        
        if ($config->required) {
            $html .= '<span class="mo_ldap_highlight">*</span>';
        }
        
        if ($config->helpTitle) {
            $html .= ' <i class="fa fa-info-circle mo_boot_ms-1" title="' . htmlspecialchars($config->helpTitle) . '"></i>';
        }
        
        if ($config->isPremium) {
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
        if ($config->layout['right'] > 0) {
            return '<div class="mo_boot_col-12 mo_boot_col-md-' . $config->layout['right'] . '"><!-- Empty div for alignment --></div>';
        }
        return '';
    }

    /**
     * Validate configuration
     */
    private static function validateConfig(FormFieldConfig $config): void
    {
        if (empty($config->id)) {
            throw new InvalidArgumentException('Field ID is required');
        }
        
        if (empty($config->label)) {
            throw new InvalidArgumentException('Field label is required');
        }
    }

    // Legacy methods for backward compatibility
    public static function renderDropdownRow($id, $label, $options = [], $disabled = false, $selectedValue = '', $placeholder = 'COM_MINIORANGE_SELECT_ATTRIBUTE', $required = false, $helpText = '', $helpTitle = '', $labelCols = 3, $dropdownCols = 8, $rightCols = 1)
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

    public static function renderButtonRow(string $id, string $text, string $type = 'button', string $btnClass = 'primary', int $labelCols = 3, int $buttonCols = 8, int $rightCols = 1, array $attributes = [], string $onclick = '', string $icon = '', bool $disabled = false): string
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
        $highlightClass = $highlight ? 'mo_boot_border-warning': '';
        
        $html = '<div class="mo_boot_col-sm-12 mo_boot_col-md-6 mo_boot_col-lg-3 mo_boot_mb-4">';
        $html .= '<div class="mo_ldap_mini_section mo_boot_shadow-sm ' . $highlightClass . '">';
        $html .= '<div class="mo_boot_text-left">';
        $html .= '<h5 class="mo_boot_fw-medium mo_boot_mb-3">' . htmlspecialchars($title) . '</h5>';
        $html .= '<h1 class="mo_boot_mb-3">' . htmlspecialchars($price) . '</h1>';
        
        if ($buttonType === 'link' && !empty($buttonUrl)) {
            $html .= '<a href="' . htmlspecialchars($buttonUrl) . '" target="_blank" class="mo_boot_btn mo_boot_btn-warning mo_boot_w-150 mo_boot_mb-3 mo_boot_text-decoration-none">' . htmlspecialchars($buttonText) . '</a>';
        } else {
            $html .= '<button class="mo_boot_btn mo_boot_btn-warning mo_boot_w-100 mo_boot_mb-3 ">' . htmlspecialchars($buttonText) . '</button>';
        }
        
        $html .= self::renderFeatureBlock($id . '-included', 'Included Features', $includedFeatures, true);
        $html .= self::renderFeatureBlock($id . '-not-included', 'Not-Included Features', $notIncludedFeatures, false);
        
        $html .= '</div></div></div>';
        return $html;
    }
    
    private static function renderFeatureBlock($collapseId, $label, $features, $included = true)
    {
        $icon = $included ? 'fa fa-check mo_boot_text-success': 'fa fa-times mo_boot_text-danger';
        $btnClass = $included ? 'mo_boot_btn-outline-success' : 'mo_boot_btn-outline-danger';
        
        $html = '<div class="mo_boot_mb-3">';
        $html .= '<button class="mo_boot_btn ' . $btnClass . ' mo_boot_w-100 mo_boot_text-start" type="button" data-bs-toggle="collapse" data-bs-target="#' . $collapseId . '">';
        $html .= '<i class="' . $icon . ' mo_boot_me-2"></i>' . htmlspecialchars($label) . ' <i class="fa fa-chevron-down mo_boot_float-end"></i>';
        $html .= '</button>';
        $html .= '<div class="collapse mo_boot_mt-2" id="' . $collapseId . '">';
        $html .= '<div class="">';
        
        $html .= '<ul class="mo_boot_mb-0" style="list-style: none;">';
        foreach ($features as $feature) {
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
        
        if (!empty($buttonUrl)) {
            $html .= '<a href="' . htmlspecialchars($buttonUrl) . '" target="_blank" class="mo_boot_btn mo_boot_btn-primary mo_boot_w-100 mo_boot_d-flex mo_boot_align-items-center mo_boot_justify-content-center mo_boot_text-decoration-none">';
            $html .= '<i class="' . htmlspecialchars($icon) . ' mo_boot_me-2"></i>' . htmlspecialchars($buttonText);
            $html .= '</a>';
        } else {
            $html .= '<button class="mo_boot_btn mo_boot_btn-primary mo_boot_w-100 mo_boot_d-flex mo_boot_align-items-center mo_boot_justify-content-center">';
            $html .= '<i class="' . htmlspecialchars($icon) . ' mo_boot_me-2"></i>' . htmlspecialchars($buttonText);
            $html .= '</button>';
        }
        
        $html .= '</div></div>';
        return $html;
    }
}
