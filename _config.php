<?php

use SilverStripe\View\Parsers\ShortcodeParser;

define('CHARTS_DIR', basename(__DIR__));

ShortcodeParser::get('default')->register('chart', ['flashbackzoo\SilverStripeCharts\ChartExtension', 'chartShortcodeHandler']);
