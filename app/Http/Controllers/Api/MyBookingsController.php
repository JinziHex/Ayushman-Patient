<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Trn_Consultation_Booking;
use App\Models\Trn_Patient_Family_Member;
use App\Models\Mst_Patient;
use App\Models\Mst_Wellness;
use App\Models\Mst_Therapy;
use App\Models\Mst_Staff;
use App\Models\Trn_Patient_Device_Tocken;
use App\Models\Trn_Notification;
use Carbon\Carbon;
use App\Helpers\PatientHelper;
use App\Helpers\DeviceTockenHelper;
use Illuminate\Support\Facades\Validator;
use DB;

class MyBookingsController extends Controller
{
    public function myBookings(Request $request)
    {
        $data = array();
        try {

            $validator = Validator::make(
                $request->all(),
                [
                    'limit' => ['integer'],
                    'page_number' => ['integer'],
                ],
                [
                    'limit.integer' => 'Limit must be an integer',
                    'page_number.integer' => 'Page number must be an integer',
                ]
            );
            $patient_id = Auth::id();
            // return $patient_id;
            if ($patient_id) {
                $currentDate = date('Y-m-d');
                $currentTime = date('H:i:s');

                // Cancel pending  bookings
                // $pending_to_cancel = Trn_Consultation_Booking::whereIn('booking_type_id', ['85', '84', '86'])
                //     ->whereIn('booking_status_id', ['87', '88'])
                //     ->join('mst_timeslots', 'trn_consultation_bookings.time_slot_id', '=', 'mst_timeslots.id')
                //     ->where(function ($query) use ($currentDate) {
                //         $query->where('trn_consultation_bookings.booking_date', '<', $currentDate)
                //             ->orWhere(function ($query) use ($currentDate) {
                //                 $query->where('trn_consultation_bookings.booking_date', '=', $currentDate);
                //                     //->where('mst_timeslots.time_to', '<', Carbon::now()->format('H:i:s'));
                //             });
                //     })
                //     ->update([
                //         'updated_at' => Carbon::now(),
                //         'booking_status_id' => 90
                //     ]);
                $my_bookings = [];
                $all_bookings = [];
                // Retrieve all consultation bookings for the given patient
                $queries = Trn_Consultation_Booking::where('patient_id', $patient_id)
                    ->whereIn('trn_consultation_bookings.booking_status_id', [87, 88])
                    ->leftJoin('mst_staffs', 'trn_consultation_bookings.doctor_id', '=', 'mst_staffs.staff_id')
                    ->leftJoin('mst_master_values as booking_type_master', 'trn_consultation_bookings.booking_type_id', '=', 'booking_type_master.id')
                    ->leftJoin('mst_timeslots', 'trn_consultation_bookings.time_slot_id', '=', 'mst_timeslots.id')
                    ->leftJoin('mst_branches', 'trn_consultation_bookings.branch_id', '=', 'mst_branches.branch_id')
                    ->leftJoin('mst_master_values as booking_status_master', 'trn_consultation_bookings.booking_status_id', '=', 'booking_status_master.id')
                    ->where('trn_consultation_bookings.booking_date', '>=', $currentDate)
                    ->select(
                        'mst_staffs.staff_name as doctor_name',
                        'booking_status_master.master_value as booking_status',
                        'mst_branches.branch_name',
                        'trn_consultation_bookings.booking_date',
                        'trn_consultation_bookings.id',
                        'trn_consultation_bookings.wellness_id',
                        'trn_consultation_bookings.therapy_id',
                        'trn_consultation_bookings.booking_reference_number',
                        'trn_consultation_bookings.is_for_family_member',
                        'trn_consultation_bookings.booking_type_id',
                        'trn_consultation_bookings.family_member_id',
                        'booking_type_master.master_value as booking_type',
                        'mst_timeslots.time_from',
                        'mst_timeslots.time_to'
                    );

                // sorting 
                if (isset($request->sort_type)) {
                    if ($request->sort_type == 0) {
                        // Booking date wise ascending order 
                        $queries = $queries->orderBy('trn_consultation_bookings.booking_date', 'asc');
                    }
                    if ($request->sort_type == 1) {
                        // Booking date wise descending order 
                        $queries = $queries->orderBy('trn_consultation_bookings.booking_date', 'desc');
                    }
                } else {
                    $queries = $queries->orderBy('trn_consultation_bookings.booking_date', 'desc');
                }
                // filter 
                if (isset($request->search_booking_date) && !is_null($request->search_booking_date) && $request->search_booking_date != "null" && $request->search_booking_date != null) {
                    $booking_date = PatientHelper::dateFormatDb($request->search_booking_date);
                    $queries = $queries->where('trn_consultation_bookings.booking_date', 'like', '%' . $booking_date . '%');
                }
                if (isset($request->search_booking_status) && !is_null($request->search_booking_status) && $request->search_booking_status != "null" && $request->search_booking_status != null) {
                    $queries = $queries->where('trn_consultation_bookings.booking_status_id', $request->search_booking_status);
                }
                if (isset($request->search_branch) && !is_null($request->search_branch) && $request->search_branch != "null" && $request->search_branch != null) {
                    $queries = $queries->where('trn_consultation_bookings.branch_id', $request->search_branch);
                }
                if (isset($request->search_booking_type) && !is_null($request->search_booking_type) && $request->search_booking_type != "null" && $request->search_booking_type != null) {

                    if ($request->search_booking_type == 86) {
                        // Booking type 86 corresponds to therapy, and it is not initiated by the patient or user; instead, it is recommended by a doctor.
                        //  Therefore, in such cases, even though no results are found, we still need to maintain the same response structure in the API.
                        $data['status'] = 1;
                        $data['message'] = "Data fetched";
                        $data['data'] = [];
                        $data['pagination_details'] = [
                            'current_page' => 1,
                            'total_records' => 0,
                            'total_pages' => 0,
                            'per_page' => 10,
                            'first_page_url' => null,
                            'last_page_url' => null,
                            'next_page_url' => null,
                            'prev_page_url' => null,
                        ];
                        return response($data);
                    }
                    $queries = $queries->where('trn_consultation_bookings.booking_type_id', $request->search_booking_type);
                }

                $all_bookings = $queries->get();

                if ($all_bookings->isNotEmpty()) {
                    // Loop through all consultation bookings and prepare data for display
                    foreach ($all_bookings as $booking) {
                        // If booking_type_id = 84, then title is the name of the doctor
                        $title = $booking->doctor_name;

                        // Check if the booking is for a family member
                        if ($booking->is_for_family_member == 1) {
                            $patient = Trn_Patient_Family_Member::find($booking->family_member_id);
                            $patient_name = $patient->family_member_name;
                        } else {
                            $patient = Mst_Patient::find($patient_id);
                            $patient_name = $patient->patient_name;
                        }

                        if ($booking->booking_type_id == 84) {
                            $bookingType = 0;
                        }

                        if ($booking->booking_type_id == 85) {
                            $bookingType = 1;
                        }

                        // Check booking type and set title accordingly
                        if ($booking->booking_type_id == 85) {
                            $wellness = Mst_Wellness::find($booking->wellness_id);
                            $title = $wellness->wellness_name;
                        }

                        if ($booking->booking_type_id == 86) {
                            $therapy = Mst_Therapy::find($booking->therapy_id);
                            $title = $therapy->therapy_name;
                        }

                        // Format date and time for display
                        $booking_date = PatientHelper::dateFormatUser($booking->booking_date);
                        $time_from = Carbon::parse($booking->time_from)->format('h:i a');
                        $time_to = Carbon::parse($booking->time_to)->format('h:i a');

                        // Create an array with booking details
                        $my_bookings[] = [
                            'booking_id' => $booking->id,
                            'booking_reference_number' => $booking->booking_reference_number,
                            'booking_status' => $booking->booking_status,
                            'title' => $title,
                            'booking_date' => $booking_date,
                            'timeslot' => $time_from . '-' . $time_to,
                            'branch_name' => $booking->branch_name,
                            'booking_type' => $bookingType,
                            'booked_for' => $patient_name,
                        ];
                    }
                }
                $limit = $request->input('limit', 10); // Default limit is 5
                $page_number = $request->input('page_number', 1); // Default page number is 1

                // Create a collection from the array
                $my_bookings_collection = collect($my_bookings);

                // Get a portion of the collection based on the pagination parameters
                $paginate_bookings = $my_bookings_collection->slice(($page_number - 1) * $limit, $limit)->all();

                $data['status'] = 1;
                $data['message'] = "Data fetched";
                $data['data'] = array_values($paginate_bookings);
                $data['pagination_details'] = [
                    'current_page' => $page_number,
                    'total_records' => count($my_bookings),
                    'total_pages' => ceil(count($my_bookings) / $limit),
                    'per_page' => $limit,
                    'first_page_url' => $page_number > 1 ? (string)($page_number = 1) : null,
                    'last_page_url' => $page_number < ceil(count($my_bookings) / $limit) ? (string) ceil(count($my_bookings) / $limit) : null,
                    'next_page_url' => $page_number < ceil(count($my_bookings) / $limit) ? (string) ($page_number + 1) : null,
                    'prev_page_url' => $page_number > 1 ? (string) ($page_number - 1)  : null,
                ];

                return response($data);
                // } else {
                //     $data['status'] = 0;
                //     $data['message'] = "No bookings";
                //     return response($data);
                // }
            } else {
                $data['status'] = 0;
                $data['message'] = "User does not exist";
                return response($data);
            }
        } catch (\Exception $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        } catch (\Throwable $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        }
    }

    public function consultationBookingDetails(Request $request)
    {
        // Currently not in use 
        $data = array();
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'booking_id' => ['required'],
                ],
                [
                    'booking_id.required' => 'Booking refernce Id required',
                ]
            );
            if (!$validator->fails()) {
                // Check if booking_id is set in the request
                if (isset($request->booking_id)) {
                    // Get the patient ID from the authenticated user
                    $patient_id = Auth::id();
                    // Check if the patient ID exists
                    if ($patient_id) {
                        $currentDate = date('Y-m-d');
                        // Check if the provided booking ID corresponds to a consultation booking for the given patient
                        $is_consultation = Trn_Consultation_Booking::where('trn_consultation_bookings.id', $request->booking_id)
                            ->where('patient_id', $patient_id)
                            ->where('trn_consultation_bookings.booking_date', '>=', $currentDate)
                            ->first();
                        // If a consultation booking is found
                        if ($is_consultation) {
                            // Check if the booking type is for a doctor consultation
                            if ($is_consultation->booking_type_id == 84) {
                                // Process for fetching the details of doctor consultation booking.
                                // Retrieve booking details for the specified booking_id and patient_id
                                $booking_details = Trn_Consultation_Booking::where('trn_consultation_bookings.id', $request->booking_id)
                                    ->where('patient_id', $patient_id)
                                    ->leftJoin('mst_staffs', 'trn_consultation_bookings.doctor_id', '=', 'mst_staffs.staff_id')
                                    ->leftJoin('mst_master_values as booking_type', 'trn_consultation_bookings.booking_type_id', '=', 'booking_type.id')
                                    ->leftJoin('mst_timeslots', 'trn_consultation_bookings.time_slot_id', '=', 'mst_timeslots.id')
                                    ->leftJoin('mst_branches', 'trn_consultation_bookings.branch_id', '=', 'mst_branches.branch_id')
                                    ->leftJoin('mst_master_values as qualification', 'mst_staffs.staff_qualification', '=', 'qualification.id')
                                    ->leftJoin('mst_master_values as booking_status', 'trn_consultation_bookings.booking_status_id', '=', 'booking_status.id')
                                    ->select(
                                        'mst_staffs.staff_name as doctor_name',
                                        'booking_status.master_value as status_name',
                                        'mst_branches.branch_name',
                                        'mst_branches.latitude',
                                        'mst_branches.longitude',
                                        'mst_branches.branch_id as branch_id',
                                        'trn_consultation_bookings.booking_date',
                                        'trn_consultation_bookings.id',
                                        'trn_consultation_bookings.wellness_id',
                                        'trn_consultation_bookings.therapy_id',
                                        'trn_consultation_bookings.booking_reference_number',
                                        'trn_consultation_bookings.is_for_family_member',
                                        'trn_consultation_bookings.booking_type_id',
                                        'trn_consultation_bookings.family_member_id',
                                        'booking_type.master_value as booking_type_name',
                                        'mst_timeslots.time_from',
                                        'mst_timeslots.time_to',
                                        'mst_staffs.staff_booking_fee',
                                        'mst_staffs.staff_id as doctor_id',
                                        'qualification.master_value as staff_qualification'
                                    )
                                    ->first();


                                $doctor_details = [];
                                $other_booking_details = [];
                                // Check if booking details exist
                                if ($booking_details) {
                                    // Determine the patient's name based on whether the booking is for a family member
                                    if ($booking_details->is_for_family_member == 1) {
                                        $patient = Trn_Patient_Family_Member::find($booking_details->family_member_id);
                                        $patient_name = $patient->family_member_name;
                                    } else {
                                        $patient = Mst_Patient::find($patient_id);
                                        $patient_name = $patient->patient_name;
                                    }
                                    // Format date and time for display
                                    $booking_date = PatientHelper::dateFormatUser($booking_details->booking_date);
                                    $time_from = Carbon::parse($booking_details->time_from)->format('h:i a');
                                    $time_to = Carbon::parse($booking_details->time_to)->format('h:i a');
                                    // Populate doctor_details array with relevant information
                                    $doctor_details[] = [
                                        'doctor_id' => $booking_details->doctor_id,
                                        'branch_id' => $booking_details->branch_id,
                                        'doctor_name' => $booking_details->doctor_name,
                                        'qualification' => $booking_details->staff_qualification,
                                        'branch_name' => $booking_details->branch_name,
                                        'latitude' => $booking_details->latitude ?? 0,
                                        'longitude' => $booking_details->longitude ?? 0,
                                    ];

                                    // Populate other_booking_details array with relevant information
                                    $fee = PatientHelper::amountDecimal($booking_details->staff_booking_fee);
                                    $other_booking_details[] = [
                                        'booking_id' => $booking_details->id,
                                        'booking_reference_number' => $booking_details->booking_reference_number,
                                        'booking_status' => $booking_details->status_name,
                                        'booking_fee' => $fee,
                                        'booking_date' => $booking_date,
                                        'timeslot' => $time_from . '-' . $time_to,
                                        'booked_for' => $patient_name,
                                    ];

                                    // Prepare the response data
                                    $data['status'] = 1;
                                    $data['message'] = "Data fetched";
                                    $data['doctor_details'] = $doctor_details;
                                    $data['other_booking_details'] = $other_booking_details;
                                    return response($data);
                                } else {
                                    // No booking details found
                                    $data['status'] = 0;
                                    $data['message'] = "No booking details";
                                    return response($data);
                                }
                            } else {
                                // Booking type is not for a doctor consultation
                                $data['status'] = 0;
                                $data['message'] = "Please provide a valid consultation booking ID.";
                                return response($data);
                            }
                        } else {
                            // No booking details found for the provided booking ID
                            $data['status'] = 0;
                            $data['message'] = "No booking details found for the provided booking ID.";
                            return response($data);
                        }
                    } else {
                        $data['status'] = 0;
                        $data['message'] = "User does not exist";
                        return response($data);
                    }
                } else {
                    $data['status'] = 0;
                    $data['message'] = "Please fill mandatory fields";
                    return response($data);
                }
            } else {
                $data['status'] = 0;
                $data['errors'] = $validator->errors();
                $data['message'] = "Validation errors";
                return response($data);
            }
        } catch (\Exception $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        } catch (\Throwable $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        }
    }

    // Wellness booking details  
    public function wellnessBookingDetails(Request $request)
    {
        // Currently not in use 
        $data = array();
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'booking_id' => ['required'],
                ],
                [
                    'booking_id.required' => 'Booking refernce Id required',
                ]
            );
            if (!$validator->fails()) {
                // Check if booking_id is set in the request
                if (isset($request->booking_id)) {
                    // Get the patient ID from the authenticated user
                    $patient_id = Auth::id();
                    // Check if the patient ID exists
                    if ($patient_id) {
                        $currentDate = date('Y-m-d');
                        // Check if the provided booking ID corresponds to a wellness booking for the given patient
                        $is_wellness = Trn_Consultation_Booking::where('trn_consultation_bookings.id', $request->booking_id)
                            ->where('patient_id', $patient_id)
                            ->where('trn_consultation_bookings.booking_date', '>=', $currentDate)
                            ->first();
                        // If a consultation booking is found
                        if ($is_wellness) {
                            // Check if the booking type is for a wellness booking
                            if ($is_wellness->booking_type_id == 85) {
                                // Process for fetching the details of wellness booking.
                                // Retrieve booking details for the specified booking_id and patient_id
                                $booking_details = Trn_Consultation_Booking::where('trn_consultation_bookings.id', $request->booking_id)
                                    ->where('patient_id', $patient_id)
                                    ->leftJoin('mst_staffs', 'trn_consultation_bookings.doctor_id', '=', 'mst_staffs.staff_id')
                                    ->leftJoin('mst_master_values as booking_type', 'trn_consultation_bookings.booking_type_id', '=', 'booking_type.id')
                                    ->leftJoin('mst_timeslots', 'trn_consultation_bookings.time_slot_id', '=', 'mst_timeslots.id')
                                    ->leftJoin('mst_branches', 'trn_consultation_bookings.branch_id', '=', 'mst_branches.branch_id')
                                    ->leftJoin('mst_master_values as qualification', 'mst_staffs.staff_qualification', '=', 'qualification.id')
                                    ->leftJoin('mst_master_values as booking_status', 'trn_consultation_bookings.booking_status_id', '=', 'booking_status.id')
                                    ->select(
                                        'mst_staffs.staff_name as doctor_name',
                                        'booking_status.master_value as status_name',
                                        'mst_branches.branch_name',
                                        'mst_branches.latitude',
                                        'mst_branches.longitude',
                                        'mst_branches.branch_id as branch_id',
                                        'trn_consultation_bookings.booking_date',
                                        'trn_consultation_bookings.id',
                                        'trn_consultation_bookings.wellness_id',
                                        'trn_consultation_bookings.therapy_id',
                                        'trn_consultation_bookings.booking_reference_number',
                                        'trn_consultation_bookings.is_for_family_member',
                                        'trn_consultation_bookings.booking_type_id',
                                        'trn_consultation_bookings.family_member_id',
                                        'trn_consultation_bookings.booking_fee',
                                        'booking_type.master_value as booking_type_name',
                                        'mst_timeslots.time_from',
                                        'mst_timeslots.time_to',
                                        'mst_staffs.staff_booking_fee',
                                        'mst_staffs.staff_id as doctor_id',
                                        'qualification.master_value as staff_qualification'
                                    )
                                    ->first();


                                $wellness_details = [];
                                $other_booking_details = [];
                                // Check if booking details exist
                                if ($booking_details) {
                                    // Determine the patient's name based on whether the booking is for a family member
                                    if ($booking_details->is_for_family_member == 1) {
                                        $patient = Trn_Patient_Family_Member::find($booking_details->family_member_id);
                                        $patient_name = $patient->family_member_name;
                                    } else {
                                        $patient = Mst_Patient::find($patient_id);
                                        $patient_name = $patient->patient_name;
                                    }
                                    // Format date and time for display
                                    $booking_date = PatientHelper::dateFormatUser($booking_details->booking_date);
                                    $time_from = Carbon::parse($booking_details->time_from)->format('h:i a');
                                    $time_to = Carbon::parse($booking_details->time_to)->format('h:i a');
                                    // Populate wellness_details array with relevant information
                                    $wellness_details[] = [
                                        'wellness_id' => $booking_details->booking_type_id,
                                        'wellness_name' => $booking_details->booking_type_name,
                                        'branch_id' => $booking_details->branch_id,
                                        'branch_name' => $booking_details->branch_name,
                                        'latitude' => $booking_details->latitude ?? 0,
                                        'longitude' => $booking_details->longitude ?? 0,
                                    ];

                                    // Populate other_booking_details array with relevant information
                                    $fee = PatientHelper::amountDecimal($booking_details->booking_fee);
                                    $other_booking_details[] = [
                                        'booking_id' => $booking_details->id,
                                        'booking_reference_number' => $booking_details->booking_reference_number,
                                        'booking_status' => $booking_details->status_name,
                                        'booking_fee' => $fee,
                                        'booking_date' => $booking_date,
                                        'timeslot' => $time_from . '-' . $time_to,
                                        'booked_for' => $patient_name,
                                    ];

                                    // Prepare the response data
                                    $data['status'] = 1;
                                    $data['message'] = "Data fetched";
                                    $data['wellness_details'] = $wellness_details;
                                    $data['other_booking_details'] = $other_booking_details;
                                    return response($data);
                                } else {
                                    // No booking details found
                                    $data['status'] = 0;
                                    $data['message'] = "No booking details";
                                    return response($data);
                                }
                            } else {
                                // Booking type is not for a wellness consultation
                                $data['status'] = 0;
                                $data['message'] = "Please provide a valid wellness booking ID.";
                                return response($data);
                            }
                        } else {
                            // No booking details found for the provided booking ID
                            $data['status'] = 0;
                            $data['message'] = "No booking details found for the provided booking ID.";
                            return response($data);
                        }
                    } else {
                        $data['status'] = 0;
                        $data['message'] = "User does not exist";
                        return response($data);
                    }
                } else {
                    $data['status'] = 0;
                    $data['message'] = "Please fill mandatory fields";
                    return response($data);
                }
            } else {
                $data['status'] = 0;
                $data['errors'] = $validator->errors();
                $data['message'] = "Validation errors";
                return response($data);
            }
        } catch (\Exception $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        } catch (\Throwable $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        }
    }

    public function cancelBooking(Request $request)
    {
        $data = array();
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'booking_id' => ['required'],
                ],
                [
                    'booking_id.required' => 'Booking refernce Id required',
                ]
            );
            if (!$validator->fails()) {
                // Check if booking_id is set in the request
                if (isset($request->booking_id)) {
                    // Update the booking status to indicate cancellation
                    $cancelBooking = Trn_Consultation_Booking::where('id', $request->booking_id)->update([
                        'updated_at' => Carbon::now(),
                        'booking_status_id' => 90
                    ]);
                    // Retrieve the updated booking details
                    $updatedBooking = Trn_Consultation_Booking::find($request->booking_id);

                    // Get the name of the doctor associated with the booking
                    $booking_type_name = Mst_Staff::where('staff_id', $updatedBooking->doctor_id)->value('staff_name');
                    if ($updatedBooking->booking_type_id == 85) {
                        $wellness_id = $updatedBooking->wellness_id;
                        $booking_type_name = Mst_Wellness::where('wellness_id', $wellness_id)->value('wellness_name');
                    }
                    if ($updatedBooking->booking_type_id == 86) {
                        $therapy_id = $updatedBooking->therapy_id;
                        $booking_type_name = Mst_Therapy::where('id', $therapy_id)->value('therapy_name');
                    }
                    // Retrieve the device tokens associated with the patient
                    $patientDevice = Trn_Patient_Device_Tocken::where('patient_id', Auth::id())->get();

                    if ($patientDevice) {
                        // Notification details
                        $updatedBookingDate = PatientHelper::dateFormatUser($updatedBooking->booking_date);
                        $title = 'Booking Cancelled';
                        $body = 'Your booking for ' . $booking_type_name . ' on ' . $updatedBookingDate . ' has been cancelled!';
                        $clickAction = "PatientBookingCancelling";
                        $type = "Cancel";
                        // Save notification to the patient's notification table
                        $notificationCreate = Trn_Notification::create([
                            'patient_id' => Auth::id(),
                            'title' => $title,
                            'content' => $body,
                            'read_status' => 0,
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                        ]);
                        // Iterate through each device token to send cancellation notification
                        foreach ($patientDevice as $pdt) {
                            // Send notification to the patient's device
                            $response =  DeviceTockenHelper::patientNotification($pdt->patient_device_token, $title, $body, $clickAction, $type);
                        }
                    }

                    // Prepare the success response
                    $data['status'] = 1;
                    $data['message'] = "Booking cancelled successfully";
                    return response($data);
                } else {
                    // Incomplete request, mandatory fields are missing
                    $data['status'] = 0;
                    $data['message'] = "Please fill mandatory fields";
                    return response($data);
                }
            } else {
                $data['status'] = 0;
                $data['errors'] = $validator->errors();
                $data['message'] = "Validation errors";
                return response($data);
            }
        } catch (\Exception $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        } catch (\Throwable $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        }
    }

    // common my booking details 
    public function myBookingDetails(Request $request)
    {
        $data = array();
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'booking_id' => ['required'],
                ],
                [
                    'booking_id.required' => 'Booking refernce Id required',
                ]
            );
            if (!$validator->fails()) {
                // Check if booking_id is set in the request
                if (isset($request->booking_id)) {
                    // Get the patient ID from the authenticated user
                    $patient_id = Auth::id();
                    // Check if the patient ID exists
                    if ($patient_id) {
                        $currentDate = date('Y-m-d');
                        // Check if the provided booking ID corresponds to a consultation booking for the given patient
                        $is_exist = Trn_Consultation_Booking::where('trn_consultation_bookings.id', $request->booking_id)
                            ->where('patient_id', $patient_id)
                            ->where('trn_consultation_bookings.booking_date', '>=', $currentDate)
                            ->first();
                        // If a consultation booking is found,Check if the booking type is for a doctor consultation
                        // Process for fetching the details of doctor consultation booking.
                        // Retrieve booking details for the specified booking_id and patient_id
                        $booking_details = Trn_Consultation_Booking::where('trn_consultation_bookings.id', $request->booking_id)
                            ->where('patient_id', $patient_id)
                            ->where('trn_consultation_bookings.booking_type_id', '!=', 86)
                            ->leftJoin('mst_staffs', 'trn_consultation_bookings.doctor_id', '=', 'mst_staffs.staff_id')
                            ->leftJoin('mst_master_values as booking_type', 'trn_consultation_bookings.booking_type_id', '=', 'booking_type.id')
                            ->leftJoin('mst_timeslots', 'trn_consultation_bookings.time_slot_id', '=', 'mst_timeslots.id')
                            ->leftJoin('mst_branches', 'trn_consultation_bookings.branch_id', '=', 'mst_branches.branch_id')
                            ->leftJoin('mst_master_values as qualification', 'mst_staffs.staff_qualification', '=', 'qualification.id')
                            ->leftJoin('mst_master_values as booking_status', 'trn_consultation_bookings.booking_status_id', '=', 'booking_status.id')
                            ->select(
                                'mst_staffs.staff_name as doctor_name',
                                'booking_status.master_value as status_name',
                                'mst_branches.branch_name',
                                'mst_branches.latitude',
                                'mst_branches.longitude',
                                'mst_branches.branch_id as branch_id',
                                'trn_consultation_bookings.booking_date',
                                'trn_consultation_bookings.id',
                                'trn_consultation_bookings.wellness_id',
                                'trn_consultation_bookings.therapy_id',
                                'trn_consultation_bookings.booking_reference_number',
                                'trn_consultation_bookings.is_for_family_member',
                                'trn_consultation_bookings.booking_type_id',
                                'trn_consultation_bookings.family_member_id',
                                'trn_consultation_bookings.booking_fee',
                                'booking_type.master_value as booking_type_name',
                                'mst_timeslots.time_from',
                                'mst_timeslots.time_to',
                                'mst_staffs.staff_booking_fee',
                                'mst_staffs.staff_id as doctor_id',
                                'qualification.master_value as staff_qualification'
                            )
                            ->first();


                        $booking_type_details = [];
                        $other_booking_details = [];
                        // Check if booking details exist
                        if ($booking_details) {
                            // Determine the patient's name based on whether the booking is for a family member
                            if ($booking_details->is_for_family_member == 1) {
                                $patient = Trn_Patient_Family_Member::find($booking_details->family_member_id);
                                $patient_name = $patient->family_member_name;
                            } else {
                                $patient = Mst_Patient::find($patient_id);
                                $patient_name = $patient->patient_name;
                            }
                            // Format date and time for display
                            $booking_date = PatientHelper::dateFormatUser($booking_details->booking_date);
                            $time_from = Carbon::parse($booking_details->time_from)->format('h:i a');
                            $time_to = Carbon::parse($booking_details->time_to)->format('h:i a');
                            // Populate booking_type_details array with relevant information
                            if ($is_exist->booking_type_id == 84) {
                                $booking_type_details = [
                                    'doctor_id' => $booking_details->doctor_id,
                                    'doctor_name' => $booking_details->doctor_name,
                                    'qualification' => $booking_details->staff_qualification,
                                    'branch_id' => $booking_details->branch_id,
                                    'branch_name' => $booking_details->branch_name,
                                    'latitude' => $booking_details->latitude ?? 0,
                                    'longitude' => $booking_details->longitude ?? 0,
                                ];
                            }
                            if ($is_exist->booking_type_id == 85) {
                                $take_wellness = Mst_Wellness::where('wellness_id', $booking_details->wellness_id)->first();
                                $booking_type_details = [
                                    'wellness_id' => $take_wellness->wellness_id,
                                    'wellness_name' => $take_wellness->wellness_name,
                                    'branch_id' => $booking_details->branch_id,
                                    'branch_name' => $booking_details->branch_name,
                                    'latitude' => $booking_details->latitude ?? 0,
                                    'longitude' => $booking_details->longitude ?? 0,
                                ];
                            }

                            // Populate other_booking_details array with relevant information
                            if ($is_exist->booking_type_id == 84) {
                                $fee = PatientHelper::amountDecimal($booking_details->staff_booking_fee);
                            }
                            if ($is_exist->booking_type_id == 85) {
                                $fee = PatientHelper::amountDecimal($booking_details->booking_fee);
                            }
                            // select booked for 
                            if ($booking_details->is_for_family_member == 0) {
                                $booked_for = $patient_name;
                            }
                            if ($booking_details->is_for_family_member == 1) {
                                $bookedMemberDetails = Trn_Patient_Family_Member::where('id', $booking_details->family_member_id)->first();
                                $booked_for = $bookedMemberDetails->family_member_name;
                            }
                            $other_booking_details = [
                                'booking_id' => $booking_details->id,
                                'booking_reference_number' => $booking_details->booking_reference_number,
                                'booking_status' => $booking_details->status_name,
                                'booking_fee' => $fee,
                                'booking_date' => $booking_date,
                                'timeslot' => $time_from . '-' . $time_to,
                                'booked_for' => $booked_for,
                            ];

                            // Prepare the response data
                            $data['status'] = 1;
                            $data['message'] = "Data fetched";
                            $data['booking_type_details'] = $booking_type_details;
                            $data['other_booking_details'] = $other_booking_details;
                            return response($data);
                        } else {
                            // No booking details found
                            $data['status'] = 0;
                            $data['message'] = "No booking details";
                            return response($data);
                        }
                    } else {
                        $data['status'] = 0;
                        $data['message'] = "User does not exist";
                        return response($data);
                    }
                } else {
                    $data['status'] = 0;
                    $data['message'] = "Please fill mandatory fields";
                    return response($data);
                }
            } else {
                $data['status'] = 0;
                $data['errors'] = $validator->errors();
                $data['message'] = "Validation errors";
                return response($data);
            }
        } catch (\Exception $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        } catch (\Throwable $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        }
    }
}
