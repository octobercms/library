<?php namespace October\Rain\Element\Dash;

use October\Rain\Element\ElementBase;

/**
 * ReportDefinition
 *
 * @method ReportDefinition reportName(string $name) reportName for this report
 * @method ReportDefinition label(string $label) label for this report
 * @method ReportDefinition type(string $type) type for display mode, eg: indicator, static
 * @method ReportDefinition row(int $row) row number where the report should be placed
 * @method ReportDefinition width(int $width) width to display the report, between 1 - 20 range
 * @method ReportDefinition icon(string $icon) icon specifies an icon name for this report
 * @method ReportDefinition dimension(string $dimension) dimension name
 * @method ReportDefinition dataSource(string $dataSource) dataSource class name for obtaining report data
 * @method ReportDefinition widget(string $widget) widget code or class name for the report widget
 * @method ReportDefinition metrics(array $metrics) metrics to display with the report
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
            ->metrics([])
            ->width(20)
            ->row(1)
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
