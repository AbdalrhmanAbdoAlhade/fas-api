<?php

return [
    // General
    'user_not_found' => 'Kullanıcı bulunamadı.',
    'unauthenticated' => 'Öncelikle giriş yapmalısınız.',
    'unauthorized' => 'Bu işlemi gerçekleştirmeye yetkiniz yok.',
    'not_found' => 'İstenen öğe bulunamadı.',
    'updated_successfully' => 'Başarıyla güncellendi.',
    'deleted_successfully' => 'Başarıyla silindi.',

    // Bookings
    'booking_not_found' => 'Rezervasyon bulunamadı.',
    'no_bookings_for_hotel' => 'Şu anda bu otel için rezervasyon bulunmamaktadır.',
    'room_not_linked_to_hotel' => 'Bu oda şu anda bir otelle ilişkili değil.',
    'booking_successful_payment_pending' => 'Rezervasyon başarıyla kaydedildi, lütfen ödemeyi tamamlayın.',
    'booking_creation_payment_failed' => 'Rezervasyon oluşturuldu ancak ödeme ağ geçidi bağlantısı başarısız oldu: :error',
    'payment_successful' => 'Ödeme başarılı.',
    'no_pending_booking_to_cancel' => 'İptal edilecek beklemede olan bir rezervasyon yok.',
    'cannot_cancel_confirmed_booking' => 'Onaylanan veya tamamlanan rezervasyonlar iptal edilemez. Lütfen otel ile iletişime geçin.',
    'booking_cancelled_successfully' => 'Rezervasyon başarıyla iptal edildi.',
    'room_not_found' => 'Oda bulunamadı.',
    'no_bookings_for_room' => 'Şu anda bu oda için rezervasyon bulunmamaktadır.',
    'ongoing_bookings_retrieved' => 'Devam eden rezervasyonlar başarıyla alındı.',
    'completed_bookings_retrieved' => 'Tamamlanan rezervasyonlar başarıyla alındı.',
    'cancelled_bookings_retrieved' => 'İptal edilen rezervasyonlar başarıyla alındı.',
    'status_updated_successfully' => 'Rezervasyon durumu başarıyla güncellendi.',

    // Auth & Users
    'otp_sent' => 'Doğrulama kodu (OTP) başarıyla gönderildi.',
    'otp_invalid_or_expired' => 'Doğrulama kodu geçersiz veya süresi dolmuş.',
    'otp_verified' => 'Başarıyla doğrulandı.',
    'otp_incorrect' => 'Yanlış doğrulama kodu.',
    'login_failed' => 'Geçersiz giriş bilgileri.',
    'invalid_email_or_password' => 'E-posta veya şifre hatalı.',
    'login_missing_fields' => 'Gerekli giriş alanları eksik.',
    'registration_failed' => 'Kayıt başarısız oldu.',
    'customer_registered' => 'Müşteri başarıyla kaydedildi.',
    'password_updated' => 'Şifre başarıyla güncellendi.',
    'guest_login_success' => 'Misafir girişi başarılı.',
    'user_registered' => 'Kullanıcı başarıyla kaydedildi.',
    'pending_users_list' => 'Bekleyen kullanıcılar listesi.',
    'update_failed' => 'Güncelleme başarısız oldu.',
    'role_status_updated' => 'Rol ve durum başarıyla güncellendi.',
    'logout_success' => 'Başarıyla çıkış yapıldı.',
    'profile_updated' => 'Profil başarıyla güncellendi.',
    'profile_update_error' => 'Profil güncellenirken bir hata oluştu.',
    'validation_error' => 'Doğrulama hatası.',

    // Notifications
    'device_token_saved' => 'Cihaz jetonu (token) başarıyla kaydedildi.',
    'device_token_not_found' => 'Bu kullanıcı için cihaz jetonu bulunamadı.',
    'firebase_credentials_missing' => 'Firebase kimlik bilgileri dosyası bulunamadı.',
    'notification_receipt_confirmed' => 'Alındı onayı başarılı.',
    'notification_log_not_found' => 'Bildirim günlüğü bulunamadı.',
    'notification_log_deleted' => 'Bildirim günlüğü başarıyla silindi.',

    // Hotels
    'hotel_not_found_with_rating' => 'Bu derecelendirmeye sahip otel bulunamadı.',
    'no_hotels_found_with_bookings' => 'Rezervasyonlu otel bulunamadı.',
    'user_hotels_list' => 'Belirtilen kullanıcıya ait oteller.',
    'all_hotels_list' => 'Tüm otellerin listesi.',
    'hotel_not_found' => 'Otel bulunamadı.',
    'hotel_details' => 'Otel detayları.',
    'hotel_deleted' => 'Otel başarıyla silindi.',
    'unauthorized_hotel_creation' => 'Yalnızca "Otel Sahibi", "Şirket Sahibi" veya "Yönetici" rolüne sahip kullanıcılar otel ekleyebilir.',

    // Property Bookings
    'property_not_found' => 'Mülk bulunamadı.',
    'no_bookings_for_property' => 'Şu anda bu mülk için rezervasyon bulunmamaktadır.',
    'cannot_cancel_non_owned_booking' => 'Size ait olmadığı veya mevcut olmadığı için bu rezervasyonu iptal edemezsiniz.',

    // Offer Bookings
    'unauthorized_offer_booking_update' => 'Bu rezervasyonu güncelleme yetkiniz yok.',
    'login_to_update_offer_booking' => 'Rezervasyon durumunu güncellemek için giriş yapmalısınız.',

    // Rooms
    'no_rooms_for_hotel' => 'Bu otel için oda bulunamadı veya otel mevcut değil.',
    'room_deleted' => 'Oda başarıyla silindi.',

    // Properties
    'unauthorized_property_creation' => 'Yalnızca mülk sahipleri veya yöneticiler mülk ekleyebilir.',
    'property_created' => 'Mülk başarıyla eklendi.',
    'unauthorized_property_update' => 'Bu mülkü güncelleme yetkiniz yok.',
    'property_updated' => 'Mülk başarıyla güncellendi.',
    'no_properties_with_bookings' => 'Rezervasyonlu mülk bulunamadı.',
    'no_properties_with_rating' => 'Bu derecelendirmeye sahip mülk bulunamadı.',
    'no_nearby_properties' => 'Yakın mülk bulunamadı.',
    'unauthorized_property_delete' => 'Bu mülkü silme yetkiniz yok.',

    // Companies
    'company_not_found' => 'Şirket bulunamadı.',
    'company_created' => 'Şirket başarıyla oluşturuldu.',
    'unauthorized_company_update' => 'Bu şirketi güncelleme yetkiniz yok.',
    'company_updated' => 'Şirket bilgileri başarıyla güncellendi.',
    'unauthorized_company_delete' => 'Bu şirketi silme yetkiniz yok.',

    // Services
    'image_required' => 'Resim gereklidir.',
    'service_not_found' => 'Hizmet bulunamadı.',
    'service_created' => 'Hizmet başarıyla oluşturuldu.',
    'service_updated' => 'Hizmet başarıyla güncellendi.',
    'service_deleted' => 'Hizmet başarıyla silindi.',
    'service_request_not_found' => 'Hizmet talebi bulunamadı.',
    'service_request_status_updated' => 'Hizmet talebi durumu başarıyla güncellendi.',
    'service_request_deleted' => 'Hizmet talebi başarıyla silindi.',
    'service_request_created' => 'Hizmet talebi başarıyla oluşturuldu.',
    'hotel_not_linked_to_user' => 'Bu kullanıcıyla ilişkili otel bulunamadı.',

    // Coordinators & Tracking Links
    'coordinator_deleted' => 'Koordinatör başarıyla silindi.',
    'save_data_error' => 'Veriler kaydedilirken bir hata oluştu.',
    'tracking_link_not_found' => 'İzleme bağlantısı bulunamadı.',
    'tracking_links_fetched' => 'İzleme bağlantıları başarıyla getirildi.',
    'invalid_url' => 'URL geçersiz.',
    'tracking_link_archived' => 'İzleme bağlantısı başarıyla arşivlendi.',
    'tracking_link_unarchived' => 'İzleme bağlantısının arşivi başarıyla kaldırıldı.',
    'tracking_link_updated' => 'İzleme bağlantısı başarıyla güncellendi.',

    // Reviews
    'review_added' => 'Değerlendirme başarıyla eklendi.',
    'invalid_type' => 'Geçersiz tür.',
    'unauthorized_review_delete' => 'Bu değerlendirmeyi silme yetkiniz yok.',
    'review_deleted' => 'Değerlendirme başarıyla silindi.',

    // Payments
    'payment_created' => 'Ödeme işlemi başarıyla oluşturuldu.',
    'payment_processing' => 'Ödeme işleniyor.',
    'payment_failed_reason' => 'Ödeme başarısız — :reason',
    'payment_refunded' => 'Ödeme iade edildi.',
    'payment_not_found' => 'Ödeme bulunamadı.',
    'payment_record_not_found' => 'Ödeme kaydı bulunamadı.',
    'missing_order_id' => 'Sipariş numarası eksik.',
    'merchant_settings_not_found' => 'Satıcı ayarları bulunamadı.',
    'invalid_hash' => 'Dijital imza geçersiz.',
    'webhook_processed' => 'Webhook başarıyla işlendi.',
    'merchant_payment_settings_exist' => 'Zaten kayıtlı ödeme ayarlarınız var.',
    'merchant_payment_settings_user_exist' => 'Bu kullanıcının zaten kayıtlı ödeme ayarları var.',

    // Notification Logs
    'notification_log_show_not_found' => 'Bildirim bulunamadı.',
    'notification_deleted' => 'Bildirim başarıyla silindi.',

    // Property Types
    'property_type_created' => 'Başarıyla oluşturuldu.',
    'property_type_updated' => 'Başarıyla güncellendi.',
    'property_type_deleted' => 'Başarıyla silindi.',

    // Booking Actions
    'booking_deleted' => 'Rezervasyon başarıyla silindi.',
    'booking_updated' => 'Rezervasyon verileri başarıyla güncellendi.',
    'unauthorized_booking_status_update' => 'Bu rezervasyonun durumunu güncelleme yetkiniz yok.',

    // Offers
    'unauthorized_offer_creation' => 'Teklif oluşturma yetkiniz yok.',
    'unauthorized_offer_update' => 'Bu teklifi güncelleme yetkiniz yok.',
    'unauthorized_offer_delete' => 'Bu teklifi silme yetkiniz yok.',
    'offer_deleted' => 'Teklif başarıyla silindi.',
];
