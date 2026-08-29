<?php

return [
    // Appointment Booked Notifications
    'appointment_booked' => [
        'subject' => 'Confirmación de cita',
        'greeting' => 'Hola :name,',
        'message' => '¡Tu cita se reservó correctamente!',
        'details' => 'Detalles de la cita:',
        'date' => 'Fecha: :date',
        'time' => 'Hora: :time',
        'tenant' => 'Clínica: :tenant',
        'footer' => '¡Gracias por usar nuestros servicios!',
        'sms' => 'Hola :name, tu cita está reservada el :date a las :time en :tenant.',
    ],

    // Appointment Reminder Notifications
    'appointment_reminder' => [
        'subject' => 'Recordatorio de cita',
        'greeting' => 'Hola :name,',
        'message' => 'Este es un recordatorio de tu próxima cita.',
        'details' => 'Detalles de la cita:',
        'date' => 'Fecha: :date',
        'time' => 'Hora: :time',
        'service' => 'Servicio: :service',
        'staff' => 'Especialista: :staff',
        'tenant' => 'Clínica: :tenant',
        'queue' => 'Número de turno: :number',
        'reference' => 'Referencia de reserva: :reference',
        'tracking' => 'Seguimiento de tu cita: :url',
        'footer' => '¡Esperamos verte pronto!',
        'sms' => 'Recordatorio: tu cita es el :date a las :time en :tenant.',
    ],

    // Appointment Cancelled Notifications
    'appointment_cancelled' => [
        'subject' => 'Cita cancelada',
        'greeting' => 'Hola :name,',
        'message' => 'Tu cita ha sido cancelada.',
        'details' => 'Detalles de la cita cancelada:',
        'date' => 'Fecha: :date',
        'time' => 'Hora: :time',
        'tenant' => 'Clínica: :tenant',
        'footer' => 'Puedes reservar otra cita cuando quieras.',
        'sms' => 'Tu cita del :date a las :time en :tenant ha sido cancelada.',
    ],

    // Appointment Confirmed Notifications
    'appointment_confirmed' => [
        'subject' => 'Cita confirmada',
        'greeting' => 'Hola :name,',
        'message' => '¡Tu cita ha sido confirmada!',
        'details' => 'Detalles de la cita:',
        'date' => 'Fecha: :date',
        'time' => 'Hora: :time',
        'tenant' => 'Clínica: :tenant',
        'footer' => '¡Esperamos verte pronto!',
        'sms' => 'Tu cita del :date a las :time en :tenant está confirmada.',
    ],

    // Queue Update Notifications
    'queue_next' => [
        'subject' => '¡Es tu turno!',
        'greeting' => 'Hola :name,',
        'message' => '¡Es tu turno! Por favor, acércate al mostrador ahora.',
        'queue_number' => 'Número de turno: :number',
        'footer' => '¡Gracias por tu paciencia!',
        'sms' => 'Hola :name, ¡es tu turno! Número: :number. Acércate al mostrador.',
    ],

    'queue_position_update' => [
        'subject' => 'Actualización de tu posición',
        'greeting' => 'Hola :name,',
        'message' => 'Actualización de tu posición en la cola:',
        'queue_number' => 'Número de turno: :number',
        'position' => 'Personas delante de ti: :position',
        'estimated_wait' => 'Tiempo de espera estimado: :time minutos',
        'footer' => '¡Gracias por tu paciencia!',
        'sms' => 'Número :number, :position personas delante, espera estimada: :time minutos.',
    ],

    'queue_ready' => [
        'subject' => 'Tu turno está cerca',
        'greeting' => 'Hola :name,',
        'message' => 'Prepárate, ¡tu turno llegará muy pronto!',
        'queue_number' => 'Número de turno: :number',
        'position' => 'Solo hay una persona delante de ti',
        'footer' => '¡Gracias por tu paciencia!',
        'sms' => '¡Prepárate! Número :number, solo una persona delante.',
    ],

    'queue_skipped' => [
        'subject' => 'Tu turno fue omitido',
        'greeting' => 'Hola :name,',
        'message' => 'Tu turno fue omitido debido a tu ausencia.',
        'queue_number' => 'Número de turno: :number',
        'footer' => 'Puedes obtener un nuevo número en el mostrador.',
        'sms' => 'Tu turno (número :number) fue omitido por ausencia. Puedes obtener un nuevo número.',
    ],

    // Common
    'view_details' => 'Ver detalles',
    'thank_you' => 'Gracias por usar nuestros servicios',
    'regards' => 'Saludos cordiales',
];
