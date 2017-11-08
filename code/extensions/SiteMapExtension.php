<?php

class SiteMapExtension extends DataExtension
{
    private static $db = [
        'ChangeFreq' => "Enum(array('daily', 'weekly'), 'daily')",
        'Priority'   => "Enum('0.0, 0.1, 0.2, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8, 0.9, 1.0', 0.0)",
    ];

    public function updateSettingsFields(FieldList $fields)
    {
        $changeFreq = Singleton('Page')->dbObject('ChangeFreq')->enumValues();
        $fields->addFieldToTab(
            'Root.Settings',
            DropDownField::create('ChangeFreq', 'Change Frequency', $changeFreq)
        );
        $priority = Singleton('Page')->dbObject('Priority')->enumValues();
        $fields->addFieldToTab(
            'Root.Settings',
            DropDownField::create('Priority', 'Priority', $priority)
        );
    }
}
