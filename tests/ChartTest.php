<?php

namespace flashbackzoo\SilverStripeCharts\Tests;

use flashbackzoo\SilverStripeCharts\Chart;
use SilverStripe\Dev\SapphireTest;

/**
 * @package SilverStripeCharts
 *
 * @subpackage tests
 */
class ChartTest extends SapphireTest
{
    protected static $fixture_file = 'ChartTest.yml';

    public function testGetChartData()
    {
        $chart = $this->objFromFixture(Chart::class, 'Bar');

        $this->assertIsString($chart->getChartData());
    }
}
