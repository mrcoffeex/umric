<?php

/**
 * Default PDF form options for panel defense evaluation forms.
 * Merged with each evaluation format’s `pdf_settings` JSON.
 */
return [

    'logo_path' => public_path('images/um-ric-evaluation-form-logo.png'),

    'defaults' => [
        'enabled' => true,
        'header_institution' => 'RESEARCH AND INNOVATION CENTER',
        'form_title' => 'THESIS OUTLINE DEFENSE EVALUATION FORM',
        'form_subtitle' => '(For Students)',
        'branches' => [
            ['label' => 'Main', 'default' => false],
            ['label' => 'Branch DIGOS', 'default' => true],
        ],
        'show_rating_scale' => true,
        'show_pass_fail' => true,
        'show_signature_block' => true,
        'document' => [
            'code' => 'F-13100-012',
            'revision' => '3',
            'effectivity' => 'January 8, 2026',
        ],
        'passing_score' => 85,
    ],

    /*
     * Default qualitative rating scale (numeric ranges and descriptions).
     * Override per format via pdf_settings['rating_scale'].
     */
    'default_rating_scale' => [
        [
            'range' => '100 – 95',
            'equivalent' => 'Outstanding',
            'description' => 'The indicators of the criterion are carried out in an excellent manner.',
        ],
        [
            'range' => '95 – 90',
            'equivalent' => 'Very Good',
            'description' => 'The indicators of the criterion are fully addressed.',
        ],
        [
            'range' => '90 – 85',
            'equivalent' => 'Good',
            'description' => 'The indicators of the criterion are substantially addressed with minor issues.',
        ],
        [
            'range' => '85 – 80',
            'equivalent' => 'Fair',
            'description' => 'The indicators of the criterion are only partially met.',
        ],
        [
            'range' => '80 – 70',
            'equivalent' => 'Needs Improvement',
            'description' => 'The indicators of the criterion are inadequately met.',
        ],
    ],
];
