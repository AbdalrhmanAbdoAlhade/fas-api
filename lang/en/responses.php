<?php

return [
    // General
    'user_not_found' => 'User not found.',
    'unauthenticated' => 'You must be logged in first.',
    'unauthorized' => 'You are not authorized to perform this action.',
    'not_found' => 'The requested item was not found.',
    'updated_successfully' => 'Updated successfully.',
    'deleted_successfully' => 'Deleted successfully.',

    // Bookings
    'booking_not_found' => 'Booking not found.',
    'no_bookings_for_hotel' => 'There are currently no bookings for this hotel.',
    'room_not_linked_to_hotel' => 'This room is not currently associated with a hotel.',
    'booking_successful_payment_pending' => 'Booking has been registered successfully, please complete the payment.',
    'booking_creation_payment_failed' => 'Booking was created, but connection to the payment gateway failed: :error',
    'payment_successful' => 'Payment successful.',
    'no_pending_booking_to_cancel' => 'There is no pending booking to cancel.',
    'cannot_cancel_confirmed_booking' => 'The booking cannot be canceled after it has been confirmed or completed. Please contact the hotel.',
    'booking_cancelled_successfully' => 'Booking has been canceled successfully.',
    'room_not_found' => 'Room not found.',
    'no_bookings_for_room' => 'There are no bookings for this room at the moment.',
    'ongoing_bookings_retrieved' => 'Ongoing bookings retrieved successfully.',
    'completed_bookings_retrieved' => 'Completed bookings retrieved successfully.',
    'cancelled_bookings_retrieved' => 'Canceled bookings retrieved successfully.',
    'status_updated_successfully' => 'Booking status updated successfully.',

    // Auth & Users
    'otp_sent' => 'OTP sent successfully.',
    'otp_invalid_or_expired' => 'OTP is invalid or has expired.',
    'otp_verified' => 'Successfully verified.',
    'otp_incorrect' => 'Incorrect OTP.',
    'login_failed' => 'Invalid credentials.',
    'invalid_email_or_password' => 'Invalid email or password.',
    'login_missing_fields' => 'Missing required login fields.',
    'registration_failed' => 'Registration failed.',
    'customer_registered' => 'Customer registered successfully.',
    'password_updated' => 'Password updated successfully.',
    'guest_login_success' => 'Guest logged in successfully.',
    'user_registered' => 'User registered successfully.',
    'pending_users_list' => 'List of pending users.',
    'update_failed' => 'Update failed.',
    'role_status_updated' => 'Role and status updated successfully.',
    'logout_success' => 'Logged out successfully.',
    'profile_updated' => 'Profile updated successfully.',
    'profile_update_error' => 'An error occurred while updating the profile.',
    'validation_error' => 'Validation error.',

    // Notifications
    'device_token_saved' => 'Device token saved successfully.',
    'device_token_not_found' => 'Device token not found for this user.',
    'firebase_credentials_missing' => 'Firebase credentials file not found.',
    'notification_receipt_confirmed' => 'Receipt confirmed.',
    'notification_log_not_found' => 'Notification log not found.',
    'notification_log_deleted' => 'Notification log deleted successfully.',

    // Hotels
    'hotel_not_found_with_rating' => 'No hotels found with this rating.',
    'no_hotels_found_with_bookings' => 'No hotels found with bookings.',
    'user_hotels_list' => 'Hotels for the specified user.',
    'all_hotels_list' => 'List of all hotels.',
    'hotel_not_found' => 'Hotel not found.',
    'hotel_details' => 'Hotel details.',
    'hotel_deleted' => 'Hotel deleted successfully.',
    'unauthorized_hotel_creation' => 'Only users with "hotel_owner", "company_owner", or "admin" role can add a hotel.',

    // Property Bookings
    'property_not_found' => 'Property not found.',
    'no_bookings_for_property' => 'There are currently no bookings for this property.',
    'cannot_cancel_non_owned_booking' => 'You cannot cancel this booking because it does not belong to you or does not exist.',

    // Offer Bookings
    'unauthorized_offer_booking_update' => 'You are not authorized to update this booking.',
    'login_to_update_offer_booking' => 'You must be logged in to update the booking status.',

    // Rooms
    'no_rooms_for_hotel' => 'No rooms found for this hotel or the hotel does not exist.',
    'room_deleted' => 'Room deleted successfully.',

    // Properties
    'unauthorized_property_creation' => 'Only property owners or admins can add a property.',
    'property_created' => 'Property added successfully.',
    'unauthorized_property_update' => 'You are not authorized to update this property.',
    'property_updated' => 'Property updated successfully.',
    'no_properties_with_bookings' => 'No properties with bookings were found.',
    'no_properties_with_rating' => 'No properties found with this rating.',
    'no_nearby_properties' => 'No nearby properties were found.',
    'unauthorized_property_delete' => 'You are not authorized to delete this property.',

    // Companies
    'company_not_found' => 'Company not found.',
    'company_created' => 'Company created successfully.',
    'unauthorized_company_update' => 'You are not authorized to update this company.',
    'company_updated' => 'Company information updated successfully.',
    'unauthorized_company_delete' => 'You are not authorized to delete this company.',

    // Services
    'image_required' => 'Image is required.',
    'service_not_found' => 'Service not found.',
    'service_created' => 'Service created successfully.',
    'service_updated' => 'Service updated successfully.',
    'service_deleted' => 'Service deleted successfully.',
    'service_request_not_found' => 'Service request not found.',
    'service_request_status_updated' => 'Service request status updated successfully.',
    'service_request_deleted' => 'Service request deleted successfully.',
    'service_request_created' => 'Service request created successfully.',
    'hotel_not_linked_to_user' => 'No hotel found associated with this user.',

    // Coordinators & Tracking Links
    'coordinator_deleted' => 'Coordinator deleted successfully.',
    'save_data_error' => 'An error occurred while saving the data.',
    'tracking_link_not_found' => 'Tracking link not found.',
    'tracking_links_fetched' => 'Tracking links fetched successfully.',
    'invalid_url' => 'The URL is invalid.',
    'tracking_link_archived' => 'Tracking link archived successfully.',
    'tracking_link_unarchived' => 'Tracking link unarchived successfully.',
    'tracking_link_updated' => 'Tracking link updated successfully.',

    // Reviews
    'review_added' => 'Review added successfully.',
    'invalid_type' => 'Invalid type.',
    'unauthorized_review_delete' => 'You are not authorized to delete this review.',
    'review_deleted' => 'Review deleted successfully.',

    // Payments
    'payment_created' => 'Payment process created successfully.',
    'payment_processing' => 'Payment is being processed.',
    'payment_failed_reason' => 'Payment failed — :reason',
    'payment_refunded' => 'Refunded successfully.',
    'payment_not_found' => 'Payment not found.',
    'payment_record_not_found' => 'Payment record not found.',
    'missing_order_id' => 'Order ID is missing.',
    'merchant_settings_not_found' => 'Merchant settings not found.',
    'invalid_hash' => 'Invalid digital signature.',
    'webhook_processed' => 'Webhook processed successfully.',
    'merchant_payment_settings_exist' => 'You already have payment settings registered.',
    'merchant_payment_settings_user_exist' => 'This user already has payment settings registered.',

    // Notification Logs
    'notification_log_show_not_found' => 'Notification not found.',
    'notification_deleted' => 'Notification deleted successfully.',

    // Property Types
    'property_type_created' => 'Created successfully.',
    'property_type_updated' => 'Updated successfully.',
    'property_type_deleted' => 'Deleted successfully.',

    // Booking Actions
    'booking_deleted' => 'Booking deleted successfully.',
    'booking_updated' => 'Booking data updated successfully.',
    'unauthorized_booking_status_update' => 'You are not authorized to update this booking status.',

    // Offers
    'unauthorized_offer_creation' => 'You are not authorized to create an offer.',
    'unauthorized_offer_update' => 'You are not authorized to update this offer.',
    'unauthorized_offer_delete' => 'You are not authorized to delete this offer.',
    'offer_deleted' => 'Offer deleted successfully.',
];
