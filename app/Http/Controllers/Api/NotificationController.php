<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Trn_Notification;
use App\Models\Trn_Patient_Device_Tocken;

class NotificationController extends Controller
{
    public function notifications(Request $request)
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
                $notifications = [];
                $user_notifications = [];
                // Retrieve all consultation bookings for the given patient
                $user_notifications = Trn_Notification::where('patient_id', $patient_id)->orderBy('created_at', 'desc')->get();
                foreach ($user_notifications as $user_notification) {
                    $time = Carbon::parse($user_notification->created_at);

                    $diffInDays = $time->diffInDays(Carbon::now());
                    $isYesterday = $time->isYesterday();

                    if ($diffInDays == 1 && $isYesterday) {
                        $formattedTime = '1 day ago';
                    } elseif ($diffInDays > 1) {
                        $formattedTime = $time->diffForHumans(null, true) . ' ago';
                    } else {
                        $formattedTime = $time->format('g:i A');
                    }

                    $notifications[] = [
                        'id' => $user_notification->id,
                        'title' => $user_notification->title,
                        'content' => $user_notification->content,
                        'read_status' => $user_notification->read_status,
                        'time' => $formattedTime,
                    ];
                }

                $limit = $request->input('limit', 10); // Default limit is 5
                $page_number = $request->input('page_number', 1); // Default page number is 1

                // Create a collection from the array
                $notification_collection = collect($notifications);

                // Get a portion of the collection based on the pagination parameters
                $all_notifications = $notification_collection->slice(($page_number - 1) * $limit, $limit)->all();

                $data['status'] = 1;
                $data['message'] = "Data fetched";
                $data['data'] = array_values($all_notifications);
                $data['pagination_details'] = [
                    'current_page' => $page_number,
                    'total_records' => count($notifications),
                    'total_pages' => ceil(count($notifications) / $limit),
                    'per_page' => $limit,
                    'first_page_url' => $page_number > 1 ? (string)($page_number = 1) : null,
                    'last_page_url' => $page_number < ceil(count($notifications) / $limit) ? (string) ceil(count($notifications) / $limit) : null,
                    'next_page_url' => $page_number < ceil(count($notifications) / $limit) ? (string) ($page_number + 1) : null,
                    'prev_page_url' => $page_number > 1 ? (string) ($page_number - 1)  : null,
                ];

                return response($data);
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

    public function read_status(Request $request)
    {
        $data = array();
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'notification_id' => ['required'],
                    'read_status' => ['required'],
                ],
                [
                    'notification_id.required' => 'Notification id required',
                    'read_status.required' => 'Read status required',
                ]
            );

            if (!$validator->fails()) {
                if (isset($request->notification_id) && $request->notification_id != "null" && $request->notification_id != null && isset($request->read_status) && $request->read_status != "null" && $request->read_status != null) {
                    // Retrieve the patient ID from token
                    $patient_id = Auth::id();

                    // If the patient does not exist, return an error response
                    if (!$patient_id) {
                        $data['status'] = 0;
                        $data['message'] = "User does not exist.";
                        return response($data);
                    }

                    $is_exist = Trn_Notification::where('id', $request->notification_id)->where('read_status', $request->read_status)->first();

                    if ($is_exist) {
                        $data['status'] = 0;
                        $data['message'] = "Notification already has the specified read status.";
                        return response($data);
                    }

                    Trn_Notification::where('id', $request->notification_id)->update([
                        'updated_at' => Carbon::now(),
                        'read_status' => $request->read_status,
                    ]);
                    $data['status'] = 1;
                    $data['message'] = "Read status updated successfully";
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

    // To save device token 
    public function device_token(Request $request)
    {
        $data = array();
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'device_token' => ['required'],
                ],
                [
                    'device_token.required' => 'Device token required',
                ]
            );

            if (!$validator->fails()) {
                if (isset($request->device_token)) {
                    // Retrieve the patient ID from token
                    $patient_id = Auth::id();
                    // If the patient does not exist, return an error response
                    if (!$patient_id) {
                        $data['status'] = 0;
                        $data['message'] = "User does not exist.";
                        return response($data);
                    }

                    // Save patient device token
                    if (isset($request->device_token)) {
                        $checkExists = Trn_Patient_Device_Tocken::where('patient_id', $patient_id)->where('patient_device_token', $request->device_token)->first();
                        if ($checkExists) {
                            $data['status'] = 0;
                            $data['message'] = "This device token is already exists.";
                            return response($data);
                        }
                        $pdt = new Trn_Patient_Device_Tocken;
                        $pdt->patient_id = $patient_id;
                        $pdt->patient_device_token = $request->device_token;
                        $pdt->created_at = Carbon::now();
                        $pdt->updated_at = Carbon::now();
                        $pdt->save();
                    }

                    $data['status'] = 1;
                    $data['message'] = "Device token saved successfully";
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
