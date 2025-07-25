<?php
/**
 * Beaver Builder Booking Module
 */

if (!defined('ABSPATH')) {
    exit;
}

class ReserveMateBookingModule extends FLBuilderModule {
    
    public function __construct() {
        parent::__construct([
            'name'            => __('Reserve Mate Booking Form', 'reserve-mate'),
            'description'     => __('Add Reserve Mate booking form to your page', 'reserve-mate'),
            'category'        => __('Advanced Modules', 'reserve-mate'),
            'icon'            => 'calendar', // Use a dashicon or ensure proper SVG path
            'editor_export'   => true,
            'enabled'         => true,
            'partial_refresh' => true,
        ]);
    }
}

FLBuilder::register_module('ReserveMateBookingModule', [
    'general' => [
        'title' => __('General', 'reserve-mate'),
        'sections' => [
            'general' => [
                'title' => __('Booking Form Settings', 'reserve-mate'),
                'fields' => [
                    'form_title' => [
                        'type' => 'text',
                        'label' => __('Form Title', 'reserve-mate'),
                        'default' => __('Make a Reservation', 'reserve-mate'),
                        'placeholder' => __('Enter form title...', 'reserve-mate'),
                        'help' => __('Optional title to display above the booking form', 'reserve-mate')
                    ],
                    'show_title' => [
                        'type' => 'select',
                        'label' => __('Show Title', 'reserve-mate'),
                        'default' => 'yes',
                        'options' => [
                            'yes' => __('Yes', 'reserve-mate'),
                            'no' => __('No', 'reserve-mate')
                        ]
                    ]
                ]
            ]
        ]
    ],
    'style' => [
        'title' => __('Style', 'reserve-mate'),
        'sections' => [
            'container' => [
                'title' => __('Container', 'reserve-mate'),
                'fields' => [
                    'container_padding' => [
                        'type' => 'dimension',
                        'label' => __('Padding', 'reserve-mate'),
                        'default' => '20',
                        'units' => ['px', 'em', '%'],
                        'slider' => true,
                        'responsive' => true,
                        'preview' => [
                            'type' => 'css',
                            'selector' => '.rm-booking-form-container',
                            'property' => 'padding'
                        ]
                    ],
                    'container_margin' => [
                        'type' => 'dimension',
                        'label' => __('Margin', 'reserve-mate'),
                        'default' => '0',
                        'units' => ['px', 'em', '%'],
                        'slider' => true,
                        'responsive' => true,
                        'preview' => [
                            'type' => 'css',
                            'selector' => '.rm-booking-form-container',
                            'property' => 'margin'
                        ]
                    ],
                    'background_color' => [
                        'type' => 'color',
                        'label' => __('Background Color', 'reserve-mate'),
                        'default' => 'ffffff',
                        'show_reset' => true,
                        'show_alpha' => true,
                        'preview' => [
                            'type' => 'css',
                            'selector' => '.rm-booking-form-container',
                            'property' => 'background-color'
                        ]
                    ],
                    'border_radius' => [
                        'type' => 'unit',
                        'label' => __('Border Radius', 'reserve-mate'),
                        'default' => '0',
                        'units' => ['px', 'em', '%'],
                        'slider' => true,
                        'preview' => [
                            'type' => 'css',
                            'selector' => '.rm-booking-form-container',
                            'property' => 'border-radius'
                        ]
                    ]
                ]
            ],
            'title_style' => [
                'title' => __('Title Style', 'reserve-mate'),
                'fields' => [
                    'title_color' => [
                        'type' => 'color',
                        'label' => __('Title Color', 'reserve-mate'),
                        'default' => '333333',
                        'show_reset' => true,
                        'preview' => [
                            'type' => 'css',
                            'selector' => '.rm-booking-form-title',
                            'property' => 'color'
                        ]
                    ],
                    'title_typography' => [
                        'type' => 'typography',
                        'label' => __('Title Typography', 'reserve-mate'),
                        'responsive' => true,
                        'preview' => [
                            'type' => 'css',
                            'selector' => '.rm-booking-form-title'
                        ]
                    ]
                ]
            ]
        ]
    ]
]);