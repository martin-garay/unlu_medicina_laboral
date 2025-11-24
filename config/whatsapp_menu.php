<?php

return [
    'body_text' => '¿Querés notificar una inasistencia o subir un certificado?',

    'buttons' => [
        [
            'id' => 'op_inasistencia',
            'title' => 'Notificar inasistencia',
        ],
        [
            'id' => 'op_certificado',
            'title' => 'Subir certificado',
        ],
    ],

    'id_to_tipo' => [
        'op_inasistencia' => 'inasistencia',
        'op_certificado' => 'certificado',
    ],

    'text_to_tipo' => [
        '1' => 'inasistencia',
        'inasistencia' => 'inasistencia',
        'notificar inasistencia' => 'inasistencia',
        '2' => 'certificado',
        'certificado' => 'certificado',
        'subir certificado' => 'certificado',
    ],
];
