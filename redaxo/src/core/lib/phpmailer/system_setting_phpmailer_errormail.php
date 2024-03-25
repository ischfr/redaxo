<?php

use Redaxo\Core\Form\Field\SelectField;
use Redaxo\Core\Translation\I18n;

/**
 * Class for errormail setting to show in settings.
 *
 * @internal
 */
class rex_system_setting_phpmailer_errormail extends rex_system_setting
{
    public function getKey()
    {
        return 'phpmailer_errormail';
    }

    public function getField()
    {
        $field = new SelectField();
        $field->setAttribute('class', 'form-control selectpicker');
        $field->setLabel(I18n::msg('system_setting_errormail'));
        $select = $field->getSelect();
        $select->addOption(I18n::msg('phpmailer_errormail_disabled'), 0);
        $select->addOption(I18n::msg('phpmailer_errormail_15min'), 900);
        $select->addOption(I18n::msg('phpmailer_errormail_30min'), 1800);
        $select->addOption(I18n::msg('phpmailer_errormail_60min'), 3600);
        $select->setSelected(rex_config::get('phpmailer', 'errormail', 1));
        return $field;
    }

    public function setValue($value)
    {
        $value = (int) $value;
        rex_config::set('phpmailer', 'errormail', $value);
        return true;
    }
}