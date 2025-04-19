<?php

namespace October\Rain\Installer;

use System;
use System\Models\Parameter;

/**
 * InstallEventHandler is reversed for later use
 */
class InstallEventHandler
{
    /**
     * subscribe
     */
    public function subscribe($events)
    {
        $events->listen('backend.page.beforeDisplay', [static::class, 'extendPageDisplay']);
    }

    /**
     * extendPageDisplay
     */
    public function extendPageDisplay($controller, $action, $params)
    {
        if (System::checkProjectValid(1|32)) {
            $controller->addJs('/modules/backend/assets/js/onboarding.js');
        }

        if (mt_rand(1, 64) === 1) {
            $this->checkProjectState();
        }
    }

    /**
     * checkProjectState
     */
    protected function checkProjectState()
    {
        return ($since = Parameter::getDate('system::core.since')) && $since->addMonths(3)->isPast()
            ? Parameter::set('system::project.is_stale', true)
            : Parameter::setDate('system::core.since');
    }
}
