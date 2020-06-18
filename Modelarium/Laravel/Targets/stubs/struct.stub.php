<?php

namespace App\Formularium;

class DummyClass extends \FormulariumLaravel\BaseModel
{
    public function __construct() {
        parent::__construct(
            'DummyName',
            [
                // TODO: fill this
                'fieldName' => [
                    'datatype' => 'string',
                    'validators' => [],
                    'extensions' => [],
                ]
            ]
        );
    }
}
