<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PatientAuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DoctorBookingController;
use App\Http\Controllers\Api\FamilyController;
use App\Http\Controllers\Api\MyBookingsController;
use App\Http\Controllers\Api\BookingHistoryController;
use App\Http\Controllers\Api\MembershipController;
use App\Http\Controllers\Api\WellnessController;
use App\Http\Controllers\Api\NotificationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
//Tablet Registration API for Patients
Route::post('patient/tab_register', [PatientAuthController::class, 'patientTabRegister']); //new

// All 
Route::post('patient/register', [PatientAuthController::class, 'patientRegister']);
Route::post('patient/login', [PatientAuthController::class, 'patientLogin']);
Route::post('patient/otp_verification', [PatientAuthController::class, 'otpVerification']);
Route::post('patient/resend_otp', [PatientAuthController::class, 'reSendOtp']);
Route::post('patient/forgot_password', [PatientAuthController::class, 'forgotPassword']);
Route::post('patient/reset_password', [PatientAuthController::class, 'resetPassword']);

Route::post('patient/membership_packages_details', [MembershipController::class, 'membershipPackageDetails']);

Route::get('branches', [DoctorBookingController::class, 'getBranches']);
Route::get('qualifications', [DoctorBookingController::class, 'getQualifications']);
Route::get('gender', [DoctorBookingController::class, 'getGender']);
Route::get('relationship', [DoctorBookingController::class, 'getRelationship']);
Route::get('blood-group', [DoctorBookingController::class, 'getBloodGroup']);
Route::get('booking_types', [DoctorBookingController::class, 'getBookingType']);
Route::post('booking-status', [DoctorBookingController::class, 'bookingStatus']);
Route::get('marital_status', [DoctorBookingController::class, 'maritalStatus']); //new

Route::middleware(['auth:api'])->group(function () {
    // Consultation
    Route::get('patient/home', [DashboardController::class, 'homePage']);

    // Route::post('patient/consultation/doctors_list', [DoctorBookingController::class, 'doctorsList']);
    Route::post('patient/consultation/doctors_details', [DoctorBookingController::class, 'doctorsDetails']);
    Route::post('patient/consultation/doctor_availability', [DoctorBookingController::class, 'doctorsAvailability']);
    Route::post('patient/consultation/booking_details', [DoctorBookingController::class, 'bookingDetails']);
    Route::post('patient/consultation/booking_summary', [DoctorBookingController::class, 'bookingSummary']);
    Route::post('patient/consultation/booking_confirmation', [DoctorBookingController::class, 'bookingConfirmation']);
    Route::post('patient/slot/availability', [DoctorBookingController::class, 'slotAvailability']);

    // Add family member
    Route::post('patient/my_family', [FamilyController::class, 'myFamily']);
    Route::post('patient/add_member', [FamilyController::class, 'addMember']);
    Route::post('patient/member/otp_verification', [FamilyController::class, 'otpVerification']);
    Route::post('patient/member/resend_otp', [FamilyController::class, 'reSendOtp']);
    Route::post('patient/member/edit', [FamilyController::class, 'editFamilyMember']);
    Route::post('patient/member/update', [FamilyController::class, 'updateFamilyMember']);
    Route::post('patient/member/delete', [FamilyController::class, 'deleteFamilyMember']);

    // Manage Bookings 
    Route::post('patient/my_bookings', [MyBookingsController::class, 'myBookings']);
    Route::post('upcoming_booking_details/consultation', [MyBookingsController::class, 'consultationBookingDetails']);
    Route::post('upcoming_booking_details/wellness', [MyBookingsController::class, 'wellnessBookingDetails']);
    Route::post('patient/cancel_booking', [MyBookingsController::class, 'cancelBooking']);
    Route::post('patient/my_booking_details', [MyBookingsController::class,'myBookingDetails']);

    // booking history
    Route::post('patient/my_booking_history', [BookingHistoryController::class, 'myBookingHistory']);
    Route::post('booking_history_details/consultation', [BookingHistoryController::class, 'consultationBookingDetails']);
    Route::post('booking_history_details/wellness', [BookingHistoryController::class, 'wellnessBookingDetails']);
    Route::post('patient/booking_history_details', [BookingHistoryController::class,'bookingHistoryDetails']);
    
    // Profile 
    Route::post('patient/get_user_details', [PatientAuthController::class, 'getUserDetails']);
    Route::post('patient/update_details', [PatientAuthController::class, 'updateDetails']);
    Route::post('patient/change_password', [PatientAuthController::class, 'changePassword']);
    Route::get('patient/logout', [PatientAuthController::class, 'logout']);

    // Membership
    // Route::get('patient/membership_packages', [MembershipController::class, 'membershipPackages']);
    // Route::post('patient/membership_packages_details', [MembershipController::class, 'membershipPackageDetails']);
    Route::post('patient/purchase_membership_package', [MembershipController::class, 'purchaseMembershipPackage']);
    Route::post('patient/current_membership_details', [MembershipController::class, 'currentMembershipDetails']);



    // Wellness 
    // Route::post('patient/wellness/search_list', [WellnessController::class, 'wellnessSearchList']);
    Route::post('patient/wellness/details', [WellnessController::class, 'wellnessDetails']);
    Route::post('patient/wellness/availability', [WellnessController::class, 'wellnessAvailability']);
    Route::post('patient/wellness/booking_details', [WellnessController::class, 'wellnessBookingDetails']);
    Route::post('patient/wellness/booking_summary', [WellnessController::class, 'wellnessSummary']);
    Route::post('patient/wellness/booking_confirmation', [WellnessController::class, 'wellnessConfirmation']);
    Route::post('patient/wellness/booking_reschedule', [WellnessController::class, 'wellnessReSchedule']);

    // Notifications 
    Route::post('patient/notifications', [NotificationController::class, 'notifications']);
    Route::post('patient/notifications/read_status', [NotificationController::class, 'read_status']);
    Route::post('patient/notifications/device_token', [NotificationController::class, 'device_token']);
});

Route::middleware('custom.auth.api')->group(function () {
    Route::post('patient/wellness/search_list', [WellnessController::class, 'wellnessSearchList']);
    Route::post('patient/consultation/doctors_list', [DoctorBookingController::class, 'doctorsList']);
    Route::post('patient/membership_packages', [MembershipController::class, 'membershipPackages']);
    Route::post('patient/membership_packages_details', [MembershipController::class, 'membershipPackageDetails']);
});
