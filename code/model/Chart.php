<?php

namespace flashbackzoo\SilverStripeCharts;

use Page;
use SilverStripe\Core\Convert;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldFilterHeader;
use SilverStripe\Forms\GridField\GridFieldSortableHeader;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\ORM\DataObject;
use UndefinedOffset\SortableGridField\Forms\GridFieldSortableRows;

/**
 * @package SilverStripeCharts
 */
class Chart extends DataObject
{
    private static $description = 'Enter your chart data';

    private static $db = [
        'SortOrder'=>'Int',
        'Title' => 'Varchar(255)',
        'ChartType' => 'Varchar',
    ];

    private static $has_one = [
        'Page' => Page::class,
    ];

    private static $has_many = [
        'Datasets' => ChartDataset::class,
    ];

    /**
     * The available chart types.
     *
     * @var array
     */
    private static $chartTypes = [
        'bar' => 'Bar Chart',
        'doughnut' => 'Doughnut Chart',
        'pie' => 'Pie Chart',
    ];

    private static $default_sort = 'SortOrder';

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName('SortOrder');
        $fields->removeByName('PageID');
        $fields->removeByName('Datasets');

        $chartTypeDropdown = DropdownField::create(
            'ChartType',
            'Type',
            self::$chartTypes
        )
        ->setEmptyString('Select...');

        $fields->addFieldsToTab(
            'Root.Main',
            [
                $chartTypeDropdown,
            ]
        );

        if ($this->ID) {
            $fields->addFieldToTab(
                'Root.Main',
                ReadonlyField::create(
                    'Shortcode',
                    'Shortcode',
                    "[chart,id='{$this->getField('ID')}']"
                ),
                'Title'
            );

            $config = GridFieldConfig_RecordEditor::create();
            $config->removeComponentsByType(GridFieldFilterHeader::class);
            $config->removeComponentsByType(GridFieldSortableHeader::class);
            $config->removeComponentsByType(GridFieldDeleteAction::class);
            $config->addComponent(new GridFieldSortableRows('SortOrder'));
            $config
                ->getComponentByType(GridFieldAddNewButton::class)
                ->setButtonName('Add Dataset');

            $fields->addFieldToTab(
                'Root.Main',
                GridField::create(
                    'Datasets',
                    'Datasets',
                    $this->getComponents('Datasets'),
                    $config
                )
            );
        }

        return $fields;
    }

    public function getCMSValidator()
    {
        return RequiredFields::create(
            'Title',
            'ChartType'
        );
    }

    protected function onAfterDelete()
    {
        parent::onAfterDelete();

        foreach ($this->getComponents('Datasets') as $dataset) {
            $dataset->delete();
        }
    }

    /**
     * Get a JSON encoded string representing the chart's CSV data.
     *
     * @return string
     */
    public function getChartData()
    {
        $chartData = [
            'type' => $this->getField('ChartType'),
            'data' => [
                'labels' => [],
                'datasets' => [],
            ],
        ];

        $datasets = $this->getComponents('Datasets');

        // Populate the data.
        if ($datasets->count()) {
            $chartData['data']['labels'] = $datasets->first()->getChartLabels();

            foreach ($datasets as $dataset) {
                $chartData['data']['datasets'][] = $dataset->getChartDataset();
            }
        }

        // Set some default options.
        switch ($chartData['type']) {
            case 'bar':
                $chartData['options'] = [
                    'responsive' => true,
                    'scales' => [
                        'yAxes' => [
                            [
                                'ticks' => [
                                    'beginAtZero' => true,
                                    'min' => 0,
                                ],
                            ],
                        ],
                    ],
                ];
                break;

            case 'pie':
                $chartData['options'] = [
                    'responsive' => true,
                ];
                break;

            default:
                $chartData['options'] = [
                    'responsive' => true,
                ];
                break;
        }

        $this->extend('updateChartData', $chartData);

        return json_encode($chartData);
    }
}
