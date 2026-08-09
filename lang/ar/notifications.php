<?php

return [
    // Appointment Booked Notifications
    'appointment_booked' => [
        'subject' => 'تأكيد حجز الموعد',
        'greeting' => 'مرحباً :name،',
        'message' => 'تم حجز موعدك بنجاح!',
        'details' => 'تفاصيل الموعد:',
        'date' => 'التاريخ: :date',
        'time' => 'الوقت: :time',
        'tenant' => 'العيادة: :tenant',
        'footer' => 'شكراً لاستخدامكم خدماتنا!',
        'sms' => 'مرحباً :name، تم حجز موعدك في :date الساعة :time في :tenant',
    ],

    // Appointment Reminder Notifications
    'appointment_reminder' => [
        'subject' => 'تذكير بالموعد',
        'greeting' => 'مرحباً :name،',
        'message' => 'نذكرك بموعدك القادم!',
        'details' => 'تفاصيل الموعد:',
        'date' => 'التاريخ: :date',
        'time' => 'الوقت: :time',
        'tenant' => 'العيادة: :tenant',
        'footer' => 'نتطلع لرؤيتك!',
        'sms' => 'تذكير: موعدك في :date الساعة :time في :tenant',
    ],

    // Appointment Cancelled Notifications
    'appointment_cancelled' => [
        'subject' => 'إلغاء الموعد',
        'greeting' => 'مرحباً :name،',
        'message' => 'تم إلغاء موعدك.',
        'details' => 'تفاصيل الموعد الملغي:',
        'date' => 'التاريخ: :date',
        'time' => 'الوقت: :time',
        'tenant' => 'العيادة: :tenant',
        'footer' => 'يمكنك حجز موعد آخر في أي وقت.',
        'sms' => 'تم إلغاء موعدك في :date الساعة :time في :tenant',
    ],

    // Appointment Confirmed Notifications
    'appointment_confirmed' => [
        'subject' => 'تأكيد الموعد',
        'greeting' => 'مرحباً :name،',
        'message' => 'تم تأكيد موعدك!',
        'details' => 'تفاصيل الموعد:',
        'date' => 'التاريخ: :date',
        'time' => 'الوقت: :time',
        'tenant' => 'العيادة: :tenant',
        'footer' => 'نتطلع لرؤيتك!',
        'sms' => 'تم تأكيد موعدك في :date الساعة :time في :tenant',
    ],

    // Queue Update Notifications
    'queue_next' => [
        'subject' => 'دورك الآن!',
        'greeting' => 'مرحباً :name،',
        'message' => 'حان دورك! يرجى التوجه إلى المكتب الآن.',
        'queue_number' => 'رقم الدور: :number',
        'footer' => 'شكراً لانتظارك!',
        'sms' => 'مرحباً :name، حان دورك! رقم الدور: :number. يرجى التوجه إلى المكتب.',
    ],

    'queue_position_update' => [
        'subject' => 'تحديث موقعك في الدور',
        'greeting' => 'مرحباً :name،',
        'message' => 'تحديث لموقعك في الدور:',
        'queue_number' => 'رقم الدور: :number',
        'position' => 'عدد الأشخاص أمامك: :position',
        'estimated_wait' => 'الوقت المقدر للانتظار: :time دقيقة',
        'footer' => 'شكراً لانتظارك!',
        'sms' => 'رقم الدور: :number، أمامك :position أشخاص، الوقت المقدر: :time دقيقة',
    ],

    'queue_ready' => [
        'subject' => 'تقريباً حان دورك',
        'greeting' => 'مرحباً :name،',
        'message' => 'يرجى الاستعداد، دورك قريب جداً!',
        'queue_number' => 'رقم الدور: :number',
        'position' => 'أمامك شخص واحد فقط',
        'footer' => 'شكراً لانتظارك!',
        'sms' => 'يرجى الاستعداد! رقم الدور: :number، أمامك شخص واحد فقط.',
    ],

    'queue_skipped' => [
        'subject' => 'تم تخطي دورك',
        'greeting' => 'مرحباً :name،',
        'message' => 'تم تخطي دورك لعدم تواجدك.',
        'queue_number' => 'رقم الدور: :number',
        'footer' => 'يمكنك الحصول على دور جديد من المكتب.',
        'sms' => 'تم تخطي دورك (رقم :number) لعدم تواجدك. يمكنك الحصول على دور جديد.',
    ],

    // Common
    'view_details' => 'عرض التفاصيل',
    'thank_you' => 'شكراً لاستخدامكم خدماتنا',
    'regards' => 'مع تحياتنا',
];
