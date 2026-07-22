<?php

return [
    // General
    'user_not_found' => 'Pengguna tidak ditemukan.',
    'unauthenticated' => 'Anda harus masuk terlebih dahulu.',
    'unauthorized' => 'Anda tidak diizinkan untuk melakukan tindakan ini.',
    'not_found' => 'Item yang diminta tidak ditemukan.',
    'updated_successfully' => 'Berhasil diperbarui.',
    'deleted_successfully' => 'Berhasil dihapus.',

    // Bookings
    'booking_not_found' => 'Pemesanan tidak ditemukan.',
    'no_bookings_for_hotel' => 'Saat ini tidak ada pemesanan untuk hotel ini.',
    'room_not_linked_to_hotel' => 'Kamar ini saat ini tidak terkait dengan hotel mana pun.',
    'booking_successful_payment_pending' => 'Pemesanan berhasil didaftarkan, silakan selesaikan pembayaran.',
    'booking_creation_payment_failed' => 'Pemesanan dibuat, tetapi koneksi ke gateway pembayaran gagal: :error',
    'payment_successful' => 'Pembayaran berhasil.',
    'no_pending_booking_to_cancel' => 'Tidak ada pemesanan tertunda untuk dibatalkan.',
    'cannot_cancel_confirmed_booking' => 'Pemesanan tidak dapat dibatalkan setelah dikonfirmasi atau selesai. Silakan hubungi pihak hotel.',
    'booking_cancelled_successfully' => 'Pemesanan berhasil dibatalkan.',
    'room_not_found' => 'Kamar tidak ditemukan.',
    'no_bookings_for_room' => 'Tidak ada pemesanan untuk kamar ini saat ini.',
    'ongoing_bookings_retrieved' => 'Pemesanan yang sedang berlangsung berhasil diambil.',
    'completed_bookings_retrieved' => 'Pemesanan yang selesai berhasil diambil.',
    'cancelled_bookings_retrieved' => 'Pemesanan yang dibatalkan berhasil diambil.',
    'status_updated_successfully' => 'Status pemesanan berhasil diperbarui.',

    // Auth & Users
    'otp_sent' => 'OTP berhasil dikirim.',
    'otp_invalid_or_expired' => 'OTP tidak valid atau telah kedaluwarsa.',
    'otp_verified' => 'Berhasil diverifikasi.',
    'otp_incorrect' => 'OTP salah.',
    'login_failed' => 'Kredensial tidak valid.',
    'invalid_email_or_password' => 'Email atau kata sandi salah.',
    'login_missing_fields' => 'Kolom login yang diperlukan tidak lengkap.',
    'registration_failed' => 'Pendaftaran gagal.',
    'customer_registered' => 'Pelanggan berhasil didaftarkan.',
    'password_updated' => 'Kata sandi berhasil diperbarui.',
    'guest_login_success' => 'Tamu berhasil masuk.',
    'user_registered' => 'Pengguna berhasil didaftarkan.',
    'pending_users_list' => 'Daftar pengguna yang tertunda.',
    'update_failed' => 'Pembaruan gagal.',
    'role_status_updated' => 'Peran dan status berhasil diperbarui.',
    'logout_success' => 'Berhasil keluar.',
    'profile_updated' => 'Profil berhasil diperbarui.',
    'profile_update_error' => 'Terjadi kesalahan saat memperbarui profil.',
    'validation_error' => 'Kesalahan validasi.',

    // Notifications
    'device_token_saved' => 'Token perangkat berhasil disimpan.',
    'device_token_not_found' => 'Token perangkat tidak ditemukan untuk pengguna ini.',
    'firebase_credentials_missing' => 'File kredensial Firebase tidak ditemukan.',
    'notification_receipt_confirmed' => 'Penerimaan dikonfirmasi.',
    'notification_log_not_found' => 'Log notifikasi tidak ditemukan.',
    'notification_log_deleted' => 'Log notifikasi berhasil dihapus.',

    // Hotels
    'hotel_not_found_with_rating' => 'Tidak ada hotel yang ditemukan dengan peringkat ini.',
    'no_hotels_found_with_bookings' => 'Tidak ada hotel dengan pemesanan yang ditemukan.',
    'user_hotels_list' => 'Hotel untuk pengguna yang ditentukan.',
    'all_hotels_list' => 'Daftar semua hotel.',
    'hotel_not_found' => 'Hotel tidak ditemukan.',
    'hotel_details' => 'Detail hotel.',
    'hotel_deleted' => 'Hotel berhasil dihapus.',
    'unauthorized_hotel_creation' => 'Hanya pengguna dengan peran "Pemilik Hotel", "Pemilik Perusahaan", atau "Admin" yang dapat menambahkan hotel.',

    // Property Bookings
    'property_not_found' => 'Properti tidak ditemukan.',
    'no_bookings_for_property' => 'Saat ini tidak ada pemesanan untuk properti ini.',
    'cannot_cancel_non_owned_booking' => 'Anda tidak dapat membatalkan pemesanan ini karena bukan milik Anda atau tidak ada.',

    // Offer Bookings
    'unauthorized_offer_booking_update' => 'Anda tidak diizinkan untuk memperbarui pemesanan ini.',
    'login_to_update_offer_booking' => 'Anda harus masuk untuk memperbarui status pemesanan.',

    // Rooms
    'no_rooms_for_hotel' => 'Tidak ada kamar yang ditemukan untuk hotel ini atau hotel tidak ada.',
    'room_deleted' => 'Kamar berhasil dihapus.',

    // Properties
    'unauthorized_property_creation' => 'Hanya pemilik properti atau admin yang dapat menambahkan properti.',
    'property_created' => 'Properti berhasil ditambahkan.',
    'unauthorized_property_update' => 'Anda tidak diizinkan untuk memperbarui properti ini.',
    'property_updated' => 'Properti berhasil diperbarui.',
    'no_properties_with_bookings' => 'Tidak ada properti dengan pemesanan yang ditemukan.',
    'no_properties_with_rating' => 'Tidak ada properti dengan peringkat ini yang ditemukan.',
    'no_nearby_properties' => 'Tidak ada properti terdekat yang ditemukan.',
    'unauthorized_property_delete' => 'Anda tidak diizinkan untuk menghapus properti ini.',

    // Companies
    'company_not_found' => 'Perusahaan tidak ditemukan.',
    'company_created' => 'Perusahaan berhasil dibuat.',
    'unauthorized_company_update' => 'Anda tidak diizinkan untuk memperbarui perusahaan ini.',
    'company_updated' => 'Informasi perusahaan berhasil diperbarui.',
    'unauthorized_company_delete' => 'Anda tidak diizinkan untuk menghapus perusahaan ini.',

    // Services
    'image_required' => 'Gambar wajib diisi.',
    'service_not_found' => 'Layanan tidak ditemukan.',
    'service_created' => 'Layanan berhasil dibuat.',
    'service_updated' => 'Layanan berhasil diperbarui.',
    'service_deleted' => 'Layanan berhasil dihapus.',
    'service_request_not_found' => 'Permintaan layanan tidak ditemukan.',
    'service_request_status_updated' => 'Status permintaan layanan berhasil diperbarui.',
    'service_request_deleted' => 'Permintaan layanan berhasil dihapus.',
    'service_request_created' => 'Permintaan layanan berhasil dibuat.',
    'hotel_not_linked_to_user' => 'Tidak ada hotel yang terkait dengan pengguna ini.',

    // Coordinators & Tracking Links
    'coordinator_deleted' => 'Koordinator berhasil dihapus.',
    'save_data_error' => 'Terjadi kesalahan saat menyimpan data.',
    'tracking_link_not_found' => 'Tautan pelacak tidak ditemukan.',
    'tracking_links_fetched' => 'Tautan pelacak berhasil diambil.',
    'invalid_url' => 'URL tidak valid.',
    'tracking_link_archived' => 'Tautan pelacak berhasil diarsipkan.',
    'tracking_link_unarchived' => 'Arsip tautan pelacak berhasil dibatalkan.',
    'tracking_link_updated' => 'Tautan pelacak berhasil diperbarui.',

    // Reviews
    'review_added' => 'Ulasan berhasil ditambahkan.',
    'invalid_type' => 'Jenis tidak valid.',
    'unauthorized_review_delete' => 'Anda tidak diizinkan untuk menghapus ulasan ini.',
    'review_deleted' => 'Ulasan berhasil dihapus.',

    // Payments
    'payment_created' => 'Proses pembayaran berhasil dibuat.',
    'payment_processing' => 'Pembayaran sedang diproses.',
    'payment_failed_reason' => 'Pembayaran gagal — :reason',
    'payment_refunded' => 'Pembayaran berhasil dikembalikan.',
    'payment_not_found' => 'Pembayaran tidak ditemukan.',
    'payment_record_not_found' => 'Catatan pembayaran tidak ditemukan.',
    'missing_order_id' => 'ID pesanan tidak ada.',
    'merchant_settings_not_found' => 'Pengaturan pedagang tidak ditemukan.',
    'invalid_hash' => 'Tanda tangan digital tidak valid.',
    'webhook_processed' => 'Webhook berhasil diproses.',
    'merchant_payment_settings_exist' => 'Anda sudah memiliki pengaturan pembayaran yang terdaftar.',
    'merchant_payment_settings_user_exist' => 'Pengguna ini sudah memiliki pengaturan pembayaran yang terdaftar.',

    // Notification Logs
    'notification_log_show_not_found' => 'Notifikasi tidak ditemukan.',
    'notification_deleted' => 'Notifikasi berhasil dihapus.',

    // Property Types
    'property_type_created' => 'Berhasil dibuat.',
    'property_type_updated' => 'Berhasil diperbarui.',
    'property_type_deleted' => 'Berhasil dihapus.',

    // Booking Actions
    'booking_deleted' => 'Pemesanan berhasil dihapus.',
    'booking_updated' => 'Data pemesanan berhasil diperbarui.',
    'unauthorized_booking_status_update' => 'Anda tidak diizinkan untuk memperbarui status pemesanan ini.',

    // Offers
    'unauthorized_offer_creation' => 'Anda tidak diizinkan untuk membuat penawaran.',
    'unauthorized_offer_update' => 'Anda tidak diizinkan untuk memperbarui penawaran ini.',
    'unauthorized_offer_delete' => 'Anda tidak diizinkan untuk menghapus penawaran ini.',
    'offer_deleted' => 'Penawaran berhasil dihapus.',
];
