<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Trn_Consultation_Booking;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\Mst_Patient;
use App\Models\Mst_Wellness;
use App\Models\Mst_Therapy;
use App\Helpers\PatientHelper;
use App\Models\Trn_Patient_Family_Member;

class BookingHistoryController extends Controller
{
    public function myBookingHistory(Request $request)
    {
        $data = array();
        try {
            // Validating request parameters
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

            // If validation fails, return error response
            if ($validator->fails()) {
                $data['status'] = 0;
                $data['message'] = $validator->errors()->first();
                return response($data);
            }

            // Get the authenticated patient's ID
            $patient_id = Auth::id();

            // Check if the patient exists
            if ($patient_id) {
                // Get the current date and time
                $currentDate = date('Y-m-d');
                $currentTime = date('H:i:s');
                // Cancel pending  bookings
                // $pending_to_cancel = Trn_Consultation_Booking::whereIn('booking_type_id', ['85', '84', '86'])
                //     ->whereIn('booking_status_id', ['87', '88'])
                //     ->join('mst_timeslots', 'trn_consultation_bookings.time_slot_id', '=', 'mst_timeslots.id')
                //     ->where(function ($query) use ($currentDate) {
                //         $query->where('trn_consultation_bookings.booking_date', '<', $currentDate)
                //             ->orWhere(function ($query) use ($currentDate) {
                //                 $query->where('trn_consultation_bookings.booking_date', '=', $currentDate)
                //                     ->where('mst_timeslots.time_to', '<', Carbon::now()->format('H:i:s'));
                //             });
                //     })
                //     ->update([
                //         'updated_at' => Carbon::now(),
                //         'booking_status_id' => 90
                //     ]);

                // Initialize array to store booking details
                $my_bookings = [];

                // Get pending consultation bookings
                $queries = Trn_Consultation_Booking::where('patient_id', $patient_id)
                    ->whereIn('trn_consultation_bookings.booking_type_id',  [84, 85])
                    ->where('trn_consultation_bookings.booking_status_id',  90)
                    ->leftJoin('mst_staffs', 'trn_consultation_bookings.doctor_id', '=', 'mst_staffs.staff_id')
                    ->leftJoin('mst_master_values as booking_type', 'trn_consultation_bookings.booking_type_id', '=', 'booking_type.id')
                    ->leftJoin('mst_timeslots', 'trn_consultation_bookings.time_slot_id', '=', 'mst_timeslots.id')
                    ->leftJoin('mst_branches', 'trn_consultation_bookings.branch_id', '=', 'mst_branches.branch_id')
                    ->leftJoin('mst_master_values as booking_status', 'trn_consultation_bookings.booking_status_id', '=', 'booking_status.id')
                    ->where(function ($query) use ($currentDate, $currentTime) {
                        $query->where(function ($query) use ($currentDate, $currentTime) {
                            $query->where('trn_consultation_bookings.booking_date', '<', $currentDate)
                                ->orWhere(function ($query) use ($currentDate, $currentTime) {
                                    $query->where('trn_consultation_bookings.booking_date', '=', $currentDate)
                                        ->where('mst_timeslots.time_to', '<', $currentTime);
                                });
                        })
                            ->orWhere(function ($query) use ($currentDate) {
                                $query->where('trn_consultation_bookings.booking_date', '>=', $currentDate)
                                    ->where('trn_consultation_bookings.booking_status_id', '=', 90);
                            });
                    })
                    ->select(
                        'mst_staffs.staff_name as doctor_name',
                        'booking_status.master_value as booking_status_name',
                        'mst_branches.branch_name',
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

                $all_bookings_consultation = $queries->get();

                // Process and add consultation bookings to the result array
                if ($all_bookings_consultation->isNotEmpty()) {
                    foreach ($all_bookings_consultation as $booking) {
                        // If booking_type_id = 84, then title is the name of the doctor
                        $title = $booking->doctor_name;

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

                        if ($booking->booking_type_id == 85) {
                            $wellness = Mst_Wellness::find($booking->wellness_id);
                            $title = $wellness->wellness_name;
                        }

                        if ($booking->booking_type_id == 86) {
                            $therapy = Mst_Therapy::find($booking->therapy_id);
                            $title = $therapy->therapy_name;
                        }

                        $booking_date = PatientHelper::dateFormatUser($booking->booking_date);
                        $time_from = Carbon::parse($booking->time_from)->format('h:i a');
                        $time_to = Carbon::parse($booking->time_to)->format('h:i a');

                        $my_bookings[] = [
                            'booking_id' => $booking->id,
                            'booking_reference_number' => $booking->booking_reference_number,
                            'booking_status' => $booking->booking_status_name,
                            'title' => $title,
                            'booking_date' => $booking_date,
                            'timeslot' => $time_from . '-' . $time_to,
                            'branch_name' => $booking->branch_name,
                            'booking_type' => $bookingType,
                            'booked_for' => $patient_name,
                        ];
                    }
                }

                // Paginate the result array
                $limit = $request->input('limit', 10); // Default limit is 5
                $page_number = $request->input('page_number', 1); // Default page number is 1

                $my_bookings_collection = collect($my_bookings);
                $paginate_bookings = $my_bookings_collection->slice(($page_number - 1) * $limit, $limit)->all();

                // Prepare the success response with pagination details
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
                    'prev_page_url' => $page_number > 1 ? (string) ($page_number - 1) : null,
                ];

                return response($data);
            } else {
                // Patient does not exist
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
                    // Get the authenticated patient's ID
                    $patient_id = Auth::id();
                    // Check if the patient exists
                    if ($patient_id) {
                        $currentDate = date('Y-m-d');
                        // Check if the provided booking ID corresponds to a consultation booking for the given patient
                        $is_consultation = Trn_Consultation_Booking::where('trn_consultation_bookings.id', $request->booking_id)
                            ->where('patient_id', $patient_id)
                            ->where('trn_consultation_bookings.booking_date', '<=', $currentDate)
                            ->first();
                        // If a consultation booking is found
                        if ($is_consultation) {
                            // Check if the booking type is for a doctor consultation
                            if ($is_consultation->booking_type_id == 84) {
                                // Process for fetching the details of doctor consultation booking.
                                // Fetch booking details for the specified booking_id and patient_id
                                $booking_details = Trn_Consultation_Booking::where('trn_consultation_bookings.id', $request->booking_id)
                                    ->where('patient_id', $patient_id)
                                    ->join('mst_staffs', 'trn_consultation_bookings.doctor_id', '=', 'mst_staffs.staff_id')
                                    ->join('mst_master_values as booking_type', 'trn_consultation_bookings.booking_type_id', '=', 'booking_type.id')
                                    ->join('mst_timeslots', 'trn_consultation_bookings.time_slot_id', '=', 'mst_timeslots.id')
                                    ->join('mst_branches', 'trn_consultation_bookings.branch_id', '=', 'mst_branches.branch_id')
                                    ->leftJoin('mst_master_values as qualification', 'mst_staffs.staff_qualification', '=', 'qualification.id')
                                    ->join('mst_master_values as booking_status', 'trn_consultation_bookings.booking_status_id', '=', 'booking_status.id')
                                    ->select(
                                        'mst_staffs.staff_name as doctor_name',
                                        'booking_status.master_value as status_name',
                                        'mst_branches.branch_name',
                                        'mst_branches.branch_id as branch_id',
                                        'trn_consultation_bookings.booking_date',
                                        'trn_consultation_bookings.id',
                                        'trn_consultation_bookings.wellness_id',
                                        'trn_consultation_bookings.therapy_id',
                                        'trn_consultation_bookings.booking_reference_number',
                                        'trn_consultation_bookings.is_for_family_member',
                                        'trn_consultation_bookings.booking_type_id',
                                        'trn_consultation_bookings.family_member_id',
                                        'mst_staffs.staff_booking_fee',
                                        'booking_type.master_value as booking_type_name',
                                        'mst_timeslots.time_from',
                                        'mst_timeslots.time_to',
                                        'mst_staffs.staff_id as doctor_id',
                                        'qualification.master_value as qualification'
                                    )->first();

                                // Initialize arrays to store doctor and other booking details
                                $doctor_details = [];
                                $other_booking_details = [];

                                // Check if booking details are fetched
                                if ($booking_details) {
                                    // Determine the patient's name based on whether it's for a family member or not
                                    if ($booking_details->is_for_family_member == 1) {
                                        $patient = Trn_Patient_Family_Member::find($booking_details->family_member_id);
                                        $patient_name = $patient->family_member_name;
                                    } else {
                                        $patient = Mst_Patient::find($patient_id);
                                        $patient_name = $patient->patient_name;
                                    }
                                    // Format date and time
                                    $booking_date = PatientHelper::dateFormatUser($booking_details->booking_date);
                                    $time_from = Carbon::parse($booking_details->time_from)->format('h:i a');
                                    $time_to = Carbon::parse($booking_details->time_to)->format('h:i a');
                                    // Populate doctor details array
                                    $doctor_details[] = [
                                        'doctor_id' => $booking_details->doctor_id,
                                        'branch_id' => $booking_details->branch_id,
                                        'doctor_name' => $booking_details->doctor_name,
                                        'qualification' => $booking_details->qualification,
                                        'branch_name' => $booking_details->branch_name,
                                    ];
                                    // Populate other booking details array
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

                                    // Prepare success response
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
                        // User does not exist
                        $data['status'] = 0;
                        $data['message'] = "User does not exist";
                        return response($data);
                    }
                } else {
                    // Booking ID is not provided in the request
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

    // Historical booking details of wellness 
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
                    // Get the authenticated patient's ID
                    $patient_id = Auth::id();
                    // Check if the patient exists
                    if ($patient_id) {
                        $currentDate = date('Y-m-d');
                        // Check if the provided booking ID corresponds to a wellness booking for the given patient.
                        $is_wellness = Trn_Consultation_Booking::where('trn_consultation_bookings.id', $request->booking_id)
                            ->where('patient_id', $patient_id)
                            ->where('trn_consultation_bookings.booking_date', '<=', $currentDate)
                            ->first();
                        // If a wellness booking is found
                        if ($is_wellness) {
                            // Check if the booking type is for wellness
                            if ($is_wellness->booking_type_id == 85) {
                                // Process for fetching the details of wellness booking.
                                // Fetch booking details for the specified booking_id and patient_id
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
                                        'mst_staffs.staff_id as doctor_id',
                                        'qualification.master_value as qualification'
                                    )->first();

                                // Initialize arrays to store doctor and other booking details
                                $doctor_details = [];
                                $other_booking_details = [];

                                // Check if booking details are fetched
                                if ($booking_details) {
                                    // Determine the patient's name based on whether it's for a family member or not
                                    if ($booking_details->is_for_family_member == 1) {
                                        $patient = Trn_Patient_Family_Member::find($booking_details->family_member_id);
                                        $patient_name = $patient->family_member_name;
                                    } else {
                                        $patient = Mst_Patient::find($patient_id);
                                        $patient_name = $patient->patient_name;
                                    }
                                    // Format date and time
                                    $booking_date = PatientHelper::dateFormatUser($booking_details->booking_date);
                                    $time_from = Carbon::parse($booking_details->time_from)->format('h:i a');
                                    $time_to = Carbon::parse($booking_details->time_to)->format('h:i a');
                                    // Populate doctor details array
                                    $wellness_details[] = [
                                        'wellness_id' => $booking_details->booking_type_id,
                                        'wellness_name' => $booking_details->booking_type_name,
                                        'branch_id' => $booking_details->branch_id,
                                        'branch_name' => $booking_details->branch_name,
                                        // 'latitude' => $booking_details->latitude ?? 0,
                                        // 'longitude' => $booking_details->longitude ?? 0,
                                    ];
                                    // Populate other booking details array
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

                                    // Prepare success response
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
                                // Booking type is not for a wellness
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
                        // User does not exist
                        $data['status'] = 0;
                        $data['message'] = "User does not exist";
                        return response($data);
                    }
                } else {
                    // Booking ID is not provided in the request
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
    // common booking history details 
    public function bookingHistoryDetails(Request $request)
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
                    // Get the authenticated patient's ID
                    $patient_id = Auth::id();
                    // Check if the patient exists
                    if ($patient_id) {
                        $currentDate = date('Y-m-d');
                        // Check if the provided booking ID corresponds to a consultation booking for the given patient
                        $is_exist = Trn_Consultation_Booking::where('trn_consultation_bookings.id', $request->booking_id)
                        ->where('patient_id', $patient_id)
                        ->first();
                    
                        // If a consultation booking is found
                        if ($is_exist) {
                            // Check if the booking type is for a doctor consultation
                            // Process for fetching the details of doctor consultation booking.
                            // Fetch booking details for the specified booking_id and patient_id
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
                                    'mst_branches.branch_id as branch_id',
                                    'trn_consultation_bookings.booking_date',
                                    'trn_consultation_bookings.id',
                                    'trn_consultation_bookings.wellness_id',
                                    'trn_consultation_bookings.therapy_id',
                                    'trn_consultation_bookings.booking_reference_number',
                                    'trn_consultation_bookings.is_for_family_member',
                                    'trn_consultation_bookings.booking_type_id',
                                    'trn_consultation_bookings.family_member_id',
                                    'mst_staffs.staff_booking_fee',
                                    'booking_type.master_value as booking_type_name',
                                    'trn_consultation_bookings.booking_fee',
                                    'mst_timeslots.time_from',
                                    'mst_timeslots.time_to',
                                    'mst_staffs.staff_id as doctor_id',
                                    'qualification.master_value as qualification'
                                )->first();

                            // Initialize arrays to store doctor and other booking details
                            $doctor_details = [];
                            $other_booking_details = [];

                            // Check if booking details are fetched
                            if ($booking_details) {
                                // Determine the patient's name based on whether it's for a family member or not
                                if ($booking_details->is_for_family_member == 1) {
                                    $patient = Trn_Patient_Family_Member::find($booking_details->family_member_id);
                                    $patient_name = $patient->family_member_name;
                                } else {
                                    $patient = Mst_Patient::find($patient_id);
                                    $patient_name = $patient->patient_name;
                                }
                                // Format date and time
                                $booking_date = PatientHelper::dateFormatUser($booking_details->booking_date);
                                $time_from = Carbon::parse($booking_details->time_from)->format('h:i a');
                                $time_to = Carbon::parse($booking_details->time_to)->format('h:i a');
                                // Populate doctor details array
                                if ($is_exist->booking_type_id == 84) {
                                    $booking_type_details[] = [
                                        'doctor_id' => $booking_details->doctor_id,
                                        'doctor_name' => $booking_details->doctor_name,
                                        'qualification' => $booking_details->qualification,
                                        'branch_id' => $booking_details->branch_id,
                                        'branch_name' => $booking_details->branch_name,
                                    ];
                                }
                                if ($is_exist->booking_type_id == 85) {
                                    $take_wellness = Mst_Wellness::where('wellness_id', $booking_details->wellness_id)->first();
                                    $booking_type_details[] = [
                                        'wellness_id' => $take_wellness->wellness_id,
                                        'wellness_name' => $take_wellness->wellness_name,
                                        'branch_id' => $booking_details->branch_id,
                                        'branch_name' => $booking_details->branch_name,
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
                                $other_booking_details[] = [
                                    'booking_id' => $booking_details->id,
                                    'booking_reference_number' => $booking_details->booking_reference_number,
                                    'booking_status' => $booking_details->status_name,
                                    'booking_fee' => $fee,
                                    'booking_date' => $booking_date,
                                    'timeslot' => $time_from . '-' . $time_to,
                                    'booked_for' => $booked_for,
                                ];

                                // Prepare success response
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
                            // No booking details found for the provided booking ID
                            $data['status'] = 0;
                            $data['message'] = "No booking details found for the provided booking ID.";
                            return response($data);
                        }
                    } else {
                        // User does not exist
                        $data['status'] = 0;
                        $data['message'] = "User does not exist";
                        return response($data);
                    }
                } else {
                    // Booking ID is not provided in the request
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
