<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Modelarium basic attributes
    |--------------------------------------------------------------------------
    |
    | These control basic things like formatting, paths, etc.
    |
    */

    "modelarium" => [
        /*
         * The Model directory.
         */
        "modelDir" => "app/Models",

        /*
         * Are you using LighthousePHP?
         */
        "lighthouse" => true
    ],

    /*
    |--------------------------------------------------------------------------
    | Frontend
    |--------------------------------------------------------------------------
    |
    | Configuration for the frontend generator
    |
    */
    "frontend" => [
        /*
         * The frameworks you are using for frontend. Order matters.
         */
        "framework" => [
            // "HTML",
            // "HTMLValidation"
            // etc
        ],

        /*
         * Should we run prettier after generating the components?
         */
        "prettier" => true,

        /*
         * Should we run eslint after generating the components?
         */
        "eslint" => true
    ],

    /*
    |--------------------------------------------------------------------------
    | Vue settings
    |--------------------------------------------------------------------------
    |
    | These control the vue generator, if you are using it.
    |
    */
    "vue" => [
        /**
         * Use the runtimeValidator JS library
         */
        'runtimeValidator' => false,

        /**
         * The axios variable name
         */
        'axios' => [
            'importFile' => 'axios',
            'method' => 'axios'
        ],

        /**
         * Generate action buttons even if we don't have a can field in the model
         */
        'actionButtonsNoCan' => false,

        /**
         * cleanIdentifier method
         */
        'cleanIdentifierBody' => 'return identifier;',
        
        /**
         * escapeIdentifier method
         */
        'escapeIdentifierBody' => 'return identifier;',

        /**
         * Message text
         */
        'messages' => [
            'nothingFound' => 'Nothing found'
        ]
    ]
];
