<?php namespace October\Rain\Element\Dashboard;

use October\Rain\Element\ElementBase;

/**
 * ReportDefinition
 *
 * @method ReportDefinition reportName(string $name) reportName for this report
 * @method ReportDefinition type(string $type) type for display mode, eg: indicator, static
 * @method ReportDefinition dateStart(string $dateStart) dateStart
 * @method ReportDefinition dateEnd(string $dateEnd) dateEnd
 * @method ReportDefinition compareWith(string $compareWith) compareWith period, eg: prev-period, prev-year
 * @method ReportDefinition resetCache(bool $resetCache) resetCache when rendering
 * @method ReportDefinition aggregationInterval(string $aggregationInterval) aggregationInterval for display, eg: day, week, month
 *
 * @package october\element
 * @author Alexey Bobkov, Samuel Georges
 */
class ReportDefinition extends ElementBase
{
    /**
     * initDefaultValues for this report
     */
    protected function initDefaultValues()
    {
        $this
            ->displayAs('static')
        ;
    }

    /**
     * displayAs type for this field
     */
    public function displayAs(string $type): ReportDefinition
    {
        $this->type($type);

        return $this;
    }
}
