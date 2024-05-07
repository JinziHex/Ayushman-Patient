<?php

namespace App\Helpers;


use App\Models\Mst_Patient;
use App\Models\Mst_Staff_Timeslot;
use App\Models\Mst_Wellness_Therapyrooms;
use App\Models\Mst_Therapy_Room_Slot;
use App\Models\Mst_TimeSlot;
use App\Models\Trn_Consultation_Booking;
use App\Models\Trn_Patient_Family_Member;
use App\Models\Mst_Wellness;
use Carbon\Carbon;

class PatientHelper
{

    // get week day using date 
    public static function getWeekDay($date)
    {
        $carbon_date = Carbon::parse($date);
        $day_of_week = $carbon_date->format('l');
        return  $day_of_week;
    }

    // get date as how it saving in DB 
    public static function dateFormatDb($date)
    {
        $formattedDate = Carbon::parse($date)->format('Y-m-d');
        return $formattedDate;
    }

    // get date as how user seeing 
    public static function dateFormatUser($date)
    {
        $formattedDate = Carbon::parse($date)->format('d-m-Y');
        return $formattedDate;
    }

    // to get timeslots
    public static function getTimeSlot($interval, $start_time, $end_time)
    {
        // currently not in use 
        $start = new \DateTime($start_time);
        $end = new \DateTime($end_time);
        $startTime = $start->format('H:i');
        $endTime = $end->format('H:i');
        $i = 0;
        $time = [];
        while (strtotime($startTime) <= strtotime($endTime)) {
            $start = $startTime;
            $end = date('H:i', strtotime('+' . $interval . ' minutes', strtotime($startTime)));
            $startTime = date('H:i', strtotime('+' . $interval . ' minutes', strtotime($startTime)));
            $i++;
            if (strtotime($startTime) <= strtotime($endTime)) {
                $time[$i]['slot_start_time'] = $start;
                $time[$i]['slot_end_time'] = $end;
            }
        }
        return $time;
    }

    // rechecking whether the slot is available or not 

    public static function recheckAvailability($booking_date, $weekDayId, $doctor_id, $slot_id)
    {

        $booking_date = self::dateFormatDb($booking_date);
        $timeSlot = Mst_Staff_Timeslot::join('mst_timeslots', 'mst__staff__timeslots.timeslot', 'mst_timeslots.id')
            ->where('mst__staff__timeslots.staff_id', $doctor_id)
            ->where('mst__staff__timeslots.week_day', $weekDayId)
            ->where('mst__staff__timeslots.timeslot', $slot_id)
            ->where('mst__staff__timeslots.is_active', 1)->first();

        $booked_tokens = Trn_Consultation_Booking::where('booking_date', $booking_date)
            ->where('doctor_id', $doctor_id)
            ->where('time_slot_id', $slot_id)
            ->whereIn('booking_status_id', [87, 88, 89])->count();

        $available_slots = $timeSlot->no_tokens - $booked_tokens;
        $currentDate = Carbon::now()->format('Y-m-d');
        $currentTime = Carbon::now()->format('H:i:s');

        if ($available_slots <= 0 || ($timeSlot->time_from <= $currentTime && $booking_date == $currentDate)) {
            $available_slots = 0;
        } else {
            $available_slots = ($available_slots <= 0) ? 0 : $available_slots;
        }
        return $available_slots;
    }

    // get all family members array using patient_id 
    public static function getFamilyDetails($patient_id)
    {
        // Initialize an array to store family details
        $family_details = array();

        // Retrieve details of the account holder
        $accountHolder = Mst_Patient::join('mst_master_values', 'mst_patients.patient_gender', '=', 'mst_master_values.id')
            ->where('mst_patients.id', $patient_id)
            ->select('mst_patients.*', 'mst_master_values.master_value as gender_name')
            ->first();

        // Retrieve family members associated with the account holder
        $members = Trn_Patient_Family_Member::join('mst_patients', 'trn_patient_family_member.patient_id', 'mst_patients.id')
            ->join('mst_master_values as gender', 'trn_patient_family_member.gender_id', '=', 'gender.id')
            ->join('mst_master_values as relationship', 'trn_patient_family_member.relationship_id', '=', 'relationship.id')
            ->select('trn_patient_family_member.id', 'trn_patient_family_member.family_member_name', 'trn_patient_family_member.email_address', 'trn_patient_family_member.mobile_number', 'gender.master_value as gender_name', 'trn_patient_family_member.date_of_birth', 'relationship.master_value as relationship')
            ->where('trn_patient_family_member.patient_id', $patient_id)
            ->where('trn_patient_family_member.is_active', 1)
            ->get();

        // Get the current year for age calculation
        $currentYear = Carbon::now()->year;
        $carbonDate = Carbon::parse($accountHolder->patient_dob);
        $year = $carbonDate->year;

        // Extract account holder's details and add to family_details array
        $family_details[] = [
            'member_id' => $accountHolder->id,
            'family_member_id' => 0,
            'member_name' => $accountHolder->patient_name,
            'relationship_id' => 0,
            'yourself' => 1,
            'relationship' => "Yourself",
            'age' => $currentYear - $year,
            'dob' => Carbon::parse($accountHolder->patient_dob)->format('d-m-Y'),
            'gender' => $accountHolder->gender_name,
            'mobile_number' => $accountHolder->patient_mobile,
            'email_address' => $accountHolder->patient_email,
        ];

        // Extract details of each family member and add to family_details array
        foreach ($members as $member) {
            $carbonDate = Carbon::parse($member->date_of_birth);
            $year = $carbonDate->year;

            $family_details[] = [
                'member_id' => $accountHolder->id,
                'family_member_id' => $member->id,
                'member_name' => $member->family_member_name,
                'relationship_id' => 1,
                'yourself' => 0,
                'relationship' => $member->relationship,
                'age' => $currentYear - $year,
                'dob' => Carbon::parse($member->date_of_birth)->format('d-m-Y'),
                'gender' => $member->gender_name,
                'mobile_number' => $member->mobile_number,
                'email_address' => $member->email_address,
            ];
        }
        // Return the family_details array
        return $family_details;
    }
    // $fee = 100.10;
    public static function amountDecimal($fee)
    {
        $parts = explode('.', $fee);
        // Check if there is a decimal point and digits after it
        if (count($parts) == 2 && strlen($parts[1]) > 0) {
            // Extract the first 3 digits after the decimal point
            $decimalDigits = substr($parts[1], 0, 3);

            // Determine the third digit after the decimal point
            $thirdDigit = (strlen($decimalDigits) >= 3) ? intval($decimalDigits[2]) : 0;

            // If the third digit is greater than or equal to 5, round up the second digit
            if ($thirdDigit >= 5) {
                $decimalDigits = substr($parts[1], 0, 2);
                $secondDigit = (strlen($decimalDigits) >= 2) ? intval($decimalDigits[1]) : 0;
                $secondDigit += 1;
                $decimalDigits = $parts[1][0] . $secondDigit;
            } else {
                // If not, use the original two digits
                $decimalDigits = rtrim(substr($parts[1], 0, 2), '0');
            }

            // If there are remaining digits, use the whole part and remaining digits
            $result = $parts[0] . ($decimalDigits !== '' ? '.' . $decimalDigits : '');
        } else {
            // No decimal point or digits after it, use the original value
            $result = $fee;
        }

        return $result;
    }

    public static function isWellnessAvailable($booking_date, $weekDayId, $branch_id, $wellness_id)
    {
        $checkAvailableSlots = 0;
        $wellness_duration = Mst_Wellness::where('wellness_id', $wellness_id)->value('wellness_duration');

        $assignedTherapyRooms = Mst_Wellness_Therapyrooms::where('branch_id', $branch_id)
            ->where('wellness_id', $wellness_id)
            ->get(['therapy_room_id'])
            ->pluck('therapy_room_id')
            ->toArray();

        $weekDayTherapyRooms = Mst_Therapy_Room_Slot::where('week_day', $weekDayId)
            ->whereIn('therapy_room_id', $assignedTherapyRooms)
            ->distinct()
            ->pluck('therapy_room_id')
            ->toArray();

        $isAnyBookings = Trn_Consultation_Booking::where('booking_date', $booking_date)
            ->whereIn('booking_status_id', [87, 88, 89])
            ->where('booking_type_id', '!=', 84)
            ->count();

        if ($isAnyBookings > 0) {
            $booked_therapy_rooms = Trn_Consultation_Booking::where('booking_date', $booking_date)
                ->whereIn('therapy_room_id', $weekDayTherapyRooms)
                ->where('booking_type_id', '!=', 84)
                ->whereIn('booking_status_id', [87, 88, 89])
                ->distinct()
                ->pluck('therapy_room_id')
                ->toArray();

            // Remove booked_therapy_rooms from weekDayTherapyRooms. wellness is assigned for this therapy so there must be atleast 1 available slot, but still check slot.
            $rest_therapy_rooms = array_diff($weekDayTherapyRooms, $booked_therapy_rooms);
            if ($rest_therapy_rooms) {
                foreach ($rest_therapy_rooms as $rest_therapy_room) {
                    $rest_therapy_room_slots = Mst_Therapy_Room_Slot::where('week_day', $weekDayId)
                        ->where('therapy_room_id', $rest_therapy_room)
                        ->distinct()
                        ->pluck('timeslot')
                        ->toArray();
                    foreach ($rest_therapy_room_slots as $rest_therapy_room_slot) {
                        $slotDetails = Mst_TimeSlot::where('id', $rest_therapy_room_slot)
                            ->first();

                        $time_from = strtotime($slotDetails->time_from);
                        $time_to = strtotime($slotDetails->time_to);

                        // Calculate duration in minutes
                        $duration_minutes = round(($time_to - $time_from) / 60);
                        if ($duration_minutes >= $wellness_duration) {
                            $checkAvailableSlots = 1;
                            break 2;
                        }
                    }
                }
            }
            if ($checkAvailableSlots == 0) {
                foreach ($weekDayTherapyRooms as $weekDayTherapyRoom) {
                    $booked_time_slots = Trn_Consultation_Booking::where('booking_date', $booking_date)
                        ->where('therapy_room_id', $weekDayTherapyRoom)
                        ->where('booking_type_id', '!=', 84)
                        ->whereIn('booking_status_id', [87, 88, 89])
                        ->distinct()
                        ->pluck('time_slot_id')
                        ->toArray();

                    foreach ($booked_time_slots as $booked_time_slot) {
                        $lastInsertedBooking = Trn_Consultation_Booking::where('booking_date', $booking_date)
                            ->where('therapy_room_id', $weekDayTherapyRoom)
                            ->where('time_slot_id', $booked_time_slot)
                            ->whereIn('booking_status_id', [87, 88, 89])
                            ->latest('created_at')
                            ->first();

                        if ($lastInsertedBooking->remaining_time > $wellness_duration) {
                            $checkAvailableSlots = 1;
                            break 2;
                        }
                    }
                }
            }
        } else {
            $checkAvailableSlots = 1;
        }
        return $checkAvailableSlots;
    }


    public static function wellnessAvailability($booking_date, $weekDayId, $branch_id, $wellness_id)
    {
        $finalSlots = array();
        $checkArray = array();

        $wellness_duration = Mst_Wellness::where('wellness_id', $wellness_id)->value('wellness_duration');

        $assignedTherapyRooms = Mst_Wellness_Therapyrooms::where('branch_id', $branch_id)
            ->where('wellness_id', $wellness_id)
            ->get(['therapy_room_id'])
            ->pluck('therapy_room_id')
            ->toArray();

        $weekDayTherapyRooms = Mst_Therapy_Room_Slot::where('week_day', $weekDayId)
            ->whereIn('therapy_room_id', $assignedTherapyRooms)
            ->distinct()
            ->pluck('therapy_room_id')
            ->toArray();

        // Condition except consultation - 84 (consultation)
        $isAnyBookings = Trn_Consultation_Booking::where('booking_date', $booking_date)
            ->whereIn('booking_status_id', [87, 88, 89])
            ->where('booking_type_id', '!=', 84)
            ->count();

        if ($isAnyBookings > 0) {
            $booked_therapy_rooms = Trn_Consultation_Booking::where('booking_date', $booking_date)
                ->whereIn('booking_status_id', [87, 88, 89])
                ->where('booking_type_id', '!=', 84)
                ->distinct()
                ->pluck('therapy_room_id')
                ->toArray();

            foreach ($booked_therapy_rooms as $booked_therapy_room) {
                $booked_time_slots = Trn_Consultation_Booking::where('booking_date', $booking_date)
                    ->where('therapy_room_id', $booked_therapy_room)
                    ->where('booking_type_id', '!=', 84)
                    ->whereIn('booking_status_id', [87, 88, 89])
                    ->distinct()
                    ->pluck('time_slot_id')
                    ->toArray();

                foreach ($booked_time_slots as $booked_time_slot) {
                    $lastInsertedBooking = Trn_Consultation_Booking::where('booking_date', $booking_date)
                        ->where('therapy_room_id', $booked_therapy_room)
                        ->where('time_slot_id', $booked_time_slot)
                        ->whereIn('booking_status_id', [87, 88, 89])
                        ->latest('created_at')
                        ->first();

                    if ($lastInsertedBooking->remaining_time < $wellness_duration) {
                        // Push values to $checkArray
                        $checkArray[] = [
                            'slot_id' => $booked_time_slot,
                            'room_id' => $booked_therapy_room,
                            'is_available' => 0,
                        ];
                    }
                }
            }

            foreach ($weekDayTherapyRooms as $therapy_room) {
                $availableTherapyRoomSlots = Mst_Therapy_Room_Slot::where('week_day', $weekDayId)
                    ->where('therapy_room_id', $therapy_room)
                    ->distinct()
                    ->pluck('timeslot')
                    ->toArray();

                foreach ($availableTherapyRoomSlots as $availableTherapyRoomSlot) {
                    $slotDetails = Mst_TimeSlot::where('id', $availableTherapyRoomSlot)
                        ->first();

                    $time_from = strtotime($slotDetails->time_from);
                    $time_to = strtotime($slotDetails->time_to);

                    // Calculate duration in minutes
                    $duration_minutes = round(($time_to - $time_from) / 60);

                    if ($duration_minutes >= $wellness_duration) {
                        // Check if this slot is in $checkArray
                        $found = false;
                        foreach ($checkArray as $checkItem) {
                            if ($checkItem['room_id'] == $therapy_room && $checkItem['slot_id'] == $availableTherapyRoomSlot) {
                                $found = true;
                                break; // exit the loop once found
                            }
                        }

                        // If found, set 'is_available' to 0; otherwise, set it to 1
                        $is_available = $found ? 0 : 1;

                        $finalSlots[] = [
                            'therapy_room_id' => $therapy_room,
                            'timeslot' => $availableTherapyRoomSlot,
                            'is_available' => $is_available,
                        ];
                    }
                }
            }
        } else {
            foreach ($weekDayTherapyRooms as $therapy_room) {
                $availableTherapyRoomSlots = Mst_Therapy_Room_Slot::where('week_day', $weekDayId)
                    ->where('therapy_room_id', $therapy_room)
                    ->distinct()
                    ->pluck('timeslot')
                    ->toArray();

                foreach ($availableTherapyRoomSlots as $timeslot) {
                    $slotDetails = Mst_TimeSlot::find($timeslot);

                    $duration_minutes = Carbon::parse($slotDetails->time_from)->diffInMinutes(Carbon::parse($slotDetails->time_to));

                    if ($duration_minutes >= $wellness_duration) {
                        $finalSlots[] = [
                            'therapy_room_id' => $therapy_room,
                            'timeslot' => $timeslot,
                            'is_available' => 1,
                        ];
                    }
                }
            }
        }
        return array_values(array_unique($finalSlots, SORT_REGULAR));
    }


    public static function availableSlots($finalSlots, $booking_date)
    {
        $time_slots = array();
        $currentDate = Carbon::now()->format('Y-m-d');
        $currentTime = Carbon::now()->format('H:i:s');
        // dd($currentTime);
        if ($finalSlots) {
            foreach ($finalSlots as $timeSlot) {
                $check_available = $timeSlot['is_available'];
                $slot_details = Mst_TimeSlot::where('id', $timeSlot['timeslot'])->first();
                if ($check_available <= 0 || ($slot_details->time_from <= $currentTime && $booking_date == $currentDate)) {
                    $time_slots[] = [
                        'time_slot_id' => $slot_details->id,
                        'time_from' => Carbon::parse($slot_details->time_from)->format('h:i A'),
                        'time_to' => Carbon::parse($slot_details->time_to)->format('h:i A'),
                        'therapy_room_id' => $timeSlot['therapy_room_id'],
                        'is_available' => 0,
                    ];
                } else {

                    $time_slots[] = [
                        'time_slot_id' => $slot_details->id,
                        'time_from' => Carbon::parse($slot_details->time_from)->format('h:i A'),
                        'time_to' => Carbon::parse($slot_details->time_to)->format('h:i A'),
                        'therapy_room_id' => $timeSlot['therapy_room_id'],
                        'is_available' => $check_available,
                    ];
                }
            }
            return $time_slots;
        } else {
            return $time_slots;
        }
    }
}
