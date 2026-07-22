<?php

return [
    // General
    'user_not_found' => 'المستخدم غير موجود.',
    'unauthenticated' => 'يجب عليك تسجيل الدخول أولاً.',
    'unauthorized' => 'غير مصرح لك بإجراء هذه العملية.',
    'not_found' => 'العنصر المطلوب غير موجود.',
    'updated_successfully' => 'تم التحديث بنجاح.',
    'deleted_successfully' => 'تم الحذف بنجاح.',

    // Bookings
    'booking_not_found' => 'الحجز غير موجود.',
    'no_bookings_for_hotel' => 'لا توجد حجوزات حالياً لهذا الفندق.',
    'room_not_linked_to_hotel' => 'هذه الغرفة غير مرتبطة بفندق حالياً.',
    'booking_successful_payment_pending' => 'تم تسجيل الحجز بنجاح، يرجى إكمال عملية الدفع.',
    'booking_creation_payment_failed' => 'تم إنشاء الحجز، ولكن فشل الاتصال ببوابة الدفع: :error',
    'payment_successful' => 'تمت عملية الدفع بنجاح.',
    'no_pending_booking_to_cancel' => 'لا يوجد حجز قيد الانتظار لإلغائه.',
    'cannot_cancel_confirmed_booking' => 'لا يمكن إلغاء الحجز بعد تأكيده أو اكتماله. يرجى التواصل مع الفندق.',
    'booking_cancelled_successfully' => 'تم إلغاء الحجز بنجاح.',
    'room_not_found' => 'الغرفة غير موجودة.',
    'no_bookings_for_room' => 'لا توجد حجوزات لهذه الغرفة في الوقت الحالي.',
    'ongoing_bookings_retrieved' => 'تم استرجاع الحجوزات الجارية بنجاح.',
    'completed_bookings_retrieved' => 'تم استرجاع الحجوزات المكتملة بنجاح.',
    'cancelled_bookings_retrieved' => 'تم استرجاع الحجوزات الملغاة بنجاح.',
    'status_updated_successfully' => 'تم تحديث حالة الحجز بنجاح.',

    // Auth & Users
    'otp_sent' => 'تم إرسال رمز التحقق (OTP) بنجاح.',
    'otp_invalid_or_expired' => 'رمز التحقق (OTP) غير صالح أو منتهي الصلاحية.',
    'otp_verified' => 'تم التحقق بنجاح.',
    'otp_incorrect' => 'رمز التحقق (OTP) غير صحيح.',
    'login_failed' => 'بيانات الدخول غير صحيحة.',
    'invalid_email_or_password' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة.',
    'login_missing_fields' => 'بيانات الدخول غير مكتملة.',
    'registration_failed' => 'فشل عملية التسجيل.',
    'customer_registered' => 'تم تسجيل العميل بنجاح.',
    'password_updated' => 'تم تحديث كلمة المرور بنجاح.',
    'guest_login_success' => 'تم تسجيل دخول الزائر بنجاح.',
    'user_registered' => 'تم تسجيل المستخدم بنجاح.',
    'pending_users_list' => 'قائمة المستخدمين قيد الانتظار.',
    'update_failed' => 'فشل التحديث.',
    'role_status_updated' => 'تم تحديث الصلاحية والحالة بنجاح.',
    'logout_success' => 'تم تسجيل الخروج بنجاح.',
    'profile_updated' => 'تم تحديث الملف الشخصي بنجاح.',
    'profile_update_error' => 'حدث خطأ أثناء تحديث الملف الشخصي.',
    'validation_error' => 'خطأ في التحقق من صحة البيانات.',

    // Notifications
    'device_token_saved' => 'تم حفظ توكن الجهاز بنجاح.',
    'device_token_not_found' => 'توكن الجهاز غير موجود لهذا المستخدم.',
    'firebase_credentials_missing' => 'ملف بيانات اعتماد Firebase غير موجود.',
    'notification_receipt_confirmed' => 'تم تأكيد الاستلام.',
    'notification_log_not_found' => 'سجل الإشعارات غير موجود.',
    'notification_log_deleted' => 'تم حذف سجل الإشعارات بنجاح.',

    // Hotels
    'hotel_not_found_with_rating' => 'لم يتم العثور على فنادق بهذا التقييم.',
    'no_hotels_found_with_bookings' => 'لم يتم العثور على فنادق بها حجوزات.',
    'user_hotels_list' => 'قائمة فنادق المستخدم المحدد.',
    'all_hotels_list' => 'قائمة بجميع الفنادق.',
    'hotel_not_found' => 'الفندق غير موجود.',
    'hotel_details' => 'تفاصيل الفندق.',
    'hotel_deleted' => 'تم حذف الفندق بنجاح.',
    'unauthorized_hotel_creation' => 'يحق فقط للمستخدمين بصلاحية "مالك فندق" أو "مالك شركة" أو "مدير" إضافة فندق.',

    // Property Bookings
    'property_not_found' => 'العقار غير موجود.',
    'no_bookings_for_property' => 'لا توجد حجوزات حالياً لهذا العقار.',
    'cannot_cancel_non_owned_booking' => 'لا يمكنك إلغاء هذا الحجز لأنه لا يخصك أو غير موجود أصلاً.',

    // Offer Bookings
    'unauthorized_offer_booking_update' => 'غير مصرح لك بتحديث هذا الحجز.',
    'login_to_update_offer_booking' => 'يجب عليك تسجيل الدخول لتحديث حالة الحجز.',

    // Rooms
    'no_rooms_for_hotel' => 'لا توجد غرف لهذا الفندق أو الفندق غير موجود.',
    'room_deleted' => 'تم حذف الغرفة بنجاح.',

    // Properties
    'unauthorized_property_creation' => 'فقط المالكين أو الأدمن يمكنهم إضافة عقار.',
    'property_created' => 'تم إضافة العقار بنجاح.',
    'unauthorized_property_update' => 'غير مصرح لك بتحديث هذا العقار.',
    'property_updated' => 'تم تحديث العقار بنجاح.',
    'no_properties_with_bookings' => 'لم يتم العثور على عقارات بها حجوزات.',
    'no_properties_with_rating' => 'لم يتم العثور على عقارات بهذا التقييم.',
    'no_nearby_properties' => 'لم يتم العثور على عقارات قريبة.',
    'unauthorized_property_delete' => 'غير مصرح لك بحذف هذا العقار.',

    // Companies
    'company_not_found' => 'الشركة غير موجودة.',
    'company_created' => 'تم إنشاء الشركة بنجاح.',
    'unauthorized_company_update' => 'غير مصرح لك بتعديل هذه الشركة.',
    'company_updated' => 'تم تحديث بيانات الشركة بنجاح.',
    'unauthorized_company_delete' => 'غير مصرح لك بحذف هذه الشركة.',

    // Services
    'image_required' => 'الصورة مطلوبة.',
    'service_not_found' => 'الخدمة غير موجودة.',
    'service_created' => 'تم إنشاء الخدمة بنجاح.',
    'service_updated' => 'تم تحديث الخدمة بنجاح.',
    'service_deleted' => 'تم حذف الخدمة بنجاح.',
    'service_request_not_found' => 'طلب الخدمة غير موجود.',
    'service_request_status_updated' => 'تم تحديث حالة طلب الخدمة بنجاح.',
    'service_request_deleted' => 'تم حذف طلب الخدمة بنجاح.',
    'service_request_created' => 'تم إنشاء الطلب بنجاح.',
    'hotel_not_linked_to_user' => 'لم يتم العثور على فندق مرتبط بهذا المستخدم.',

    // Coordinators & Tracking Links
    'coordinator_deleted' => 'تم حذف المنسق بنجاح.',
    'save_data_error' => 'حدث خطأ أثناء حفظ البيانات.',
    'tracking_link_not_found' => 'رابط التتبع غير موجود.',
    'tracking_links_fetched' => 'تم جلب الروابط بنجاح.',
    'invalid_url' => 'الرابط غير صحيح.',
    'tracking_link_archived' => 'تمت أرشفة الرابط بنجاح.',
    'tracking_link_unarchived' => 'تم إلغاء أرشفة الرابط بنجاح.',
    'tracking_link_updated' => 'تم تحديث الرابط بنجاح.',

    // Reviews
    'review_added' => 'تم إضافة التقييم بنجاح.',
    'invalid_type' => 'نوع غير صالح.',
    'unauthorized_review_delete' => 'غير مصرح لك بحذف هذا التقييم.',
    'review_deleted' => 'تم حذف التقييم بنجاح.',

    // Payments
    'payment_created' => 'تم إنشاء عملية الدفع بنجاح.',
    'payment_processing' => 'الدفع قيد المعالجة.',
    'payment_failed_reason' => 'فشل الدفع — :reason',
    'payment_refunded' => 'تم الاسترداد.',
    'payment_not_found' => 'عملية الدفع غير موجودة.',
    'payment_record_not_found' => 'سجل الدفع غير موجود.',
    'missing_order_id' => 'رقم الطلب مفقود.',
    'merchant_settings_not_found' => 'إعدادات التاجر غير موجودة.',
    'invalid_hash' => 'التوقيع الرقمي غير صحيح.',
    'webhook_processed' => 'تمت معالجة الـ Webhook بنجاح.',
    'merchant_payment_settings_exist' => 'لديك إعدادات دفع مسجلة مسبقاً.',
    'merchant_payment_settings_user_exist' => 'هذا المستخدم لديه إعدادات دفع مسجلة مسبقاً.',

    // Notification Logs
    'notification_log_show_not_found' => 'لم يتم العثور على الإشعار.',
    'notification_deleted' => 'تم حذف الإشعار بنجاح.',

    // Property Types
    'property_type_created' => 'تم الإنشاء بنجاح.',
    'property_type_updated' => 'تم التحديث بنجاح.',
    'property_type_deleted' => 'تم الحذف بنجاح.',

    // Booking Actions
    'booking_deleted' => 'تم حذف الحجز بنجاح.',
    'booking_updated' => 'تم تحديث بيانات الحجز بنجاح.',
    'unauthorized_booking_status_update' => 'غير مصرح لك بتعديل حالة هذا الحجز.',

    // Offers
    'unauthorized_offer_creation' => 'غير مصرح لك بإنشاء عرض.',
    'unauthorized_offer_update' => 'غير مصرح لك بتعديل هذا العرض.',
    'unauthorized_offer_delete' => 'غير مصرح لك بحذف هذا العرض.',
    'offer_deleted' => 'تم حذف العرض بنجاح.',
];
