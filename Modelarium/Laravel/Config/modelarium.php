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
            "HTML",
            "HTMLValidation"
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
        "axios" => [
            "importFile" => "",
            "method" => "axios"
        ],
        "cleanIdentifierBody" => "return identifier;",
        "escapeIdentifierBody" => "return identifier;",
        "messages" => [
            "nothingFound" => "Nothing found"
        ]
    ]
];
