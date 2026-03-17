<?php

return [
    'general' => [
        'bienvenida_institucional' => 'Bienvenido/a al canal oficial de Medicina Laboral UNLu.',
        'canal_alcance' => 'Este canal permite registrar aviso de ausencia y anticipo de certificado médico.',
        'canal_derivacion' => 'Para otras gestiones o consultas no contempladas en este flujo, por favor utilice los canales institucionales habituales.',
        'reinicio' => 'Por favor, escribí tu DNI para comenzar.',
    ],

    'menu' => [
        'prompt' => 'Seleccione una opción para continuar.',
        'consultas_no_disponible' => 'La opción Consultas todavía no está disponible en este MVP. Puede continuar con aviso de ausencia o anticipo de certificado médico.',
        'options' => [
            'consultas' => 'Consultas',
            'aviso_ausencia' => 'Aviso de ausencia',
            'anticipo_certificado' => 'Anticipo de certificado médico',
        ],
        'button_titles' => [
            'consultas' => 'Consultas',
            'aviso_ausencia' => 'Aviso ausencia',
            'anticipo_certificado' => 'Anticipo cert.',
        ],
    ],

    'identificacion' => [
        'inicio' => 'Para continuar, necesitamos validar tu identificación.',
        'dni' => 'Por favor, escribí tu número de DNI para continuar.',
        'nombre_completo' => 'Por favor, ingresá tu nombre completo.',
        'legajo' => 'Por favor, ingresá tu número de legajo.',
        'sede' => 'Indicá tu sede. Podés responder con el número o el nombre de la sede.',
        'jornada_laboral' => 'Indicá tu jornada laboral.',
        'continuacion_aviso' => 'Identificación registrada. El siguiente paso del aviso será ingresar la fecha de inicio de la ausencia.',
        'continuacion_certificado' => 'Identificación registrada. El siguiente paso del anticipo será ingresar el número de aviso asociado.',
        'confirmacion_previa' => 'Antes de continuar, confirmá si los datos de identificación son correctos.',
        'confirmar_si' => 'Sí',
        'confirmar_no' => 'No',
    ],

    'aviso' => [
        'inicio' => 'Vas a registrar un aviso de ausencia. Completá los datos solicitados para continuar.',
        'intro_transicional' => "Vas a registrar un aviso de ausencia. Por ahora este MVP continúa con una carga mínima transicional.\n\n¿Cuántos días de inasistencia querés registrar?",
        'prompts' => [
            'fecha_desde' => 'Ingresá la fecha de inicio de la ausencia.',
            'fecha_hasta' => 'Ingresá la fecha de finalización de la ausencia.',
            'periodo_validado' => 'Ud. ha ingresado un período de :dias días consecutivos de ausencia correspondiente al período comprendido entre :fecha_desde y :fecha_hasta.',
            'tipo_ausentismo' => 'Seleccioná el tipo de ausentismo.',
            'nombre_familiar' => 'Ingresá el nombre completo del familiar.',
            'parentesco' => 'Ingresá el parentesco.',
            'motivo' => 'Describí la lesión, afección o síntomas. Si corresponde, indicá si existe internación o cirugía programada.',
            'domicilio_circunstancial_pregunta' => '¿Desea informar un domicilio circunstancial?',
            'domicilio_circunstancial' => 'Ingresá el domicilio circunstancial.',
            'observaciones_pregunta' => '¿Desea agregar observaciones adicionales?',
            'cantidad_dias_transicional' => '¿Cuántos días de inasistencia querés registrar?',
        ],
        'options' => [
            'por_enfermedad' => 'Por Enfermedad',
            'atencion_familiar_enfermo' => 'Por Atención de Familiar Enfermo',
            'si' => 'Sí',
            'no_continuar' => 'No, continuar',
            'confirmar' => 'Confirmar aviso',
            'cancelar' => 'Cancelar y volver al menú principal',
        ],
        'confirmacion_final' => 'Revisá el resumen del aviso antes de confirmar.',
        'pendiente_siguiente_etapa' => 'El paso de fecha de inicio del aviso queda preparado, pero su implementación completa se continúa en la siguiente etapa.',
        'registrado_breve' => 'Inasistencia registrada. Revisá el detalle enviado por este canal.',
    ],

    'certificado' => [
        'inicio' => 'Vas a registrar un anticipo de certificado médico. Podés adjuntar hasta :max_files archivos o imágenes dentro de :deadline horas del aviso. Formatos permitidos: :allowed_extensions.',
        'intro_transicional' => "Vas a registrar un anticipo de certificado médico. Podés adjuntar hasta :max_files archivos o imágenes dentro de :deadline horas del aviso. Formatos permitidos: :allowed_extensions.\n\nPodés escribir un breve detalle del certificado o adjuntar una imagen (por ahora solo manejamos texto).",
        'numero_aviso' => 'Para vincular el certificado al aviso de ausencia, ingrese el Número de Aviso.',
        'tipo_certificado' => 'Seleccioná el tipo de certificado.',
        'adjuntar_archivo' => 'Adjuntá el archivo o imagen del certificado.',
        'adjuntar_otro_archivo' => '¿Desea adjuntar otro archivo?',
        'detalle_o_adjunto_transicional' => 'Podés escribir un breve detalle del certificado o adjuntar una imagen (por ahora solo manejamos texto).',
        'options' => [
            'manuscrito' => 'Manuscrito',
            'electronico' => 'Electrónico',
            'si' => 'Sí',
            'no_continuar' => 'No, continuar',
            'confirmar' => 'Confirmar anticipo de certificado',
            'cancelar' => 'Cancelar y volver al menú principal',
        ],
        'confirmacion_final' => 'Revisá el resumen del anticipo de certificado antes de confirmar.',
        'pendiente_siguiente_etapa' => 'El paso de número de aviso para el anticipo queda preparado, pero su implementación completa se continúa en la siguiente etapa.',
        'registrado_breve' => 'Certificado registrado. Revisá el detalle enviado por este canal.',
    ],

    'errores' => [
        'legajo_invalido' => 'El número de legajo ingresado no es válido. Por favor, verifíquelo.',
        'aviso_inexistente' => 'No se encontró un aviso de ausencia con el número informado.',
        'aviso_no_corresponde_legajo' => 'El aviso informado no corresponde al legajo ingresado.',
        'plazo_vencido_anticipo' => 'El plazo para registrar el anticipo de certificado se encuentra vencido.',
        'required' => 'El dato ingresado es obligatorio.',
        'invalid_data' => 'El dato ingresado no es válido. Por favor, verifíquelo.',
        'invalid_option' => 'La opción ingresada no es válida.',
        'invalid_format' => 'El formato ingresado no es válido.',
        'invalid_date' => 'La fecha ingresada no es válida.',
        'invalid_attachment_type' => 'El archivo enviado no tiene un formato permitido.',
        'max_attempts_exceeded' => 'Se alcanzó el máximo de intentos permitidos.',
        'no_open_aviso' => 'No se encontró un aviso de ausencia abierto para continuar con esta gestión.',
        'sede_invalida' => 'La sede ingresada no es válida. Seleccioná una de las opciones disponibles.',
        'unsupported_step' => 'No se pudo resolver el paso actual de la conversación.',
    ],

    'errors' => [
        'required' => 'El dato ingresado es obligatorio.',
        'invalid_date' => 'La fecha ingresada no es válida.',
        'invalid_option' => 'La opción ingresada no es válida.',
        'max_attempts_exceeded' => 'Se alcanzó el máximo de intentos permitidos.',
        'no_open_aviso' => 'No se encontró un aviso de ausencia abierto para continuar con esta gestión.',
    ],

    'interacciones_invalidas' => [
        'general' => 'El mensaje o archivo enviado no puede ser procesado por este canal. Para continuar, por favor utilice las opciones del menú.',
        'certificado_fuera_flujo' => 'No es posible registrar un certificado en este momento porque no hay un flujo de anticipo activo.',
        'texto_no_reconocido' => 'No pudimos reconocer el texto ingresado. Para continuar, utilizá las opciones disponibles.',
    ],

    'timeouts' => [
        'recordatorio' => 'Tu trámite sigue pendiente. Si deseás continuar, respondé a este mensaje. De lo contrario, el flujo podrá cancelarse automáticamente.',
        'cancelacion' => 'La conversación fue cancelada por inactividad. Si deseás retomar la gestión, iniciá una nueva interacción desde el menú principal.',
    ],

    'derivacion' => [
        'umbral_superado' => 'Se superó el umbral de intentos permitido. ¿Desea contactar a un operador?',
        'contactar_operador' => 'Sí, contactar a un operador',
        'cancelar_menu' => 'No, cancelar y volver al menú principal',
    ],

    'confirmaciones' => [
        'si' => 'Sí',
        'no' => 'No',
    ],

    'cancelacion' => [
        'accion' => 'Cancelar y volver al menú principal',
        'volver_menu_principal' => 'Operación cancelada. Volvés al menú principal.',
        'aviso' => 'El aviso fue cancelado. No se generó ningún registro y los datos informados fueron descartados.',
        'certificado' => 'El anticipo de certificado fue cancelado. No se generó ningún registro y los datos informados fueron descartados.',
    ],
];
