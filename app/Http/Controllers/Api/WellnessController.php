<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Mst_Wellness;
use App\Models\Mst_Branch;
use App\Models\Mst_Patient;
use App\Models\Mst_Master_Value;
use App\Models\Mst_Therapy_Room_Slot;
use App\Models\Mst_TimeSlot;
use App\Models\Mst_Wellness_Therapyrooms;
use App\Models\Trn_Patient_Family_Member;
use App\Models\Trn_Consultation_Booking;
use App\Models\Mst_Patient_Membership_Booking;
use App\Models\Trn_Wellness_Branch;
use App\Models\Trn_Patient_Wellness_Sessions;
use Carbon\Carbon;
use App\Helpers\PatientHelper;
use App\Helpers\DeviceTockenHelper;
use App\Models\Trn_Notification;
use App\Models\Trn_Patient_Device_Tocken;

class WellnessController extends Controller
{
    public function wellnessSearchList(Request $request)
    {
        $data = [];
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'branch_id' => ['required'],
                    'booking_date' => ['required'],
                    'limit' => ['integer'],
                    'page_number' => ['integer'],
                ],
                [
                    'branch_id.required' => 'Branch required',
                    'booking_date.required' => 'Booking date required',
                    'limit.integer' => 'Limit must be an integer',
                    'page_number.integer' => 'Page number must be an integer',
                ]
            );

            if (!$validator->fails()) {
                if (isset($request->branch_id) && !empty($request->booking_date)) {
                    $bookingDate = Carbon::parse($request->booking_date);
                    $currentDate = Carbon::now();
                    $currentYear = Carbon::now()->year;

                    if ($bookingDate->year > $currentYear + 1) {
                        $data['status'] = 0;
                        $data['message'] = "Booking date cannot be more than 1 year in the future.";
                        return response($data);
                    }

                    if (!$bookingDate->isSameDay($currentDate) && $bookingDate->isPast()) {
                        $data['status'] = 0;
                        $data['message'] = "Booking date is older than the current date.";
                        return response($data);
                    }

                    $patient_id = Auth::id();
                    $day_of_week = PatientHelper::getWeekDay($request->booking_date);
                    $weekDayId = Mst_Master_Value::where('master_id', 3)->where('master_value', 'LIKE', '%' . $day_of_week . '%')->pluck('id')->first();

                    $availableRooms = Mst_Therapy_Room_Slot::where('week_day', $weekDayId)
                        ->distinct('therapy_room_id')
                        ->pluck('therapy_room_id')
                        ->toArray();

                    if (isset($request->search_wellness_branch) && !is_null($request->search_wellness_branch) && $request->search_wellness_branch != "null" && $request->search_wellness_branch != null) {
                        $branch_name = Mst_Branch::where('branch_id', $request->search_wellness_branch)->where('is_active', 1)->value('branch_name');

                        $roomWellness = Mst_Wellness_Therapyrooms::where('branch_id', $request->search_wellness_branch)
                            ->whereIn('therapy_room_id', $availableRooms)
                            ->distinct('wellness_id')
                            ->pluck('wellness_id')
                            ->toArray();
                    } else {
                        $branch_name = Mst_Branch::where('branch_id', $request->branch_id)->where('is_active', 1)->value('branch_name');

                        // $roomWellness = Mst_Wellness_Therapyrooms::where('branch_id', $request->branch_id)
                        //     ->whereIn('therapy_room_id', $availableRooms)
                        //     ->distinct('wellness_id')
                        //     ->pluck('wellness_id')
                        //     ->toArray();
                    }

                    $queries = Mst_Wellness::
                        // whereIn('wellness_id', $roomWellness)
                        where('is_active', 1);

                    if (isset($request->search_wellness_name) && !is_null($request->search_wellness_name) && $request->search_wellness_name != "null" && $request->search_wellness_name != null) {
                        $queries = $queries->where('wellness_name', 'like', '%' . $request->search_wellness_name . '%');
                    }

                    $all_wellness = $queries->paginate($request->limit ?? 10, ['*'], 'page_number', $request->page_number ?? 1);

                    $wellness_list = [];
                    $allWellnessIds = [];
                    $checkMembership = Mst_Patient::where('id', $patient_id)->value('available_membership');
                    if ($checkMembership == 1) {
                        $active_membership_booking_ids = Mst_Patient_Membership_Booking::where('patient_id', Auth::id())
                            ->where('membership_expiry_date', '>=', Carbon::now())
                            ->where('is_active', 1)
                            ->pluck('membership_patient_id')
                            ->toArray();

                        $allWellnessIds = Trn_Patient_Wellness_Sessions::whereIn('membership_patient_id', $active_membership_booking_ids)
                            ->where('status', 0)
                            ->distinct()
                            ->pluck('wellness_id');

                        $allWellnessIds = $allWellnessIds->toArray();
                    }

                    // if (!$all_wellness->isEmpty()) {
                    foreach ($all_wellness as $wellness) {
                        $is_included = 0;
                        if (in_array($wellness->wellness_id, $allWellnessIds)) {
                            $is_included = 1;
                        }
                        $wellness_price = PatientHelper::amountDecimal($wellness->wellness_cost);
                        $wellness_offer_price = PatientHelper::amountDecimal($wellness->offer_price);
                        $wellness_image =  config('admin_assets.wellness-image-path'). $wellness->wellness_image;
                        $is_offer = ($wellness_price > $wellness_offer_price) ? 1 : 0;

                        $wellness_list[] = [
                            'id' => $wellness->wellness_id,
                            'wellness_name' => $wellness->wellness_name,
                            'wellness_price' => $wellness_price,
                            'wellness_offer_price' => $wellness_offer_price,
                            'is_offer' => $is_offer,
                            'is_included' => $is_included,
                            'wellness_image' => $wellness_image,
                        ];
                    }
                    $booking_date = PatientHelper::dateFormatUser($request->booking_date);
                    $data['status'] = 1;
                    $data['message'] = "Data fetched";
                    $data['booking_date'] = $booking_date;
                    $data['branch_name'] = $branch_name;
                    $data['data'] =  $wellness_list;
                    $data['pagination_details'] = [
                        'current_page' => $all_wellness->currentPage(),
                        'total_records' => $all_wellness->total(),
                        'total_pages' => $all_wellness->lastPage(),
                        'per_page' => $all_wellness->perPage(),
                        // 'first_page_url' => $all_wellness->currentPage() > 1 ? (string)1 : null,
                        // 'last_page_url' => (string)$all_wellness->lastPage(),
                        // 'next_page_url' => $all_wellness->nextPageUrl() ? (string)($all_wellness->currentPage() + 1) : null,
                        // 'prev_page_url' => $all_wellness->previousPageUrl() ? (string)($all_wellness->currentPage() - 1) : null,
                    ];

                    return response()->json($data);
                    // } else {
                    //     $data['status'] = 0;
                    //     $data['message'] = "No wellness found";
                    //     return response()->json($data);
                    // }
                } else {
                    $data['status'] = 0;
                    $data['message'] = "Please fill mandatory fields";
                    return response()->json($data);
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


    public function wellnessDetails(Request $request)
    {
        $data = array();
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'wellness_id' => ['required'],
                    'booking_date' => ['required'],
                    'branch_id' => ['required'],
                ],
                [
                    'wellness_id.required' => 'Wellness required',
                    'booking_date.required' => 'Booking date required',
                    'branch_id.required' => 'Branch required',
                ]
            );

            if (!$validator->fails()) {
                if (isset($request->booking_date) && isset($request->wellness_id) && isset($request->branch_id)) {
                    $bookingDate = Carbon::parse($request->booking_date);
                    $currentDate = Carbon::now();
                    $currentYear = Carbon::now()->year;

                    if ($bookingDate->year > $currentYear + 1) {
                        $data['status'] = 0;
                        $data['message'] = "Booking date cannot be more than 1 year in the future.";
                        return response($data);
                    }

                    if (!$bookingDate->isSameDay($currentDate) && $bookingDate->isPast()) {
                        $data['status'] = 0;
                        $data['message'] = "Booking date is older than the current date.";
                        return response($data);
                    }

                    $branch_wellness_id = Trn_Wellness_Branch::where('branch_id', $request->branch_id)->where('wellness_id', $request->wellness_id)->first();
                    if ($branch_wellness_id) {
                        $branch_name = Mst_Branch::where('branch_id', $request->branch_id)->where('is_active', 1)->value('branch_name');
                        $branch_address = Mst_Branch::where('branch_id', $request->branch_id)->where('is_active', 1)->value('branch_address');
                        $branchAddress = str_replace("\r\n", "\n", $branch_address);
                        $branchAddress = str_replace("\n", "", $branchAddress);
                        $wellness = Mst_Wellness::where('wellness_id', $request->wellness_id)->where('is_active', 1)->first();
                        $booking_date = PatientHelper::dateFormatDb($request->booking_date);

                        $day_of_week = PatientHelper::getWeekDay($request->booking_date);
                        $weekDayId = Mst_Master_Value::where('master_id', 3)->where('master_value', 'LIKE', '%' . $day_of_week . '%')->pluck('id')->first();

                        $is_available = PatientHelper::isWellnessAvailable($booking_date, $weekDayId, $request->branch_id, $request->wellness_id);

                        $wellness_details = [];
                        if (!empty($wellness)) {
                            $wellness_price = PatientHelper::amountDecimal($wellness->wellness_cost);
                            $wellness_offer_price = PatientHelper::amountDecimal($wellness->offer_price);
                            $wellness_image = config('admin_assets.wellness-image-path') . $wellness->wellness_image;
                            $is_offer = ($wellness_price > $wellness_offer_price) ? 1 : 0;

                            $inclusions = Mst_Wellness::where('wellness_id', $request->wellness_id)
                                ->where('is_active', 1)
                                ->pluck('wellness_inclusions')
                                ->map(function ($inclusion) {
                                    preg_match_all('/<li>(.*?)<\/li>/', $inclusion, $matches);
                                    return $matches[1];
                                })
                                ->flatten() // Flatten the nested arrays
                                ->map(function ($item) {
                                    return ['inclusion' => $item];
                                })
                                ->values();
                            

                            $wellness_details= [
                                'id' => $wellness->wellness_id,
                                'wellness_name' => $wellness->wellness_name,
                                'wellness_description' => $wellness->wellness_description,
                                'wellness_price' => $wellness_price,
                                'wellness_offer_price' => $wellness_offer_price,
                                'is_offer' => $is_offer,
                                'wellness_inclusions' => $inclusions,
                                'wellness_terms_conditions' => strip_tags($wellness->wellness_terms_conditions),
                                'is_available' => $is_available ?? 0,
                                'wellness_image' => $wellness_image,
                            ];

                            $booking_date = PatientHelper::dateFormatUser($request->booking_date);
                            $data['status'] = 1;
                            $data['message'] = "Data fetched";
                            $data['branch_id'] = $request->branch_id;
                            $data['branch_name'] = $branch_name;
                            $data['branch_address'] = $branchAddress;
                            $data['booking_date'] = $booking_date;
                            $data['data'] = $wellness_details;
                            return response()->json($data);
                        } else {
                            $data['status'] = 0;
                            $data['message'] = "No details found";
                            return response()->json($data);
                        }
                    } else {
                        // this wellness is not included in this branch.
                        $data['status'] = 0;
                        $data['message'] = "The selected wellness is not available in this branch";
                        return response($data);
                    }
                } else {
                    $data['status'] = 0;
                    $data['message'] = "Please fill mandatory fields";
                    return response()->json($data);
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

    // new api for wellness avilability 
    public function wellnessAvailability(Request $request)
    {
        $data = array();
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'wellness_id' => ['required'],
                    'booking_date' => ['required'],
                    'branch_id' => ['required'],
                ],
                [
                    'wellness_id.required' => 'Wellness required',
                    'booking_date.required' => 'Booking date required',
                    'branch_id.required' => 'Branch required',
                ]
            );

            if (!$validator->fails()) {
                if (isset($request->booking_date) && isset($request->wellness_id) && isset($request->branch_id)) {
                    $bookingDate = Carbon::parse($request->booking_date);
                    $currentDate = Carbon::now();
                    $currentYear = Carbon::now()->year;

                    if ($bookingDate->year > $currentYear + 1) {
                        $data['status'] = 0;
                        $data['message'] = "Booking date cannot be more than 1 year in the future.";
                        return response($data);
                    }

                    if (!$bookingDate->isSameDay($currentDate) && $bookingDate->isPast()) {
                        $data['status'] = 0;
                        $data['message'] = "Booking date is older than the current date.";
                        return response($data);
                    }

                    $branch_wellness_id = Trn_Wellness_Branch::where('branch_id', $request->branch_id)->where('wellness_id', $request->wellness_id)->first();
                    if ($branch_wellness_id) {
                        $day_of_week = PatientHelper::getWeekDay($request->booking_date);
                        $weekDayId = Mst_Master_Value::where('master_id', 3)->where('master_value', 'LIKE', '%' . $day_of_week . '%')->pluck('id')->first();
                        $booking_date = PatientHelper::dateFormatDb($request->booking_date);

                        $finalSlots = PatientHelper::wellnessAvailability($booking_date, $weekDayId, $request->branch_id, $request->wellness_id);

                        $wellness = Mst_Wellness::where('wellness_id', $request->wellness_id)
                            ->where('is_active', 1)->first();
                        if (!$finalSlots) {
                            // sorry no slots available 
                            $data['status'] = 1;
                            $data['message'] = "Data fetched.";
                            $data['wellness_name'] = $wellness->wellness_name;
                            $data['data'] = [];
                            return response()->json($data);
                        }

                        $available_slots = PatientHelper::availableSlots($finalSlots, $booking_date, $weekDayId, $request->wellness_id);

                        // Check if $available_slots is an array before further processing
                        if (is_array($available_slots)) {
                            // Initialize an associative array to keep track of unique time_slot_ids
                            $uniqueTimeSlots = [];

                            // Iterate through each slot in $available_slots
                            foreach ($available_slots as $slot) {
                                // dd($slot);
                                $time_slot_id = $slot['time_slot_id'];

                                // Check if this time_slot_id is not already in $uniqueTimeSlots
                                if (!isset($uniqueTimeSlots[$time_slot_id])) {
                                    // Remove the "therapy_room_id" key from the slot
                                    unset($slot['therapy_room_id']);

                                    // Check if "is_available" is 0, update it to 0
                                    if ($slot['is_available'] == 0) {
                                        $uniqueTimeSlots[$time_slot_id] = $slot;
                                    } else {
                                        // Check if any other slot with the same 'time_slot_id' has 'is_available' as 1
                                        $otherSlots = array_filter($uniqueTimeSlots, function ($otherSlot) use ($time_slot_id) {
                                            return $otherSlot['time_slot_id'] === $time_slot_id && $otherSlot['is_available'] == 1;
                                        });

                                        // If any other slot has 'is_available' as 1, update it to 1
                                        if (!empty($otherSlots)) {
                                            $slot['is_available'] = 1;
                                        }

                                        $uniqueTimeSlots[$time_slot_id] = $slot;
                                    }
                                } else {
                                    // Slot with the same time_slot_id already exists
                                    // Compare the "is_available" values and update it to 1 if any of them is 1
                                    if ($uniqueTimeSlots[$time_slot_id]['is_available'] == 0 && $slot['is_available'] == 1) {
                                        $uniqueTimeSlots[$time_slot_id]['is_available'] = 1;
                                    }
                                }
                            }

                            // Re-index the array to have consecutive numeric keys
                            $finalSlots = array_values($uniqueTimeSlots);

                            // Define a custom comparison function for sorting
                            usort($finalSlots, function ($a, $b) {
                                return strtotime($a['time_from']) - strtotime($b['time_from']);
                            });
                        } else {
                            // Handle the case where $available_slots is not an array
                            $data['status'] = 0;
                            $data['message'] = "Sorry, no slots available";
                            return response()->json($data);
                        }


                        $branch_name = Mst_Branch::where('branch_id', $request->branch_id)->where('is_active', 1)->value('branch_name');
                        $wellness = Mst_Wellness::where('wellness_id', $request->wellness_id)
                            ->where('is_active', 1)->first();

                        $wellness_details = [];
                        if (!empty($wellness)) {
                            // $wellness_details[] = [
                            //     'id' => $wellness->wellness_id,
                            //     'wellness_name' => $wellness->wellness_name,
                            //     'wellness_description' => $wellness->wellness_description,
                            //     'wellness_cost' => $wellness->wellness_cost,
                            //     'wellness_inclusions' => strip_tags($wellness->wellness_inclusions),
                            //     'wellness_terms_conditions' => strip_tags($wellness->wellness_terms_conditions),
                            // ];

                            $data['status'] = 1;
                            $data['message'] = "Data fetched";
                            // $data['branch_id'] = $request->branch_id;
                            // $data['branch_name'] = $branch_name;
                            // $data['booking_date'] = $request->booking_date;
                            $data['wellness_name'] = $wellness->wellness_name;
                            $data['data'] = $finalSlots;
                            return response()->json($data);
                        } else {
                            $data['status'] = 0;
                            $data['message'] = "No details found";
                            return response()->json($data);
                        }
                    } else {
                        // this wellness is not included in this branch.
                        $data['status'] = 0;
                        $data['message'] = "The selected wellness is not available in this branch";
                        return response($data);
                    }
                } else {
                    $data['status'] = 0;
                    $data['message'] = "Please fill mandatory fields";
                    return response()->json($data);
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

    // wellness_bookig_details 
    public function wellnessBookingDetails(Request $request)
    {
        $data = array();
        try {
            // Validating request parameters
            $validator = Validator::make(
                $request->all(),
                [
                    'branch_id' => ['required'],
                    'wellness_id' => ['required'],
                    'booking_date' => ['required'],
                    'slot_id' => ['required'],
                    'limit' => ['integer'],
                    'page_number' => ['integer'],
                ],
                [
                    'wellness_id.required' => 'Wellness required',
                    'branch_id.required' => 'Branch required',
                    'slot_id.required' => 'Slot required',
                    'booking_date.required' => 'Booking date required',
                    'limit.integer' => 'Limit must be an integer',
                    'page_number.integer' => 'Page number must be an integer',
                ]
            );

            // If validation fails, return error response
            if ($validator->fails()) {
                $data['status'] = 0;
                $data['errors'] = $validator->errors();
                $data['message'] = "Validation errors";
                return response($data);
            }

            if (isset($request->wellness_id) && isset($request->branch_id) && isset($request->booking_date) && isset($request->slot_id)) {
                $bookingDate = Carbon::parse($request->booking_date);
                $currentDate = Carbon::now();
                $currentYear = Carbon::now()->year;

                if ($bookingDate->year > $currentYear + 1) { // Allow up to 1 year in the future
                    $data['status'] = 0;
                    $data['message'] = "Booking date cannot be more than 1 year in the future.";
                    return response($data);
                }

                // Check if the booking date is in the past and not the same day as the current date
                if (!$bookingDate->isSameDay($currentDate) && $bookingDate->isPast()) {
                    $data['status'] = 0;
                    $data['message'] = "Booking date is older than the current date.";
                    return response($data);
                } else {
                    $patient_id = Auth::id();

                    $family_details = array();

                    $family_details = PatientHelper::getFamilyDetails($patient_id);

                    $day_of_week = PatientHelper::getWeekDay($request->booking_date);
                    $weekDayId = Mst_Master_Value::where('master_value', 'LIKE', '%' . $day_of_week . '%')->pluck('id')->first();

                    // Recheck the availability of the specified time slot for booking on the given date and for the particular wellness
                    $weekDayId = Mst_Master_Value::where('master_id', 3)->where('master_value', 'LIKE', '%' . $day_of_week . '%')->pluck('id')->first();
                    $booking_date = PatientHelper::dateFormatDb($request->booking_date);
                    $finalSlots = PatientHelper::wellnessAvailability($booking_date, $weekDayId, $request->branch_id, $request->wellness_id);

                    if (!$finalSlots) {
                        $data['status'] = 0;
                        $data['message'] = "Sorry, no slots available";
                        return response($data);
                    }
                    $available_slots = PatientHelper::availableSlots($finalSlots, $booking_date, $weekDayId, $request->wellness_id);

                    $is_available = 0;
                    if ($available_slots) {
                        foreach ($available_slots as $available_slot) {
                            if ($available_slot['time_slot_id'] == $request->slot_id && $available_slot['is_available'] == 1) {
                                $is_available = 1;
                                break;
                            }
                        }
                    }

                    if ($is_available == 0) {
                        // sorry no slots available 
                        $data['status'] = 0;
                        $data['message'] = "Sorry, no slots available";
                        return response($data);
                    }
                    // Pagination logic
                    $limit = $request->input('limit', 5); // Default limit is 5
                    $page_number = $request->input('page_number', 1); // Default page number is 1

                    $family_details_collection = collect($family_details);
                    $paginate_family_details = $family_details_collection->slice(($page_number - 1) * $limit, $limit)->all();

                    // Prepare the success response with pagination details
                    $data['status'] = 1;
                    $data['message'] = "Data Fetched";
                    $data['data'] = array_values($paginate_family_details);
                    $data['pagination_details'] = [
                        'current_page' => intval($page_number),
                        'total_records' => count($family_details),
                        'total_pages' => ceil(count($family_details) / $limit),
                        'per_page' => $limit,
                        // 'first_page_url' => $page_number > 1 ? url(request()->path() . '?page_number=1&limit=' . $limit) : null,
                        // 'last_page_url' => $page_number < ceil(count($family_details) / $limit) ? url(request()->path() . '?page_number=' . ceil(count($family_details) / $limit) . '&limit=' . $limit) : null,
                        // 'next_page_url' => $page_number < ceil(count($family_details) / $limit) ? url(request()->path() . '?page_number=' . ($page_number + 1) . '&limit=' . $limit) : null,
                        // 'prev_page_url' => $page_number > 1 ? url(request()->path() . '?page_number=' . ($page_number - 1) . '&limit=' . $limit) : null,
                    ];

                    return response($data);
                }
            } else {
                $data['status'] = 0;
                $data['message'] = "Please fill mandatory fields";
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

    public function wellnessSummary(Request $request)
    {
        $data = array();
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'branch_id' => ['required'],
                    'wellness_id' => ['required'],
                    'family_member_id' => ['required'],
                    'booking_date' => ['required'],
                    //'slot_id' => ['required'],
                    'yourself' => ['required'],
                ],
                [
                    'branch_id.required' => 'Branch required',
                    'wellness_id.required' => 'Wellness required',
                    'family_member_id.required' => 'Family member id required',
                    'booking_date.required' => 'Booking date required',
                    'slot_id.required' => 'Slot required',
                    'yourself.required' => 'Yourself required',
                ]
            );

            if (!$validator->fails()) {
                if (isset($request->branch_id) && isset($request->wellness_id) && isset($request->yourself) && isset($request->booking_date)) {
                    $bookingDate = Carbon::parse($request->booking_date);
                    $currentDate = Carbon::now();
                    $currentYear = Carbon::now()->year;

                    if ($bookingDate->year > $currentYear + 1) {
                        $data['status'] = 0;
                        $data['message'] = "Booking date cannot be more than 1 year in the future.";
                        return response($data);
                    }

                    if (!$bookingDate->isSameDay($currentDate) && $bookingDate->isPast()) {
                        $data['status'] = 0;
                        $data['message'] = "Booking date is older than the current date.";
                        return response($data);
                    }
                  
                    $slotDetails = Mst_TimeSlot::where('id', $request->slot_id)
                        ->first();
                    if($slotDetails)
                    {
                        $time_from = date('h:i A', strtotime($slotDetails->time_from));
                        $time_to = date('h:i A', strtotime($slotDetails->time_to));
                        
                    }
                    else
                    {
                        $time_from = '';
                        $time_to = ''; 
                    }
                    

                    $patient_id = Auth::id();
                    $branch_name = Mst_Branch::where('branch_id', $request->branch_id)->where('is_active', 1)->value('branch_name');
                    $wellness = Mst_Wellness::where('wellness_id', $request->wellness_id)->where('is_active', 1)->first();
                    $wellness_details = [];
                    if (!empty($wellness)) {
                        $fee = PatientHelper::amountDecimal($wellness->wellness_cost);

                        $wellness_details = [
                            'id' => $wellness->wellness_id,
                            'wellness_name' => $wellness->wellness_name,
                            'wellness_image' => config('admin_assets.wellness-image-path') . $wellness->wellness_image,
                            // 'wellness_description' => $wellness->wellness_description,
                            // 'wellness_cost' => $fee,
                            // 'wellness_inclusions' => strip_tags($wellness->wellness_inclusions),
                            // 'wellness_terms_conditions' => strip_tags($wellness->wellness_terms_conditions),
                            'branch_name' => $branch_name,
                            'branch_id'=>$request->branch_id
                        ];
                    }

                    $patientDetails = [];
                    $day_of_week = PatientHelper::getWeekDay($request->booking_date);
                    $weekDayId = Mst_Master_Value::where('master_id', 3)->where('master_value', 'LIKE', '%' . $day_of_week . '%')->pluck('id')->first();
                    $booking_date = PatientHelper::dateFormatDb($request->booking_date);
                    // $finalSlots = PatientHelper::wellnessAvailability($booking_date, $weekDayId, $request->branch_id, $request->wellness_id);

                    // if (!$finalSlots) {
                    //     $data['status'] = 0;
                    //     $data['message'] = "Sorry, no slots available";
                    //     return response($data);
                    // }
                    //$available_slots = PatientHelper::availableSlots($finalSlots, $booking_date, $weekDayId, $request->wellness_id);

                    // $is_available = 0;
                    // if ($available_slots) {
                    //     foreach ($available_slots as $available_slot) {
                    //         if ($available_slot['time_slot_id'] == $request->slot_id && $available_slot['is_available'] == 1) {
                    //             $is_available = 1;
                    //             break;
                    //         }
                    //     }
                    // }

                    // if ($is_available == 0) {
                    //     // sorry no slots available 
                    //     $data['status'] = 0;
                    //     $data['message'] = "Sorry, no slots available";
                    //     return response($data);
                    // }

                    if ($request->yourself == 1) {
                        $accountHolder = Mst_Patient::where('id', $patient_id)->first();
                        $patient_gender_name = Mst_Master_Value::where('id', $accountHolder->patient_gender)->value('master_value');
                        $patientDetails = [
                            'member_id' => $patient_id,
                            'family_member_id' => 0,
                            'yourself' => 1,
                            'booking_date' => Carbon::parse($request->booking_date)->format('d-m-Y'),
                            //'slot' => $time_from . ' - ' . $time_to,
                            // 'slot_id' => $slotDetails->id,
                            'preffered_slot'=>$request->preffered_slot??0,
                            'booking_source'=>$request->booking_source??'',
                            'patient_name' => $accountHolder->patient_name,
                            'dob' => Carbon::parse($accountHolder->patient_dob)->format('d-m-Y'),
                            'gender' => $patient_gender_name,
                            'mobile_number' => $accountHolder->patient_mobile,
                            'email_address' => $accountHolder->patient_email,
                        ];
                    } else {
                        if (isset($request->family_member_id)) {

                            $member = Trn_Patient_Family_Member::join('mst_patients', 'trn_patient_family_member.patient_id', 'mst_patients.id')
                                ->join('mst_master_values', 'trn_patient_family_member.gender_id', 'mst_master_values.id')
                                ->select('trn_patient_family_member.id', 'trn_patient_family_member.mobile_number', 'trn_patient_family_member.email_address', 'trn_patient_family_member.family_member_name', 'mst_master_values.master_value as gender_name', 'trn_patient_family_member.date_of_birth')
                                ->where('trn_patient_family_member.patient_id', $patient_id)
                                ->where('trn_patient_family_member.id', $request->family_member_id)
                                ->where('trn_patient_family_member.is_active', 1)
                                ->first();

                            $patientDetails = [
                                'member_id' => $patient_id,
                                'family_member_id' => $member->id,
                                'yourself' => 0,
                                'booking_date' => Carbon::parse($request->booking_date)->format('d-m-Y'),
                                // 'slot' => $time_from . ' - ' . $time_to,
                                //  'slot_id' => $slotDetails->id,
                                'preffered_slot'=>$request->preffered_slot??0,
                                'booking_source'=>$request->booking_source??'',
                                'patient_name' => $member->family_member_name,
                                'dob' => Carbon::parse($member->date_of_birth)->format('d-m-Y'),
                                'gender' => $member->gender_name,
                                'mobile_number' => $member->mobile_number,
                                'email_address' => $member->email_address,
                            ];
                        } else {
                            $data['status'] = 0;
                            $data['message'] = "Please provide member_id";
                            return response()->json($data);
                        }
                    }
                    $fee = PatientHelper::amountDecimal($wellness->wellness_cost);

                    $paymentDetails = [
                        'consultation_fee' => $fee,
                        'total_amount' => $fee,
                    ];

                    $data['status'] = 1;
                    $data['message'] = "Data Fetched";
                    $data['wellness_details'] = $wellness_details;
                    $data['patient_details'] = $patientDetails;
                    $data['payment_details'] = $paymentDetails;
                    return response($data);
                } else {
                    $data['status'] = 0;
                    $data['message'] = "Please fill mandatory fields";
                    return response()->json($data);
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

    public function wellnessConfirmation(Request $request)
    {
        $data = array();
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'wellness_id' => ['required'],
                    'family_member_id' => ['required'],
                    //'slot_id' => ['required'],
                    'booking_date' => ['required'],
                    'yourself' => ['required'],
                    'branch_id' => ['required'],
                    'reschedule_key' => ['required'],
                ],
                [
                    'wellness_id.required' => 'Wellness required',
                    'family_member_id.required' => 'Member id required',
                    'slot_id.required' => 'Slot required',
                    'booking_date.required' => 'Booking date required',
                    'yourself.required' => 'Yourself required',
                    'branch_id.required' => 'Branch required',
                    'reschedule_key.required' => 'Reschedule key required',
                ]
            );

            if (!$validator->fails()) {
                if (isset($request->booking_date)) {

                    $bookingDate = Carbon::parse($request->booking_date);
                    $currentDate = Carbon::now();
                    $currentYear = Carbon::now()->year;

                    $patient_id = Auth::id();
                    $accountHolder = Mst_Patient::where('mst_patients.id', $patient_id)->first();
                    if (!$accountHolder) {
                        $data['status'] = 0;
                        $data['message'] = "User does not exist";
                        return response($data);
                    }

                    if ($bookingDate->year > $currentYear + 1) {
                        $data['status'] = 0;
                        $data['message'] = "Booking date cannot be more than 1 year in the future.";
                        return response($data);
                    }

                    if (!$bookingDate->isSameDay($currentDate) && $bookingDate->isPast()) {
                        $data['status'] = 0;
                        $data['message'] = "Booking date is older than the current date.";
                        return response($data);
                    }

                    if ($request->reschedule_key == 1) {
                        if (!$request->has('booking_id')) {
                            $data['status'] = 0;
                            $data['message'] = "Booking id is required";
                            return response($data);
                        } else {
                            $booking_id = intval($request->booking_id);
                        }
                    }

                    $wellness_duration = Mst_Wellness::where('wellness_id', $request->wellness_id)->value('wellness_duration');

                    $day_of_week = PatientHelper::getWeekDay($request->booking_date);
                    $weekDayId = Mst_Master_Value::where('master_id', 3)->where('master_value', 'LIKE', '%' . $day_of_week . '%')->pluck('id')->first();
                    $booking_date = PatientHelper::dateFormatDb($request->booking_date);
                    $finalSlots = PatientHelper::wellnessAvailability($booking_date, $weekDayId, $request->branch_id, $request->wellness_id);
                    // if (!$finalSlots) {
                    //     $data['status'] = 0;
                    //     $data['message'] = "Sorry, no slots available";
                    //     return response($data);
                    // }
                    $available_slots = PatientHelper::availableSlots($finalSlots, $booking_date, $weekDayId, $request->wellness_id);
                    $therapy_room_id = 0;
                    // $is_available = 0;
                    // $therapy_room_id = 0;
                    $next_remaining_time = 0;

                    // if ($available_slots) {
                    //     foreach ($available_slots as $available_slot) {
                    //         if ($available_slot['time_slot_id'] == $request->slot_id && $available_slot['is_available'] == 1) {
                    //             $therapy_room_id = $available_slot['therapy_room_id'];

                    //             $lastInsertedBooking = Trn_Consultation_Booking::where('booking_date', $booking_date)
                    //                 ->where('therapy_room_id', $therapy_room_id)
                    //                 ->where('booking_type_id', '!=', 84)
                    //                 ->where('time_slot_id', $request->slot_id)
                    //                 ->whereIn('booking_status_id', [87, 88, 89])
                    //                 ->latest('created_at')
                    //                 ->first();

                    //             if ($lastInsertedBooking) {
                    //                 if ($lastInsertedBooking->remaining_time >= $wellness_duration) {
                    //                     $is_available = 1;
                    //                     $therapy_room_id = $available_slot['therapy_room_id'];
                    //                     $next_remaining_time = $lastInsertedBooking->remaining_time - $wellness_duration;
                    //                     $next_remaining_time = max(0, $next_remaining_time);
                    //                 }
                    //             } else {
                    //                 $slotDetails = Mst_TimeSlot::where('id', $request->slot_id)
                    //                     ->first();

                    //                 $time_from = strtotime($slotDetails->time_from);
                    //                 $time_to = strtotime($slotDetails->time_to);

                    //                 // Calculate duration in minutes
                    //                 $duration_minutes = round(($time_to - $time_from) / 60);
                    //                 if ($duration_minutes >= $wellness_duration) {
                    //                     $is_available = 1;
                    //                     $therapy_room_id = $available_slot['therapy_room_id'];
                    //                     $next_remaining_time = $duration_minutes - $wellness_duration;
                    //                     $next_remaining_time = max(0, $next_remaining_time);
                    //                 }
                    //             }
                    //             break;
                    //         }
                    //     }
                    // }

                   /* if ($is_available == 0) {
                        // sorry no slots available
                        $data['status'] = 0;
                        $data['message'] = "Sorry, no slots available";
                        return response($data);
                    }*/

                    $wellness = Mst_Wellness::where('wellness_id', $request->wellness_id)->where('is_active', 1)->first();

                    $yourself = $request->yourself;
                    $booked_for = $accountHolder->patient_name;
                    $newRecordData = [
                        'booking_type_id' => 85,
                        'wellness_id' => $request->wellness_id,
                        'patient_id' => $patient_id,
                        'branch_id' => $request->branch_id,
                        'booking_date' => $booking_date,
                        'booking_status_id' => 88,
                        'booking_fee' => $wellness->wellness_cost,
                        'is_for_family_member' => 0,
                        'family_member_id' => 0,
                        //'time_slot_id' => $request->slot_id,
                        'therapy_room_id' => $therapy_room_id,
                        'remaining_time' => $next_remaining_time,
                        'created_at' => Carbon::now(),
                    ];

                    if ($yourself == 0) {
                        if (isset($request->family_member_id)) {
                            $familyMemberData = [
                                'is_for_family_member' => 1,
                                'family_member_id' => $request->family_member_id,
                            ];
                            $newRecordData = $familyMemberData + $newRecordData;

                            $bookedMemberDetails = Trn_Patient_Family_Member::where('id', $request->family_member_id)->first();
                            $booked_for = $bookedMemberDetails->family_member_name;
                        } else {
                            $data['status'] = 0;
                            $data['message'] = "Family member is required";
                            return response($data);
                        }
                    }

                    // Check for any bookings in the same time slot
                    // $checkSameSlot = Trn_Consultation_Booking::where('patient_id', Auth::id())
                    //     ->where('booking_date', $newRecordData['booking_date'])
                    //     ->where('time_slot_id', $request->slot_id)
                    //     ->where('trn_consultation_bookings.booking_status_id', '!=', 90)
                    //     ->where('family_member_id', $newRecordData['family_member_id'])
                    //     ->first();

                    // // Check for the same wellness for two different slots on the same day for the same patient
                    // $checkSameWellness = Trn_Consultation_Booking::where('patient_id', Auth::id())
                    //     ->where('booking_date', $newRecordData['booking_date'])
                    //     ->where('wellness_id', $newRecordData['wellness_id'])
                    //     ->where('time_slot_id', '!=', $request->slot_id)
                    //     ->where('trn_consultation_bookings.booking_status_id', '!=', 90)
                    //     ->where('family_member_id', $newRecordData['family_member_id'])
                    //     ->first();

                    // if ($checkSameSlot) {
                    //     $data['status'] = 0;
                    //     $data['message'] = $accountHolder->patient_name . ", you already have a booking for this time slot.";
                    //     return response($data);
                    //}

                    // if ($checkSameWellness) {
                    //     $data['status'] = 0;
                    //     $data['message'] = $accountHolder->patient_name . ", you already have a booking for this wellness.";
                    //     return response($data);
                    // }

                    // Create new data 
                    if (isset($booking_id)) {
                        $bookingDetails = Trn_Consultation_Booking::where('id', $booking_id)->first();
                        if ($bookingDetails->booking_status_id == 89 || ($bookingDetails->booking_status_id == 90 && $bookingDetails->booking_date < Carbon::now())) {
                            $createdRecord = Trn_Consultation_Booking::create($newRecordData);
                            $lastInsertedId = $createdRecord->id;
                            $leadingZeros = str_pad('', 3 - strlen($lastInsertedId), '0', STR_PAD_LEFT);
                            $bookingRefNo = 'BRN' . $leadingZeros . $lastInsertedId;
                            $updateConsultation = Trn_Consultation_Booking::where('id', $lastInsertedId)->update([
                                'updated_at' => Carbon::now(),
                                'booking_reference_number' => $bookingRefNo,
                                'booking_source'=>$request->booking_source,
                                'preffered_slot'=>$request->preffered_slot,
                                
                            ]);
                            $patientDevice = Trn_Patient_Device_Tocken::where('patient_id', $patient_id)->get();
                            $booking_date = PatientHelper::dateFormatUser($request->booking_date);

                            if ($patientDevice) {
                                $title = 'Wellness Booking';
                                $body = ' Here is a wellness booking for ' . $wellness->wellness_name . ' on ' . $booking_date . '.';
                                $clickAction = "PatientWellnessBooking";
                                $type = "Wellness Booking";

                                // Save notification to the patient's notification table
                                $notificationCreate = Trn_Notification::create([
                                    'patient_id' => Auth::id(),
                                    'title' => $title,
                                    'content' => $body,
                                    'read_status' => 0,
                                    'created_at' => Carbon::now(),
                                    'updated_at' => Carbon::now(),
                                ]);
                                foreach ($patientDevice as $pdt) {
                                    // Send notification to the patient's device
                                    $response =  DeviceTockenHelper::patientNotification($pdt->patient_device_token, $title, $body, $clickAction, $type);
                                }
                            }
                        } else {
                            $updateRecord = Trn_Consultation_Booking::where('id', $request->booking_id)->update($newRecordData);
                            $bookingRefNo = $bookingDetails->booking_reference_number;
                            $lastInsertedId = intval($request->booking_id);
                            $patientDevice = Trn_Patient_Device_Tocken::where('patient_id', $patient_id)->get();
                            $booking_date = PatientHelper::dateFormatUser($request->booking_date);

                            if ($patientDevice) {
                                $title = 'Wellness Booking Rescheduled';
                                $body = ' Rescheduled the booking for ' . $wellness->wellness_name . ' on ' . $booking_date . '.';
                                $clickAction = "PatientBookingReschedule";
                                $type = "Reschedule";

                                // Save notification to the patient's notification table
                                $notificationCreate = Trn_Notification::create([
                                    'patient_id' => Auth::id(),
                                    'title' => $title,
                                    'content' => $body,
                                    'read_status' => 0,
                                    'created_at' => Carbon::now(),
                                    'updated_at' => Carbon::now(),
                                ]);
                                foreach ($patientDevice as $pdt) {
                                    // Send notification to the patient's device
                                    $response =  DeviceTockenHelper::patientNotification($pdt->patient_device_token, $title, $body, $clickAction, $type);
                                }
                            }
                        }
                    } else {
                        $createdRecord = Trn_Consultation_Booking::create($newRecordData);
                        $lastInsertedId = $createdRecord->id;
                        $leadingZeros = str_pad('', 3 - strlen($lastInsertedId), '0', STR_PAD_LEFT);
                        $bookingRefNo = 'BRN' . $leadingZeros . $lastInsertedId;
                        $updateConsultation = Trn_Consultation_Booking::where('id', $lastInsertedId)->update([
                            'updated_at' => Carbon::now(),
                            'booking_reference_number' => $bookingRefNo,
                            'booking_source'=>$request->booking_source,
                            'preffered_slot'=>$request->preffered_slot,
                        ]);

                        $patientDevice = Trn_Patient_Device_Tocken::where('patient_id', $patient_id)->get();
                        if ($patientDevice) {
                            $title = 'Wellness Booking';
                            $body = ' Here is a wellness booking for ' . $wellness->wellness_name . ' on ' . $booking_date . '.';
                            $clickAction = "PatientWellnessBooking";
                            $type = "Wellness Booking";

                            // Save notification to the patient's notification table
                            $notificationCreate = Trn_Notification::create([
                                'patient_id' => Auth::id(),
                                'title' => $title,
                                'content' => $body,
                                'read_status' => 0,
                                'created_at' => Carbon::now(),
                                'updated_at' => Carbon::now(),
                            ]);
                            foreach ($patientDevice as $pdt) {
                                // Send notification to the patient's device
                                $response =  DeviceTockenHelper::patientNotification($pdt->patient_device_token, $title, $body, $clickAction, $type);
                            }
                        }
                    }
                    // Fetch details of the selected time slot for the booking
                    // $slotDetails = Mst_TimeSlot::where('id', $request->slot_id)->first();

                    // // Convert time format from database format to user-readable format
                    // $time_from = date('h:i A', strtotime($slotDetails->time_from));
                    // $time_to = date('h:i A', strtotime($slotDetails->time_to));

                    $booking_details = [];
                    $booking_date = PatientHelper::dateFormatUser($request->booking_date);
                    $booking_details = [
                        'booking_id' => $lastInsertedId,
                        'member_name' => $accountHolder->patient_name,
                        'booking_referance_number' => $bookingRefNo,
                        'booking_to' => $wellness->wellness_name,
                        'booking_for' => $booked_for,
                        'booking_date' => $booking_date,
                       // 'time_slot' => $time_from . ' - ' . $time_to,
                    ];

                    $data['status'] = 1;
                    $data['message'] = $accountHolder->patient_name . ", your booking has been confirmed.";
                    $data['booking_details'] = $booking_details;
                    return response($data);
                } else {
                    $data['status'] = 0;
                    $data['message'] = "Please fill mandatory fields";
                    return response()->json($data);
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

    // Reschedule or Rebooking wellness booking 
    public function wellnessReSchedule(Request $request)
    {
        // Currently not in use 
        $data = array();
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'wellness_id' => ['required'],
                    'family_member_id' => ['required'],
                    'slot_id' => ['required'],
                    'booking_date' => ['required'],
                    'yourself' => ['required'],
                    'booking_id' => ['required'],
                    'branch_id' => ['required'],
                ],
                [
                    'wellness_id.required' => 'Wellness required',
                    'family_member_id.required' => 'Member id required',
                    'slot_id.required' => 'Slot required',
                    'booking_date.required' => 'Booking date required',
                    'yourself.required' => 'Yourself required',
                    'booking_id.required' => 'Booking id is required',
                    'branch_id.required' => 'Branch required',
                ]
            );

            if (!$validator->fails()) {
                if (isset($request->wellness_id) && isset($request->family_member_id) && isset($request->slot_id) && isset($request->booking_date) && isset($request->yourself) && isset($request->booking_id) && isset($request->branch_id)) {

                    $bookingDate = Carbon::parse($request->booking_date);
                    $currentDate = Carbon::now();
                    $currentYear = Carbon::now()->year;

                    if ($bookingDate->year > $currentYear + 1) {
                        $data['status'] = 0;
                        $data['message'] = "Booking date cannot be more than 1 year in the future.";
                        return response($data);
                    }
                    if (!$bookingDate->isSameDay($currentDate) && $bookingDate->isPast()) {
                        $data['status'] = 0;
                        $data['message'] = "Booking date is older than the current date.";
                        return response($data);
                    }

                    $wellness_duration = Mst_Wellness::where('wellness_id', $request->wellness_id)->value('wellness_duration');
                    $booking_date = PatientHelper::dateFormatDb($request->booking_date);

                    $check_same_slot = Trn_Consultation_Booking::where('id', $request->booking_id)->first();
                    if (($check_same_slot->booking_status_id != 89 && $check_same_slot->time_slot_id == $request->slot_id)) {
                        // Code to handle rescheduling when the time slot is the same
                        $therapy_room_id = $check_same_slot->therapy_room_id;
                        $next_remaining_time = $check_same_slot->remaining_time;
                    } else {
                        $day_of_week = PatientHelper::getWeekDay($request->booking_date);
                        $weekDayId = Mst_Master_Value::where('master_id', 3)->where('master_value', 'LIKE', '%' . $day_of_week . '%')->pluck('id')->first();
                        $finalSlots = PatientHelper::wellnessAvailability($booking_date, $weekDayId, $request->branch_id, $request->wellness_id);

                        if (!$finalSlots) {
                            $data['status'] = 0;
                            $data['message'] = "Sorry, no slots available";
                            return response($data);
                        }

                        $is_available = 0;
                        $therapy_room_id = 0;
                        $next_remaining_time = 0;

                        $available_slots = PatientHelper::availableSlots($finalSlots, $booking_date, $weekDayId, $request->wellness_id);
                        if ($available_slots) {
                            foreach ($available_slots as $available_slot) {
                                if ($available_slot['time_slot_id'] == $request->slot_id && $available_slot['is_available'] == 1) {
                                    $therapy_room_id = $available_slot['therapy_room_id'];

                                    $lastInsertedBooking = Trn_Consultation_Booking::where('booking_date', $booking_date)
                                        ->where('therapy_room_id', $therapy_room_id)
                                        ->where('booking_type_id', '!=', 84)
                                        ->where('time_slot_id', $request->slot_id)
                                        ->whereIn('booking_status_id', [87, 88, 89])
                                        ->latest('created_at')
                                        ->first();

                                    if ($lastInsertedBooking) {
                                        if ($lastInsertedBooking->remaining_time >= $wellness_duration) {
                                            $is_available = 1;
                                            $therapy_room_id = $available_slot['therapy_room_id'];
                                            $next_remaining_time = $lastInsertedBooking->remaining_time - $wellness_duration;
                                            $next_remaining_time = max(0, $next_remaining_time);
                                        }
                                    } else {
                                        $slotDetails = Mst_TimeSlot::where('id', $request->slot_id)
                                            ->first();

                                        $time_from = strtotime($slotDetails->time_from);
                                        $time_to = strtotime($slotDetails->time_to);

                                        // Calculate duration in minutes
                                        $duration_minutes = round(($time_to - $time_from) / 60);
                                        if ($duration_minutes >= $wellness_duration) {
                                            $is_available = 1;
                                            $therapy_room_id = $available_slot['therapy_room_id'];
                                            $next_remaining_time = $duration_minutes - $wellness_duration;
                                            $next_remaining_time = max(0, $next_remaining_time);
                                        }
                                    }
                                    break;
                                }
                            }
                        }
                        if ($is_available == 0) {
                            // sorry no slots available 
                            $data['status'] = 0;
                            $data['message'] = "Sorry, no slots available";
                            return response($data);
                        }
                    }

                    $patient_id = Auth::id();
                    $wellness = Mst_Wellness::where('wellness_id', $request->wellness_id)->where('is_active', 1)->first();
                    $accountHolder = Mst_Patient::where('mst_patients.id', $patient_id)->first();
                    if (!$accountHolder) {
                        $data['status'] = 0;
                        $data['message'] = "User does not exist";
                        return response($data);
                    }

                    $yourself = $request->yourself;
                    $booked_for = $accountHolder->patient_name;
                    $newRecordData = [
                        'booking_type_id' => 85,
                        'wellness_id' => $request->wellness_id,
                        'patient_id' => $patient_id,
                        'branch_id' => $request->branch_id,
                        'booking_date' => $booking_date,
                        'booking_status_id' => 88,
                        'booking_fee' => $wellness->wellness_cost,
                        'is_for_family_member' => 0,
                        'family_member_id' => 0,
                        'time_slot_id' => $request->slot_id,
                        'therapy_room_id' => $therapy_room_id,
                        'remaining_time' => $next_remaining_time,
                        'created_at' => Carbon::now(),
                    ];

                    if ($yourself == 0) {
                        if (isset($request->family_member_id)) {
                            $familyMemberData = [
                                'is_for_family_member' => 1,
                                'family_member_id' => $request->family_member_id,
                            ];
                            $newRecordData = $familyMemberData + $newRecordData;

                            $bookedMemberDetails = Trn_Patient_Family_Member::where('id', $request->family_member_id)->first();
                            $booked_for = $bookedMemberDetails->family_member_name;
                        } else {
                            $data['status'] = 0;
                            $data['message'] = "Family member is required";
                            return response($data);
                        }
                    }

                    // Check for any bookings in the same time slot
                    $checkSameSlot = Trn_Consultation_Booking::where('patient_id', Auth::id())
                        ->where('booking_date', $newRecordData['booking_date'])
                        ->where('time_slot_id', $request->slot_id)
                        ->where('id', '!=', $request->booking_id)
                        ->where('family_member_id', $newRecordData['family_member_id'])
                        ->first();

                    // Check for the same wellness for two different slots on the same day for the same patient
                    $checkSameWellness = Trn_Consultation_Booking::where('patient_id', Auth::id())
                        ->where('booking_date', $newRecordData['booking_date'])
                        ->where('wellness_id', $newRecordData['wellness_id'])
                        ->where('time_slot_id', '!=', $request->slot_id)
                        ->where('id', '!=', $request->booking_id)
                        ->where('family_member_id', $newRecordData['family_member_id'])
                        ->first();

                    $accountHolder = Mst_Patient::where('mst_patients.id', $patient_id)->first();

                    if ($checkSameSlot) {
                        $data['status'] = 0;
                        $data['message'] = $accountHolder->patient_name . ", you already have a booking for this time slot.";
                        return response($data);
                    }

                    if ($checkSameWellness) {
                        $data['status'] = 0;
                        $data['message'] = $accountHolder->patient_name . ", you already have a booking for this wellness.";
                        return response($data);
                    }

                    // Update existing data
                    $bookingDetails = Trn_Consultation_Booking::where('id', $request->booking_id)->first();
                    if ($bookingDetails->booking_status_id == 89 || ($bookingDetails->booking_status_id == 90 && $bookingDetails->booking_date < Carbon::now())) {
                        $createdRecord = Trn_Consultation_Booking::create($newRecordData);
                        $lastInsertedId = $createdRecord->id;
                        $leadingZeros = str_pad('', 3 - strlen($lastInsertedId), '0', STR_PAD_LEFT);
                        $bookingRefNo = 'BRN' . $leadingZeros . $lastInsertedId;
                        $updateConsultation = Trn_Consultation_Booking::where('id', $lastInsertedId)->update([
                            'updated_at' => Carbon::now(),
                            'booking_reference_number' => $bookingRefNo
                        ]);
                        $patientDevice = Trn_Patient_Device_Tocken::where('patient_id', $patient_id)->get();
                        $booking_date = PatientHelper::dateFormatUser($request->booking_date);

                        if ($patientDevice) {
                            $title = 'Wellness Booking';
                            $body = ' Here is a wellness booking for ' . $wellness->wellness_name . ' on ' . $booking_date . '.';
                            $clickAction = "PatientWellnessBooking";
                            $type = "Wellness Booking";

                            // Save notification to the patient's notification table
                            $notificationCreate = Trn_Notification::create([
                                'patient_id' => Auth::id(),
                                'title' => $title,
                                'content' => $body,
                                'read_status' => 0,
                                'created_at' => Carbon::now(),
                                'updated_at' => Carbon::now(),
                            ]);
                            foreach ($patientDevice as $pdt) {
                                // Send notification to the patient's device
                                $response =  DeviceTockenHelper::patientNotification($pdt->patient_device_token, $title, $body, $clickAction, $type);
                            }
                        }
                    } else {
                        $updateRecord = Trn_Consultation_Booking::where('id', $request->booking_id)->update($newRecordData);
                        $bookingRefNo = $bookingDetails->booking_reference_number;
                        $lastInsertedId = intval($request->booking_id);
                        $patientDevice = Trn_Patient_Device_Tocken::where('patient_id', $patient_id)->get();
                        $booking_date = PatientHelper::dateFormatUser($request->booking_date);

                        if ($patientDevice) {
                            $title = 'Wellness Booking Rescheduled';
                            $body = ' Rescheduled the booking for ' . $wellness->wellness_name . ' on ' . $booking_date . '.';
                            $clickAction = "PatientBookingReschedule";
                            $type = "Reschedule";

                            // Save notification to the patient's notification table
                            $notificationCreate = Trn_Notification::create([
                                'patient_id' => Auth::id(),
                                'title' => $title,
                                'content' => $body,
                                'read_status' => 0,
                                'created_at' => Carbon::now(),
                                'updated_at' => Carbon::now(),
                            ]);
                            foreach ($patientDevice as $pdt) {
                                // Send notification to the patient's device
                                $response =  DeviceTockenHelper::patientNotification($pdt->patient_device_token, $title, $body, $clickAction, $type);
                            }
                        }
                    }

                    // Fetch details of the selected time slot for the booking
                    $slotDetails = Mst_TimeSlot::where('id', $request->slot_id)->first();

                    // Convert time format from database format to user-readable format
                    $time_from = date('h:i A', strtotime($slotDetails->time_from));
                    $time_to = date('h:i A', strtotime($slotDetails->time_to));

                    $booking_details = [];
                    $booking_date = PatientHelper::dateFormatUser($request->booking_date);
                    $booking_details[] = [
                        'booking_id' => $lastInsertedId,
                        'member_name' => $accountHolder->patient_name,
                        'booking_referance_number' => $bookingRefNo,
                        'booking_to' => $wellness->wellness_name,
                        'booking_for' => $booked_for,
                        'booking_date' => $booking_date,
                        'time_slot' => $time_from . ' - ' . $time_to,
                    ];

                    $data['status'] = 1;
                    $data['message'] = $accountHolder->patient_name . ", your booking has been confirmed.";
                    $data['booking_details'] = $booking_details;
                    return response($data);
                } else {
                    $data['status'] = 0;
                    $data['message'] = "Please fill mandatory fields";
                    return response()->json($data);
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
