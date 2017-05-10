<?php
class Sitemap
{
    const DEFAULT_PRIORITY = '0.5';
    const DEFAULT_FREQUENCY = 'weekly';

    public static function getPriorityList()
    {
        return array('0.0', '0.1', '0.2', '0.3', '0.4', '0.5', '0.6', '0.7', '0.8', '0.9', '1.0');
    }

    public static function getFrequenceList()
    {
        return array('always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never');
    }
}
