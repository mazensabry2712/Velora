<?php

return [
    // Appointment Booked Notifications
    'appointment_booked' => [
        'subject' => 'Appointment Confirmation',
        'greeting' => 'Hello :name,',
        'message' => 'Your appointment has been successfully booked!',
        'details' => 'Appointment Details:',
        'date' => 'Date: :date',
        'time' => 'Time: :time',
        'tenant' => 'Clinic: :tenant',
        'footer' => 'Thank you for using our services!',
        'sms' => 'Hello :name, your appointment is booked for :date at :time at :tenant',
    ],

    // Appointment Reminder Notifications
    'appointment_reminder' => [
        'subject' => 'Appointment Reminder',
        'greeting' => 'Hello :name,',
        'message' => 'This is a reminder for your upcoming appointment!',
        'details' => 'Appointment Details:',
        'date' => 'Date: :date',
        'time' => 'Time: :time',
        'tenant' => 'Clinic: :tenant',
        'footer' => 'We look forward to seeing you!',
        'sms' => 'Reminder: Your appointment is on :date at :time at :tenant',
    ],

    // Appointment Cancelled Notifications
    'appointment_cancelled' => [
        'subject' => 'Appointment Cancelled',
        'greeting' => 'Hello :name,',
        'message' => 'Your appointment has been cancelled.',
        'details' => 'Cancelled Appointment Details:',
        'date' => 'Date: :date',
        'time' => 'Time: :time',
        'tenant' => 'Clinic: :tenant',
        'footer' => 'You can book another appointment anytime.',
        'sms' => 'Your appointment on :date at :time at :tenant has been cancelled',
    ],

    // Appointment Confirmed Notifications
    'appointment_confirmed' => [
        'subject' => 'Appointment Confirmed',
        'greeting' => 'Hello :name,',
        'message' => 'Your appointment has been confirmed!',
        'details' => 'Appointment Details:',
        'date' => 'Date: :date',
        'time' => 'Time: :time',
        'tenant' => 'Clinic: :tenant',
        'footer' => 'We look forward to seeing you!',
        'sms' => 'Your appointment on :date at :time at :tenant is confirmed',
    ],

    // Queue Update Notifications
    'queue_next' => [
        'subject' => 'Your Turn Now!',
        'greeting' => 'Hello :name,',
        'message' => "It's your turn! Please proceed to the desk now.",
        'queue_number' => 'Queue Number: :number',
        'footer' => 'Thank you for waiting!',
        'sms' => 'Hello :name, it\'s your turn! Queue Number: :number. Please proceed to the desk.',
    ],

    'queue_position_update' => [
        'subject' => 'Queue Position Update',
        'greeting' => 'Hello :name,',
        'message' => 'Update on your queue position:',
        'queue_number' => 'Queue Number: :number',
        'position' => 'People ahead of you: :position',
        'estimated_wait' => 'Estimated wait time: :time minutes',
        'footer' => 'Thank you for your patience!',
        'sms' => 'Queue Number: :number, :position people ahead, estimated time: :time minutes',
    ],

    'queue_ready' => [
        'subject' => 'Almost Your Turn',
        'greeting' => 'Hello :name,',
        'message' => 'Please get ready, your turn is coming up very soon!',
        'queue_number' => 'Queue Number: :number',
        'position' => 'Only 1 person ahead of you',
        'footer' => 'Thank you for waiting!',
        'sms' => 'Please get ready! Queue Number: :number, only 1 person ahead of you.',
    ],

    'queue_skipped' => [
        'subject' => 'Your Turn Was Skipped',
        'greeting' => 'Hello :name,',
        'message' => 'Your turn was skipped due to absence.',
        'queue_number' => 'Queue Number: :number',
        'footer' => 'You can get a new queue number from the desk.',
        'sms' => 'Your turn (number :number) was skipped due to absence. You can get a new queue number.',
    ],

    // Common
    'view_details' => 'View Details',
    'thank_you' => 'Thank you for using our services',
    'regards' => 'Regards',
];
