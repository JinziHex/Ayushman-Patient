<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Mst_Branch;
use App\Models\Mst_Staff_Timeslot;
use App\Models\Mst_TimeSlot;
use App\Models\Trn_Staff_Leave;
use App\Models\Mst_Patient;
use App\Models\Trn_Consultation_Booking;
use App\Models\Mst_Master_Value;
use App\Models\Trn_Patient_Family_Member;
use App\Models\Mst_Staff;
use App\Models\Trn_Patient_Device_Tocken;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Helpers\PatientHelper;
use App\Helpers\DeviceTockenHelper;
use DB;
use App\Models\Trn_Notification;
use Illuminate\Support\Facades\Auth;

class DoctorBookingController extends Controller
{
    //Fetch marital status from master and master value table
    public function maritalStatus()
    {
        $data = [];

        try {

            $maritalstatus = Mst_Master_Value::where('master_id', 27)->get(['id', 'master_value as name'])->toArray();

            if ($maritalstatus) {
                $data['status'] = 1;
                $data['message'] = "Data fetched.";
                $data['data'] = $maritalstatus;
            } else {
                $data['status'] = 0;
                $data['message'] = "Marital Status not detected.";
            }

            return response($data);
        } catch (\Exception $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        } catch (\Throwable $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        }
    }

    // to get all active qualifications , value fetching from mst_master_values table.
    public function getQualifications()
    {
        $data = [];

        try {
            $qualifications = Mst_Master_Value::where('master_id', 6)->get(['id', 'master_value as name'])->toArray();

            if ($qualifications) {
                $data['status'] = 1;
                $data['message'] = "Data fetched.";
                $data['data'] = $qualifications;
            } else {
                $data['status'] = 0;
                $data['message'] = "Qualifications not detected.";
            }

            return response($data);
        } catch (\Exception $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        } catch (\Throwable $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        }
    }

    // to get all active booking status , value fetching from mst_master_values table.
    public function bookingStatus(Request $request)
    {
        $data = [];
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'booking_type'    => ['required'],
                ],
                [
                    'booking_type.required'       => 'Booking type required',
                ]
            );

            if (!$validator->fails()) {
                if ($request->booking_type == 0) {
                    $booking_status = Mst_Master_Value::whereIn('id', [87, 88])->get(['id', 'master_value as name'])->toArray();
                } elseif ($request->booking_type == 1) {
                    $booking_status = Mst_Master_Value::whereIn('id', [89, 90])->get(['id', 'master_value as name'])->toArray();
                } else {
                    $data['status'] = 0;
                    $data['message'] = "Please provide valid booking type";
                    return response($data);
                }
                if ($booking_status) {
                    $data['status'] = 1;
                    $data['message'] = "Data fetched.";
                    $data['data'] = $booking_status;
                } else {
                    $data['status'] = 0;
                    $data['message'] = "Status not detected.";
                }

                return response($data);
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

    public function getBookingType()
    {
        $data = [];

        try {
            $qualifications = Mst_Master_Value::where('master_id', 21)->get(['id', 'master_value as name'])->toArray();

            if ($qualifications) {
                $data['status'] = 1;
                $data['message'] = "Data fetched.";
                $data['data'] = $qualifications;
            } else {
                $data['status'] = 0;
                $data['message'] = "Data not detected.";
            }

            return response($data);
        } catch (\Exception $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        } catch (\Throwable $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        }
    }

    // get all genders, value fetching from mst_master_values table.
    public function getGender()
    {
        $data = [];

        try {

            $genders = Mst_Master_Value::where('master_id', 17)->get(['id', 'master_value as name'])->toArray();

            if ($genders) {
                $data['status'] = 1;
                $data['message'] = "Data fetched.";
                $data['data'] = $genders;
            } else {
                $data['status'] = 0;
                $data['message'] = "Gender not detected.";
            }

            return response($data);
        } catch (\Exception $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        } catch (\Throwable $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        }
    }

    // get all relationships , value fetching from mst_master_values table.
    public function getRelationship()
    {
        $data = [];

        try {

            $relationships = Mst_Master_Value::where('master_id', 18)->get(['id', 'master_value as name'])->toArray();

            if ($relationships) {
                $data['status'] = 1;
                $data['message'] = "Data fetched.";
                $data['data'] = $relationships;
            } else {
                $data['status'] = 0;
                $data['message'] = "Relationships not detected.";
            }

            return response($data);
        } catch (\Exception $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        } catch (\Throwable $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        }
    }

    // get all blood groups , value fetching from mst_master_values table.
    public function getBloodGroup()
    {
        $data = [];

        try {

            $bloodGroups = Mst_Master_Value::where('master_id', 19)->get(['id', 'master_value as name'])->toArray();

            if ($bloodGroups) {
                $data['status'] = 1;
                $data['message'] = "Data fetched.";
                $data['data'] = $bloodGroups;
            } else {
                $data['status'] = 0;
                $data['message'] = "Blood group not detected.";
            }

            return response($data);
        } catch (\Exception $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        } catch (\Throwable $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        }
    }

    // get all branches , value fetching from mst_branches table.

    public function getBranches()
    {
        $data = array();
        try {
            $branches = Mst_Branch::where('is_active', 1)->get(['branch_id', 'branch_name'])->toArray();
            if ($branches) {
                $data['status'] = 1;
                $data['message'] = "Data fetched.";
                $data['data'] = $branches;
            } else {
                $data['status'] = 0;
                $data['message'] = "No branches found.";
            }
            return response($data);
        } catch (\Exception $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        } catch (\Throwable $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        }
    }


   
     public function doctorsList(Request $request)
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
                $bookingDate = $request->input('booking_date');

                if (isset($request->branch_id) && !empty($request->booking_date)) {

                    $bookingDate = Carbon::parse($request->booking_date);
                    $currentDate = Carbon::now();
                    $currentYear = Carbon::now()->year;

                    // Allow up to 1 years in the future
                    if ($bookingDate->year > $currentYear + 1) {
                        $data['status'] = 0;
                        $data['message'] = "Booking date cannot be more than 1 year in the future.";
                        return response($data);
                    }
                    $doctorONLeave=[];

                    // Check if the booking date is in the past and not the same day as the current date
                    if (!$bookingDate->isSameDay($currentDate) && $bookingDate->isPast()) {
                        $data['status'] = 0;
                        $data['message'] = "Booking date is older than the current date.";
                        return response($data);
                    } else {
                        // getting the doctors id  as array, they are leave on the day 
                        //return 'test';
                        $doctorONLeave = Trn_Staff_Leave::where('from_date', '<=', $request->booking_date)
                                        ->where('to_date', '>=', $request->booking_date)
                                        ->pluck('staff_id')
                                        ->toArray();
                       
                       // $day_of_week = PatientHelper::getWeekDay($request->booking_date);
                        $weekday = Carbon::parse($bookingDate)->format('l');
                        //$weekDayId = Mst_Master_Value::where('master_value', 'LIKE', '%' . $day_of_week . '%')->pluck('id')->first();
                        //$allotted_doctors = Mst_Staff_Timeslot::where('week_day', $weekDayId)->where('is_active', 1)->distinct()->pluck('staff_id')->toArray();
                        $allotted_doctors=Mst_Staff_Timeslot::where('is_active', 1)
                                        ->whereHas('weekDay', function ($query) use ($weekday) {
                                            $query->where('master_id', 3)
                                                ->where('master_value', $weekday);
                                        })
                                        ->with(['timeSlot', 'bookings'])
                                        ->where(function ($query) use ($bookingDate) {
                                            $query->whereDoesntHave('bookings', function ($query) use ($bookingDate) {
                                                $query->where('booking_date', $bookingDate);
                                            })->orWhereHas('bookings', function ($query) use ($bookingDate) {
                                                $query->where('booking_date', $bookingDate)->havingRaw('MAX(token_number) < no_tokens');
                                            });
                                        })->distinct()->pluck('staff_id')->toArray();
                        //return $allotted_doctors;

                        // getting the available doctors id as array 
                        $doctorONLeaveCollection = collect($doctorONLeave);
                        $allottedDoctorsCollection = collect($allotted_doctors);
                        $filteredDoctors = $allottedDoctorsCollection->diff($doctorONLeaveCollection);
                        $filteredDoctorsArray = $filteredDoctors->values()->all();
                        //return  $filteredDoctorsArray;

                        $queries = Mst_Staff::leftjoin('mst_branches', 'mst_staffs.branch_id', '=', 'mst_branches.branch_id')
                            ->leftjoin('mst_master_values', 'mst_staffs.staff_qualification', '=', 'mst_master_values.id')
                            ->select('mst_staffs.staff_id', 'mst_staffs.staff_name as name','mst_staffs.branch_id','mst_branches.branch_name as branch_name', 'mst_master_values.master_value as qualification', 'mst_staffs.staff_image as profile_image','mst_staffs.staff_qualification as designation')
                            ->where('mst_staffs.staff_type', 20)
                            ->where('mst_staffs.is_active', 1)
                           ->where('mst_staffs.branch_id', $request->branch_id)
                            ->whereIn('mst_staffs.staff_id',$filteredDoctorsArray);

                        // Apply filtration based on user search criteria

                        // Check if a doctor name is provided for searching
                        if (isset($request->search_doctor_name)) {
                            $queries = $queries->where('mst_staffs.staff_name', 'like', '%' . $request->search_doctor_name . '%');
                        }
                        // Check if a branch name is provided for searching
                        if (isset($request->search_branch_name)) {
                            $queries = $queries->where('mst_branches.branch_name', 'like', '%' . $request->search_branch_name . '%');
                        }
                         if (isset($request->search_designation)) {
                            $queries = $queries->where('mst_staffs.staff_qualification', 'like', '%' .$request->search_designation . '%');
                        }
                        // Check if a specific qualification ID is provided for searching
                        if (isset($request->search_qualification_id) && $request->search_qualification_id != 0) {
                            $queries = $queries->where('mst_master_values.id', $request->search_qualification_id);
                        }

                        // Get the results with pagination
                        $limit = $request->input('limit',10); // Default limit is 5
                        $page_number = $request->input('page_number', 1); // Default page number is 1

                        $doctorsList = $queries->paginate($limit, ['*'], 'page_number', $page_number);

                        // Format the response
                        $formattedDoctorsList = $doctorsList->items();
                        //return $formattedDoctorsList;
                        foreach ($formattedDoctorsList as &$doctor) {
                            $doctor['profile_image'] = config('admin_assets.doctor-image-path') . $doctor['profile_image'];
                        }

                        $data['status'] = 1;
                        $data['message'] = "Data fetched";
                        $data['data'] =  $formattedDoctorsList;
                        $data['pagination_details'] = [
                            'current_page' => $doctorsList->currentPage(),
                            'total_records' => $doctorsList->total(),
                            'total_pages' => $doctorsList->lastPage(),
                            'per_page' => $doctorsList->perPage(),
                            // 'first_page_url' => $doctorsList->currentPage() > 1 ? $this->getPageNumberFromUrl($doctorsList->url(1)) : null,
                            // 'last_page_url' => $this->getPageNumberFromUrl($doctorsList->url($doctorsList->lastPage())),
                            // 'next_page_url' => $this->getPageNumberFromUrl($doctorsList->nextPageUrl()),
                            // 'prev_page_url' => $this->getPageNumberFromUrl($doctorsList->previousPageUrl()),
                        ];
                        return response($data);
                    }
                } else {
                    $data['status'] = 0;
                    $data['message'] = "Please select branch and date";
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

    private function getPageNumberFromUrl($url)
    {
        $query = parse_url($url, PHP_URL_QUERY);
        parse_str($query, $params);
        return isset($params['page_number']) ? $params['page_number'] : null;
    }


    public function doctorsDetails(Request $request)
    {
        $data = array();
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'doctor_id' => ['required'],
                ],
                [
                    'doctor_id.required' => 'Doctor required',
                ]
            );
            if (!$validator->fails()) {
                if (isset($request->doctor_id)) {
                    // Retrieve doctor details by joining staff, branch, qualification, and specialization tables
                    $getDoctorDetails = Mst_Staff::leftjoin('mst_branches', 'mst_staffs.branch_id', '=', 'mst_branches.branch_id')
                        //->join('mst_master_values AS qualification', 'mst_staffs.staff_qualification', '=', 'qualification.id')
                        //->join('mst_master_values AS specialization', 'mst_staffs.staff_specialization', '=', 'specialization.id')
                        ->select(
                            'mst_staffs.staff_id as doctor_id',
                            'mst_staffs.staff_name as name',
                            'mst_staffs.staff_booking_fee',
                            'mst_staffs.staff_qualification',
                             'mst_staffs.staff_specialization',
                            'mst_branches.branch_name as branch_name',
                            //'qualification.master_value as qualification',
                            //'specialization.master_value as specialization',
                            'mst_staffs.staff_image as profile_image',
                            'mst_staffs.staff_address as address'
                        )
                        ->where('mst_staffs.staff_type', 20)
                        ->where('mst_staffs.staff_id', $request->doctor_id)
                        ->first();

                    $doctorDetails = [];

                    if ($getDoctorDetails) {
                        $fee = PatientHelper::amountDecimal($getDoctorDetails->staff_booking_fee);
                        $doctorDetails = (object)[
                            'doctor_id' => $getDoctorDetails->doctor_id,
                            'name' => $getDoctorDetails->name,
                            'booking_fee' => $fee,
                            'branch_name' => $getDoctorDetails->branch_name,
                            'qualification' => $getDoctorDetails->staff_qualification,
                            'specialization' => $getDoctorDetails->staff_specialization,
                            'address' => $getDoctorDetails->address,
                            'profile_image' => config('admin_assets.doctor-image-path') . $getDoctorDetails->profile_image,
                            'description' => "",
                        ];

                        $data['status'] = 1;
                        $data['message'] = "Data fetched";
                        $data['data'] = $doctorDetails;
                    } else {
                        $data['status'] = 0;
                        $data['message'] = "No doctor found";
                    }

                    return response($data);
                } else {
                    $data['status'] = 0;
                    $data['message'] = "Please select a doctor";
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

    public function doctorsAvailability(Request $request)
    {
        $data = array();
        $time_slots = array();
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'doctor_id' => ['required'],
                    'branch_id' => ['required'],
                    'booking_date' => ['required'],

                ],
                [
                    'doctor_id.required' => 'Doctor required',
                    'branch_id.required' => 'Branch required',
                    'booking_date.required' => 'Booking date required',

                ]
            );
            if (!$validator->fails()) {
                if (isset($request->branch_id) && isset($request->doctor_id) && isset($request->booking_date)) {
                    $bookingDate = Carbon::parse($request->booking_date);
                    $currentDate = Carbon::now();
                    $currentYear = Carbon::now()->year;

                    // Allow up to 1 years in the future
                    if ($bookingDate->year > $currentYear + 1) {
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
                        $doctor_details = Mst_Staff::where('mst_staffs.staff_id', $request->doctor_id)->first();
                        $doctor_name = $doctor_details->staff_name;

                        $day_of_week = PatientHelper::getWeekDay($request->booking_date);
                        $weekDayId = Mst_Master_Value::where('master_value', 'LIKE', '%' . $day_of_week . '%')->pluck('id')->first();

                        $booking_date = PatientHelper::dateFormatDb($request->booking_date);

                        // Check if the doctor is on leave on the specified booking date and retrieve the leave record
                        // $doctorOnLeave = Trn_Staff_Leave::where('leave_date', $booking_date)
                        //     ->where('user_id', $request->doctor_id)
                        //     ->whereIn('leave_duration', [81, 82])
                        //     ->first();
                            
                        $doctorONLeave = Trn_Staff_Leave::where('from_date', '<=', $request->booking_date)
                                        ->where('to_date', '>=', $request->booking_date)
                                        ->first();
                                        
                        //Updated query
                         $weekday = Carbon::parse($bookingDate)->format('l');
                        //$weekDayId = Mst_Master_Value::where('master_value', 'LIKE', '%' . $day_of_week . '%')->pluck('id')->first();
                        //$allotted_doctors = Mst_Staff_Timeslot::where('week_day', $weekDayId)->where('is_active', 1)->distinct()->pluck('staff_id')->toArray();
                        $timeSlotsQuery=Mst_Staff_Timeslot::join('mst_timeslots', 'mst__staff__timeslots.timeslot', 'mst_timeslots.id')->where('mst__staff__timeslots.staff_id', $request->doctor_id) ->where('mst__staff__timeslots.is_active', 1)
                                        ->whereHas('weekDay', function ($query) use ($weekday) {
                                            $query->where('master_id', 3)
                                                ->where('master_value', $weekday);
                                        })
                                        ->with(['timeSlot', 'bookings'])
                                        ->where(function ($query) use ($bookingDate) {
                                            $query->whereDoesntHave('bookings', function ($query) use ($bookingDate) {
                                                $query->where('booking_date', $bookingDate);
                                            })->orWhereHas('bookings', function ($query) use ($bookingDate) {
                                                $query->where('booking_date', $bookingDate)->havingRaw('MAX(token_number) < no_tokens');
                                            });
                                        });
                        
                        
                        ////////

                        // Build a query to retrieve staff time slots for the specified doctor on the given week day
                        // $timeSlotsQuery = Mst_Staff_Timeslot::join('mst_timeslots', 'mst__staff__timeslots.timeslot', 'mst_timeslots.id')->where('mst__staff__timeslots.staff_id', $request->doctor_id)
                        //     ->where('mst__staff__timeslots.week_day', $weekDayId)
                        //     ->where('mst__staff__timeslots.is_active', 1);

                        // Adjust the time slot query based on the doctor's leave status full day/first half/second half.
                        if ($doctorONLeave!=NULL) {
                            $timeToCondition = $doctorONLeave->start_day =='First Half' ? '>=' : '<=';
                            $timeSlotsQuery->where('mst_timeslots.time_from', $timeToCondition, "12:00:00");
                        }

                        $timeSlots = $timeSlotsQuery->get();

                        if ($timeSlots) {

                            $currentDate = Carbon::now()->format('Y-m-d');
                            $currentTime = Carbon::now()->format('H:i:s');
                            //return $currentTime; 
                            // dd($currentTime);
                            $time_slots = [];
                            foreach ($timeSlots as $timeSlot) {
                                $booked_tokens = Trn_Consultation_Booking::where('booking_date', $booking_date)
                                    ->where('staff_timeslot_id', $timeSlot->id)
                                    ->where('doctor_id', $request->doctor_id)
                                    ->whereIn('booking_status_id', [87, 88])
                                    ->count();

                                    $available_slots = $timeSlot->no_tokens - $booked_tokens;
                                    $available_slots = ($available_slots <= 0) ? 0 : $available_slots;

                                if ($timeSlot->time_from <= $currentTime && $booking_date == $currentDate) {
                                    //return 1;
                                    $time_slots[] = [
                                        'time_slot_id' => $timeSlot->id,
                                        'time_from' => Carbon::parse($timeSlot->time_from)->format('h:i A'),
                                        'time_to' => Carbon::parse($timeSlot->time_to)->format('h:i A'),
                                        'available_slots' => $available_slots,
                                        'is_available' => 0,
                                    ];
                                } else {
                                    $time_slots[] = [
                                        'time_slot_id' => $timeSlot->id,
                                        'time_from' => Carbon::parse($timeSlot->time_from)->format('h:i A'),
                                        'time_to' => Carbon::parse($timeSlot->time_to)->format('h:i A'),
                                        'available_slots' => $available_slots,
                                        'is_available' => 1,
                                    ];
                                }
                            }

                            $data['status'] = 1;
                            $data['message'] = "Data fetched.";
                            $data['doctor_name'] = $doctor_name;
                            $data['data'] = $time_slots;
                            // $data['booking_id'] = $booking_id ?? '';
                            return response($data);
                        } else {
                            $data['status'] = 0;
                            $data['message'] = "No slots available on this date.";
                            $data['data'] = $time_slots;
                            return response($data);
                        }
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
            $response = ['status' => 0, 'message' => $e->getMessage()];
            return response($response);
        } catch (\Throwable $e) {
            $response = ['status' => 0, 'message' => $e->getMessage()];
            return response($response);
        }
    }

    public function bookingDetails(Request $request)
    {
        // Currently not in use 
        $data = array();
        try {
            // Validating request parameters
            $validator = Validator::make(
                $request->all(),
                [
                    'doctor_id' => ['required'],
                    'branch_id' => ['required'],
                    'slot_id' => ['required'],
                    'booking_date' => ['required'],
                    'limit' => ['integer'],
                    'page_number' => ['integer'],
                ],
                [
                    'doctor_id.required' => 'Doctor required',
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

            if (isset($request->doctor_id) && isset($request->branch_id) && isset($request->booking_date) && isset($request->slot_id)) {
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

                    // Recheck the availability of the specified time slot for booking on the given date and for the particular doctor
                    $available_slots = PatientHelper::recheckAvailability($request->booking_date, $weekDayId, $request->doctor_id, $request->slot_id);

                    if ($available_slots > 0) {
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
                            'first_page_url' => $page_number > 1 ? url(request()->path() . '?page_number=1&limit=' . $limit) : null,
                            'last_page_url' => $page_number < ceil(count($family_details) / $limit) ? url(request()->path() . '?page_number=' . ceil(count($family_details) / $limit) . '&limit=' . $limit) : null,
                            'next_page_url' => $page_number < ceil(count($family_details) / $limit) ? url(request()->path() . '?page_number=' . ($page_number + 1) . '&limit=' . $limit) : null,
                            'prev_page_url' => $page_number > 1 ? url(request()->path() . '?page_number=' . ($page_number - 1) . '&limit=' . $limit) : null,
                        ];

                        return response($data);
                    } else {
                        $data['status'] = 0;
                        $data['message'] = "No slots available";
                        return response($data);
                    }
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


    public function bookingSummary(Request $request)
    {
        $data = array();
        $doctorDetails = array();
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'doctor_id' => ['required'],
                    'family_member_id' => ['required'],
                    'slot_id' => ['required'],
                    'booking_date' => ['required'],
                    'yourself' => ['required'],
                    // 'reschedule_key' => ['required'],
                ],
                [
                    'doctor_id.required' => 'Doctor required',
                    'family_member_id.required' => 'Family member id required',
                    'slot_id.required' => 'Slot required',
                    'booking_date.required' => 'Booking date required',
                    'yourself.required' => 'Yourself is required',
                    // 'reschedule_key.required' => 'Reschedule key required',
                ]
            );
            if (!$validator->fails()) {
                if (isset($request->family_member_id) && isset($request->yourself) && isset($request->slot_id) && isset($request->doctor_id) && isset($request->booking_date)) {
                    $bookingDate = Carbon::parse($request->booking_date);
                    $currentDate = Carbon::now();
                    $currentYear = Carbon::now()->year;

                    if ($bookingDate->year > $currentYear + 1) { // Allow up to 1 years in the future
                        $data['status'] = 0;
                        $data['message'] = "Booking date cannot be more than 1 year in the future.";
                        return response($data);
                    }
                    // Check if the booking date is in the past and not the same day as the current date
                    if (!$bookingDate->isSameDay($currentDate) && $bookingDate->isPast()) {
                        $data['status'] = 0;
                        $data['message'] = "Booking date is older than the current date.";
                        return response($data);
                    }

                    $patient_id = Auth::id();
                    $accountHolder = Mst_Patient::where('id', $patient_id)->first();
                    if (!$accountHolder) {
                        $data['status'] = 0;
                        $data['message'] = "User does not exist";
                        return response($data);
                    }

                    // Checking already booked or not 
                    $booking_date_db_format = PatientHelper::dateFormatDb($request->booking_date);
                    $checkAlreadyBooked =  Trn_Consultation_Booking::where('patient_id', $patient_id)->where('booking_date', $booking_date_db_format)->where('time_slot_id', $request->slot_id)->where('family_member_id', $request->family_member_id)->first();

                    if ($checkAlreadyBooked) {
                        $data['status'] = 0;
                        $data['message'] = $accountHolder->patient_name . ", you've already booked this slot";
                        return response($data);
                    }

                    $slotDetails = Mst_TimeSlot::where('id', $request->slot_id)
                        ->first();

                    $time_from = date('h:i A', strtotime($slotDetails->time_from));
                    $time_to = date('h:i A', strtotime($slotDetails->time_to));
                    

                    $doctor = Mst_Staff::join('mst_branches', 'mst_staffs.branch_id', '=', 'mst_branches.branch_id')
                        //->join('mst_master_values AS qualification', 'mst_staffs.staff_qualification', '=', 'qualification.id')
                       // ->join('mst_master_values AS specialization', 'mst_staffs.staff_specialization', '=', 'specialization.id')
                        ->select(
                            'mst_staffs.staff_id as doctor_id',
                            'mst_staffs.staff_name as name',
                            'mst_staffs.staff_booking_fee',
                            'mst_branches.branch_name as branch_name',
                            'mst_staffs.staff_qualification',
                            'mst_staffs.staff_specialization',
                            // 'qualification.master_value as qualification',
                            // 'specialization.master_value as specialization',
                            'mst_staffs.staff_image as profile_image'
                        )
                        ->where('mst_staffs.staff_type', 20)
                        ->where('mst_staffs.staff_id', $request->doctor_id)
                        ->first();
                     $doctorDetails=[];
                    $doctorDetails = [
                        'doctor_id' => $doctor->doctor_id,
                        'doctor_name' => $doctor->name,
                        'qualification' => $doctor->staff_qualification,
                        'specialization' => $doctor->staff_specialization,
                        'doctor_branch' => $doctor->branch_name,
                        'doctor_profile_image' => 'https://staging-ayushman-patient.hexprojects.in/assets/uploads/doctor_profile/' . $doctor->profile_image,
                    ];

                    $patientDetails = [];

                    if ($request->yourself == 1) {

                        $gender_name = Mst_Master_Value::where('id', $accountHolder->patient_gender)->value('master_value');
                        $patientDetails = [
                            'member_id' => $patient_id,
                            'family_member_id' => 0,
                            'yourself' => 1,
                            'booking_date' => Carbon::parse($request->booking_date)->format('d-m-Y'),
                            'slot' => $time_from . ' - ' . $time_to,
                            'slot_id' => $slotDetails->id,
                            'patient_name' => $accountHolder->patient_name,
                            'dob' => Carbon::parse($accountHolder->patient_dob)->format('d-m-Y'),
                            'gender' => $gender_name,
                            'mobile_number' => $accountHolder->patient_mobile,
                            'email_address' => $accountHolder->patient_email,
                        ];
                    } else {
                        $member = Trn_Patient_Family_Member::join('mst_patients', 'trn_patient_family_member.patient_id', 'mst_patients.id')
                            ->join('mst_master_values as gender', 'trn_patient_family_member.gender_id', 'gender.id')
                            ->join('mst_master_values as relationship', 'trn_patient_family_member.relationship_id', 'relationship.id')
                            ->select('trn_patient_family_member.id', 'trn_patient_family_member.mobile_number', 'trn_patient_family_member.email_address', 'trn_patient_family_member.family_member_name', 'gender.master_value as gender_name', 'trn_patient_family_member.date_of_birth', 'relationship.master_value as relationship')
                            ->where('trn_patient_family_member.patient_id', $patient_id)
                            ->where('trn_patient_family_member.id', $request->family_member_id)
                            ->where('trn_patient_family_member.is_active', 1)
                            ->first();

                        $patientDetails = [
                            'member_id' => $patient_id,
                            'family_member_id' => $member->id,
                            'yourself' => 0,
                            'booking_date' => Carbon::parse($request->booking_date)->format('d-m-Y'),
                            'slot' => $time_from . ' - ' . $time_to,
                             'slot_id' => $slotDetails->id,
                            'patient_name' => $member->family_member_name,
                            'dob' => Carbon::parse($member->date_of_birth)->format('d-m-Y'),
                            'gender' => $member->gender_name,
                            'mobile_number' => $member->mobile_number,
                            'email_address' => $member->email_address,
                        ];
                    }
                    $fee = PatientHelper::amountDecimal($doctor->staff_booking_fee);
                    $paymentDetails=[];
                    $paymentDetails = [
                        'consultation_fee' => $fee,
                        'total_amount' => $fee,
                    ];

                    $day_of_week = PatientHelper::getWeekDay($request->booking_date);
                    $weekDayId = Mst_Master_Value::where('master_value', 'LIKE', '%' . $day_of_week . '%')->pluck('id')->first();


                    $available_slots = PatientHelper::recheckAvailability($request->booking_date, $weekDayId, $request->doctor_id, $request->slot_id);

                    if ($available_slots >= 1) {
                        $data['status'] = 1;
                        $data['message'] = "Data Fetched";
                        $data['doctor_details'] = $doctorDetails;
                        $data['patient_details'] = $patientDetails;
                        $data['payment_details'] = $paymentDetails;
                        // $data['booking_id'] = $booking_id ?? '';
                        return response($data);
                    } else {
                        $data['status'] = 0;
                        $data['message'] = "Sorry, no slots available";
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

    public function bookingConfirmation(Request $request)
    {
        $data = array();
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'doctor_id' => ['required'],
                    'family_member_id' => ['required'],
                    'slot_id' => ['required'],
                    'booking_date' => ['required'],
                    'yourself' => ['required'],
                    'reschedule_key' => ['required'],
                ],
                [
                    'doctor_id.required' => 'Doctor required',
                    'family_member_id.required' => 'Member id required',
                    'slot_id.required' => 'Slot required',
                    'booking_date.required' => 'Booking date required',
                    'yourself.required' => 'Yourself is required',
                    'reschedule_key.required' => 'Reschedule key required',
                ]
            );
            if (!$validator->fails()) {
                if (isset($request->yourself) && isset($request->slot_id) && isset($request->doctor_id) && isset($request->booking_date) && isset($request->reschedule_key)) {
                    $bookingDate = Carbon::parse($request->booking_date);
                    $currentDate = Carbon::now();
                    $currentYear = Carbon::now()->year;

                    if ($bookingDate->year > $currentYear + 1) { // Allow up to 1 years in the future
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
                        if ($request->reschedule_key == 1) {
                            if (!$request->has('booking_id')) {
                                $data['status'] = 0;
                                $data['message'] = "Booking id is required";
                                return response($data);
                            } else {
                                $booking_id = $request->booking_id;
                            }
                        }

                        $accountHolder = Mst_Patient::where('id', $patient_id)->first();
                        if (!$accountHolder) {
                            $data['status'] = 0;
                            $data['message'] = "User does not exist";
                            return response($data);
                        }

                        // Fetch details of the selected time slot for the booking
                        $slotDetails = Mst_TimeSlot::where('id', $request->slot_id)->first();

                        // Convert time format from database format to user-readable format
                        $time_from = date('h:i A', strtotime($slotDetails->time_from));
                        $time_to = date('h:i A', strtotime($slotDetails->time_to));

                        // Fetch details of the selected doctor for the booking
                        $doctor = Mst_Staff::select('mst_staffs.staff_id as doctor_id', 'mst_staffs.staff_name as doctor_name', 'mst_staffs.staff_booking_fee', 'mst_staffs.branch_id')
                            ->where('mst_staffs.staff_type', 20)
                            ->where('mst_staffs.staff_id', $request->doctor_id)
                            ->first();

                        // Extract data from the request
                        $yourself = $request->yourself;
                        $booking_date = PatientHelper::dateFormatDb($request->booking_date);

                        // Prepare data for creating a new booking record
                        $booked_for = $accountHolder->patient_name;
                        $newRecordData = [
                            'booking_type_id' => 84,
                            'patient_id' => $patient_id,
                            'doctor_id' => $request->doctor_id,
                            'branch_id' => $doctor->branch_id,
                            'booking_date' => $booking_date,
                            'time_slot_id' => $request->slot_id,
                            'booking_status_id' => 88,
                            'booking_fee' => $doctor->consultation_fee,
                            'is_for_family_member' => 0,
                            'family_member_id' => 0,
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
                                $data['message'] = "Member is required";
                                return response($data);
                            }
                        }

                        // checking already booked or not 
                        // $checkAlreadyBooked =  Trn_Consultation_Booking::where('patient_id', Auth::id())->where('booking_date', $newRecordData['booking_date'])->where('time_slot_id', $newRecordData['time_slot_id'])->where('family_member_id', $newRecordData['family_member_id'])->where('doctor_id', $newRecordData['doctor_id'])->first();
                        $checkAlreadyBooked =  Trn_Consultation_Booking::where('patient_id', Auth::id())->where('booking_date', $newRecordData['booking_date'])->where('trn_consultation_bookings.booking_status_id', '!=', 90)->where('time_slot_id', $newRecordData['time_slot_id'])->where('family_member_id', $newRecordData['family_member_id'])->first();

                        if ($checkAlreadyBooked) {
                            $data['status'] = 0;
                            $data['message'] = $accountHolder->patient_name . ", you've already booked this slot";
                            return response($data);
                        }

                        $day_of_week = PatientHelper::getWeekDay($request->booking_date);
                        $weekDayId = Mst_Master_Value::where('master_value', 'LIKE', '%' . $day_of_week . '%')->pluck('id')->first();

                        $available_slots = PatientHelper::recheckAvailability($booking_date, $weekDayId, $request->doctor_id, $request->slot_id);
                        if ($available_slots >= 1) {
                            if (isset($booking_id)) {
                                // Update existing data
                                $bookingDetails = Trn_Consultation_Booking::where('id', $booking_id)->first();
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
                                    if ($patientDevice) {
                                        $title = 'Consultation Booking';
                                        $body = ' Here is a consultation booking for ' . $doctor->doctor_name . ' on ' . $request->booking_date . '.';
                                        $clickAction = "PatientConsultationBookingAgain";
                                        $type = "Book Again";

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
                                    $updateRecord = Trn_Consultation_Booking::where('id', $booking_id)->update($newRecordData);
                                    $bookingRefNo = $bookingDetails->booking_reference_number;
                                    $lastInsertedId = intval($booking_id);

                                    $patientDevice = Trn_Patient_Device_Tocken::where('patient_id', $patient_id)->get();

                                    if ($patientDevice) {
                                        $title = 'Booking Rescheduled';
                                        $body = ' Rescheduled the booking for ' . $doctor->doctor_name . ' on ' . $request->booking_date . '.';
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
                                // Create new data 
                                $createdRecord = Trn_Consultation_Booking::create($newRecordData);
                                $lastInsertedId = $createdRecord->id;
                                $leadingZeros = str_pad('', 3 - strlen($lastInsertedId), '0', STR_PAD_LEFT);
                                $bookingRefNo = 'BRN' . $leadingZeros . $lastInsertedId;
                                $updateConsultation = Trn_Consultation_Booking::where('id', $lastInsertedId)->update([
                                    'updated_at' => Carbon::now(),
                                    'booking_reference_number' => $bookingRefNo
                                ]);

                                $patientDevice = Trn_Patient_Device_Tocken::where('patient_id', $patient_id)->get();
                                if ($patientDevice) {
                                    $title = 'Consultation Booking';
                                    $body = ' Here is a consultation booking for ' . $doctor->doctor_name . ' on ' . $request->booking_date . '.';
                                    $clickAction = "PatientConsultationBooking";
                                    $type = "Consultation Booking";

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
                            $accountHolder = Mst_Patient::where('mst_patients.id', $patient_id)->first();
                            $booking_details = [];
                            $booking_date = PatientHelper::dateFormatUser($request->booking_date);
                            $booking_details = [
                                'booking_id' => $lastInsertedId,
                                'member_name' => $accountHolder->patient_name,
                                'booking_referance_number' => $bookingRefNo,
                                'booking_to' => $doctor->doctor_name,
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
                            $data['message'] = "Sorry, no slots available";
                            return response($data);
                        }
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
    // slot availability 
    public function slotAvailability(Request $request)
    {
        $data = array();
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'booking_date' => ['required'],
                    'slot_id' => ['required'],
                ],
                [
                    'booking_date.required' => 'Booking date is required',
                    'slot_id.required' => 'Slot is required',
                ]
            );
            if (!$validator->fails()) {
                if (isset($request->booking_date) && isset($request->slot_id)) {

                    $bookingDate = Carbon::parse($request->booking_date);
                    $currentDate = Carbon::now();
                    $currentYear = Carbon::now()->year;

                    if ($bookingDate->year > $currentYear + 1) { // Allow up to 1 years in the future
                        $data['status'] = 0;
                        $data['message'] = "Booking date cannot be more than 1 year in the future.";
                        return response($data);
                    }
                    // Check if the booking date is in the past and not the same day as the current date
                    if (!$bookingDate->isSameDay($currentDate) && $bookingDate->isPast()) {
                        $data['status'] = 0;
                        $data['message'] = "Booking date is older than the current date.";
                        return response($data);
                    }
                    $currentDate = Carbon::now()->format('Y-m-d');
                    $currentTime = Carbon::now()->format('H:i:s');
                    $is_available = 1;
                    $booking_date = PatientHelper::dateFormatDb($request->booking_date);

                    if ($booking_date == $currentDate) {
                        $slot_details = Mst_TimeSlot::where('id', $request->slot_id)->first();
                        if ($slot_details->time_from <= $currentTime) {
                            $is_available = 0;
                        }
                    }

                    $message = "Data has been verified";
                    if($is_available == 0){
                        $message = "Currently, this slot is not available";
                    }
                    $booking_date = PatientHelper::dateFormatUser($request->booking_date);
                    $data['status'] = 1;
                    $data['message'] = $message;
                    $data['booking_date'] = $booking_date;
                    $data['slot_id'] = $request->slot_id;
                    $data['is_available'] = $is_available;
                    return response($data);
                } else {
                    $data['status'] = 0;
                    $data['message'] = "Please provide mandatory fields";
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
