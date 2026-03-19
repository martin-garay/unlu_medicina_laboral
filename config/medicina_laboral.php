<?php

return [
    'conversation' => [
        'max_invalid_attempts' => 3,
        'cancel_keywords' => [
            'cancelar',
            'salir',
            'anular',
        ],
        'allowed_restart_keywords' => [
            'menu',
            'inicio',
            'reiniciar',
            'empezar',
        ],
        'first_inactivity_minutes' => 30,
        'second_inactivity_minutes' => 60,
    ],

    'certificados' => [
        'max_files' => 3,
        'max_files_initial_step' => 1,
        'allowed_extensions' => [
            'pdf',
            'jpg',
            'jpeg',
            'png',
        ],
        'allowed_mime_types' => [
            'application/pdf',
            'image/jpeg',
            'image/png',
        ],
        'max_size_kb' => 5120,
        'deadline_business_hours' => 24,
        'allowed_incoming_message_types' => [
            'document',
            'image',
        ],
    ],

    'avisos' => [
        'input_date_format' => 'd/m/Y',
        'input_date_display_format' => 'dd/mm/YYYY',
        'domicilio_yes_keywords' => [
            '1',
            'si',
            'sí',
        ],
        'domicilio_no_keywords' => [
            '2',
            'no',
            'continuar',
            'no, continuar',
        ],
        'observaciones_skip_keywords' => [
            'no',
            'continuar',
            'no, continuar',
            'sin observaciones',
        ],
    ],

    'catalogos' => [
        'menu_principal' => [
            'consultas' => [
                'id' => 'op_consultas',
                'flow_code' => 'consultas',
                'aliases' => [
                    '1',
                    'consultas',
                ],
            ],
            'aviso_ausencia' => [
                'id' => 'op_inasistencia',
                'flow_code' => 'inasistencia',
                'aliases' => [
                    '2',
                    'aviso de ausencia',
                    'inasistencia',
                    'notificar inasistencia',
                ],
            ],
            'anticipo_certificado' => [
                'id' => 'op_certificado',
                'flow_code' => 'certificado',
                'aliases' => [
                    '3',
                    'certificado',
                    'subir certificado',
                    'anticipo de certificado',
                    'anticipo de certificado médico',
                ],
            ],
        ],
        'sedes' => [
            'central' => 'Sede Central',
            'campus' => 'Campus Luján',
            'delegacion' => 'Delegación San Fernando',
        ],
        'tipos_ausentismo' => [
            'por_enfermedad' => 'Por Enfermedad',
            'atencion_familiar_enfermo' => 'Por Atención de Familiar Enfermo',
        ],
        'tipos_certificado' => [
            'manuscrito' => 'Manuscrito',
            'electronico' => 'Electrónico',
        ],
        'parentescos' => [
            'madre' => 'Madre',
            'padre' => 'Padre',
            'hijo_hija' => 'Hijo/a',
            'conyuge' => 'Cónyuge',
            'otro' => 'Otro',
        ],
    ],

    'mapuche' => [
        'driver' => env('MAPUCHE_DRIVER', 'mock'),
        'mock' => [
            'accept_unknown_legajo' => env('MAPUCHE_MOCK_ACCEPT_UNKNOWN_LEGAJO', true),
            'records' => [
                '10001' => [
                    'nombre_completo' => 'Ana Perez',
                    'sede' => 'Sede Central',
                    'jornada_laboral' => 'Manana',
                ],
                '10002' => [
                    'nombre_completo' => 'Juan Gomez',
                    'sede' => 'Campus Lujan',
                    'jornada_laboral' => 'Tarde',
                ],
            ],
        ],
    ],

    'worker_identification' => [
        'driver' => env('WORKER_IDENTIFICATION_DRIVER', 'mapuche'),
        'snapshot_metadata_key' => 'worker_lookup',
    ],

    'mail' => [
        'driver' => env('MEDICINA_LABORAL_MAIL_DRIVER', 'null'),
        'aviso_registered_recipient' => env('MEDICINA_LABORAL_MAIL_AVISO_REGISTERED_RECIPIENT'),
    ],

    'storage' => [
        'driver' => env('MEDICINA_LABORAL_STORAGE_DRIVER', 'metadata'),
        'draft_attachments' => [
            'disk' => env('MEDICINA_LABORAL_DRAFT_ATTACHMENTS_DISK', 'local'),
            'directory' => env('MEDICINA_LABORAL_DRAFT_ATTACHMENTS_DIRECTORY', 'medicina_laboral/drafts'),
        ],
    ],

    'mensajes' => [
        'use_emojis' => true,
        'prefix_numbered_options' => true,
        'menu_principal_options' => [
            'consultas',
            'aviso_ausencia',
            'anticipo_certificado',
        ],
        'templates' => [
            'aviso_confirmacion_final' => 'messages.aviso.confirmacion_final',
            'aviso_registrado' => 'messages.aviso.aviso_registrado',
            'aviso_cancelacion' => 'messages.aviso.cancelacion',
            'certificado_confirmacion_final' => 'messages.certificado.confirmacion_final',
            'certificado_resumen_borrador' => 'messages.certificado.resumen_borrador',
            'certificado_registrado' => 'messages.certificado.anticipo_registrado',
            'certificado_cancelacion' => 'messages.certificado.cancelacion',
            'bienvenida' => 'messages.comunes.bienvenida',
            'inactividad_recordatorio' => 'messages.comunes.inactividad_recordatorio',
            'inactividad_cancelacion' => 'messages.comunes.inactividad_cancelacion',
        ],
    ],
];
