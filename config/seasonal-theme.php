<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Active Seasonal Theme
    |--------------------------------------------------------------------------
    |
    | This option determines the currently active seasonal theme. Set to
    | 'none' to disable seasonal decorations. Set to 'auto' to activate
    | themes automatically based on the date ranges defined below.
    |
    | Supported themes: 'none', 'halloween', 'christmas', 'newyear'
    |
    */

    'active' => env('SEASONAL_THEME', 'auto'),

    /*
    |--------------------------------------------------------------------------
    | Automatic Theme Schedules
    |--------------------------------------------------------------------------
    |
    | Define date ranges for automatic theme activation when active is set
    | to 'auto'. Format: ['start' => 'MM-DD', 'end' => 'MM-DD']
    |
    */

    'schedules' => [
        'halloween' => [
            'start' => '10-31',
            'end'   => '11-02',
        ],
        'christmas' => [
            'start' => '12-20',
            'end'   => '12-26',
        ],
        'newyear'   => [
            'start' => '01-01',
            'end'   => '01-02',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Theme Definitions
    |--------------------------------------------------------------------------
    |
    | Human-readable labels and descriptions for each theme, used in the UI.
    |
    */

    'themes' => [
        'none'      => [
            'label'       => 'Default',
            'description' => 'No seasonal decoration',
        ],
        'halloween' => [
            'label'       => 'Halloween',
            'description' => 'Spooky orange and purple accents',
        ],
        'christmas' => [
            'label'       => 'Christmas',
            'description' => 'Festive red and green accents',
        ],
        'newyear'   => [
            'label'       => 'New Year',
            'description' => 'Golden fireworks and celebration',
        ],
    ],

];
