<?php

if (!defined('ABSPATH')) {
    exit; // Don't access directly
};

use const LEXO\CP\{
    PATH,
    URL
};

return [
    'priority'  => 90,
    'dist_path' => PATH . 'dist',
    'dist_uri'  => URL . 'dist',
    'assets'    => [
        'front' => [
            'styles'    => [],
            'scripts'   => []
        ],
        'admin' => [
            'styles'    => ['css/admin-cp.css'],
            'scripts'   => ['js/admin-cp.js']
        ],
        'editor' => [
            'styles'    => []
        ],
    ]
];
