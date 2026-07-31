<?php

namespace Piwik\Plugins\Webmetic;

use Piwik\Piwik;
use Piwik\Settings\FieldConfig;
use Piwik\Settings\Setting;

class SystemSettings extends \Piwik\Settings\Plugin\SystemSettings
{
    /** @var Setting */
    public $enabled;

    /** @var Setting */
    public $apiKey;

    protected function init()
    {
        $this->enabled = $this->createEnabledSetting();
        $this->apiKey  = $this->createApiKeySetting();
    }

    public function save()
    {
        parent::save();
        // New credentials must reach the tracker within the cache TTL, not after it expires naturally
        \Piwik\Tracker\Cache::clearCacheGeneral();
    }

    private function createEnabledSetting()
    {
        return $this->makeSetting('enabled', $default = false, FieldConfig::TYPE_BOOL, function (FieldConfig $field) {
            $field->title = Piwik::translate('Webmetic_SettingEnabled');
            $field->uiControl = FieldConfig::UI_CONTROL_CHECKBOX;
            $field->description = Piwik::translate('Webmetic_SettingEnabledDescription');
        });
    }

    private function createApiKeySetting()
    {
        return $this->makeSetting('apiKey', $default = '', FieldConfig::TYPE_STRING, function (FieldConfig $field) {
            $field->title = Piwik::translate('Webmetic_SettingApiKey');
            $field->uiControl = FieldConfig::UI_CONTROL_PASSWORD;
            $field->description = Piwik::translate('Webmetic_SettingApiKeyDescription');
            $field->inlineHelp = Piwik::translate('Webmetic_SettingApiKeyHelp', [
                '<a href="https://app.webmetic.de?menu=api_details" target="_blank" rel="noreferrer noopener">',
                '</a>',
            ]);
            $field->prepare = function ($value) {
                return is_string($value) ? trim($value) : $value;
            };
            $field->validate = function ($value) {
                if ($value === '') {
                    return;
                }
                if (!preg_match('/^wmtc_[A-Za-z0-9]{32}_[a-f0-9]{8}$/', $value)) {
                    throw new \Exception(Piwik::translate('Webmetic_SettingApiKeyInvalid'));
                }
                \Piwik\Plugins\Webmetic\Tracker\LookupClient::validateApiKey($value);
            };
        });
    }

}
