<?php

return [

    // Appointment Booked Notifications
    'appointment_booked' => [
        'subject' => 'Confirmation de rendez-vous',
        'greeting' => 'Bonjour :name,',
        'message' => 'Votre rendez-vous a été réservé avec succès !',
        'details' => 'Détails du rendez-vous :',
        'date' => 'Date : :date',
        'time' => 'Heure : :time',
        'tenant' => 'Clinique : :tenant',
        'footer' => 'Merci d’utiliser nos services !',
        'sms' => 'Bonjour :name, votre rendez-vous est réservé le :date à :time chez :tenant',
    ],

    // Appointment Reminder Notifications
    'appointment_reminder' => [
        'subject' => 'Rappel de rendez-vous',
        'greeting' => 'Bonjour :name,',
        'message' => 'Ceci est un rappel concernant votre prochain rendez-vous !',
        'details' => 'Détails du rendez-vous :',
        'date' => 'Date : :date',
        'time' => 'Heure : :time',
        'tenant' => 'Clinique : :tenant',
        'footer' => 'Nous avons hâte de vous accueillir !',
        'sms' => 'Rappel : votre rendez-vous est prévu le :date à :time chez :tenant',
    ],

    // Appointment Cancelled Notifications
    'appointment_cancelled' => [
        'subject' => 'Rendez-vous annulé',
        'greeting' => 'Bonjour :name,',
        'message' => 'Votre rendez-vous a été annulé.',
        'details' => 'Détails du rendez-vous annulé :',
        'date' => 'Date : :date',
        'time' => 'Heure : :time',
        'tenant' => 'Clinique : :tenant',
        'footer' => 'Vous pouvez prendre un autre rendez-vous à tout moment.',
        'sms' => 'Votre rendez-vous du :date à :time chez :tenant a été annulé',
    ],

    // Appointment Confirmed Notifications
    'appointment_confirmed' => [
        'subject' => 'Rendez-vous confirmé',
        'greeting' => 'Bonjour :name,',
        'message' => 'Votre rendez-vous a été confirmé !',
        'details' => 'Détails du rendez-vous :',
        'date' => 'Date : :date',
        'time' => 'Heure : :time',
        'tenant' => 'Clinique : :tenant',
        'footer' => 'Nous avons hâte de vous accueillir !',
        'sms' => 'Votre rendez-vous du :date à :time chez :tenant est confirmé',
    ],

    // Queue Update Notifications
    'queue_next' => [
        'subject' => 'C’est votre tour !',
        'greeting' => 'Bonjour :name,',
        'message' => 'C’est votre tour ! Veuillez vous présenter au guichet maintenant.',
        'queue_number' => 'Numéro dans la file : :number',
        'footer' => 'Merci pour votre patience !',
        'sms' => 'Bonjour :name, c’est votre tour ! Numéro dans la file : :number. Veuillez vous présenter au guichet.',
    ],

    'queue_position_update' => [
        'subject' => 'Mise à jour de votre position dans la file',
        'greeting' => 'Bonjour :name,',
        'message' => 'Mise à jour de votre position dans la file :',
        'queue_number' => 'Numéro dans la file : :number',
        'position' => 'Personnes devant vous : :position',
        'estimated_wait' => 'Temps d’attente estimé : :time minutes',
        'footer' => 'Merci pour votre patience !',
        'sms' => 'Numéro : :number, :position personnes devant vous, temps estimé : :time minutes',
    ],

    'queue_ready' => [
        'subject' => 'C’est bientôt votre tour',
        'greeting' => 'Bonjour :name,',
        'message' => 'Préparez-vous, votre tour arrive très bientôt !',
        'queue_number' => 'Numéro dans la file : :number',
        'position' => 'Une seule personne devant vous',
        'footer' => 'Merci pour votre patience !',
        'sms' => 'Préparez-vous ! Numéro dans la file : :number, une seule personne devant vous.',
    ],

    'queue_skipped' => [
        'subject' => 'Votre tour a été passé',
        'greeting' => 'Bonjour :name,',
        'message' => 'Votre tour a été passé en raison de votre absence.',
        'queue_number' => 'Numéro dans la file : :number',
        'footer' => 'Vous pouvez obtenir un nouveau numéro auprès du guichet.',
        'sms' => 'Votre tour (numéro :number) a été passé en raison de votre absence. Vous pouvez obtenir un nouveau numéro.',
    ],

    // Common
    'view_details' => 'Voir les détails',
    'thank_you' => 'Merci d’utiliser nos services',
    'regards' => 'Cordialement',
];
