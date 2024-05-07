<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\DeviceTockenHelper;
use App\Models\Trn_Notification;
use App\Models\Mst_Patient;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Helpers\PatientHelper;
use App\Models\Trn_Patient_Otp;
use App\Models\Trn_Patient_Device_Tocken;
use Illuminate\Support\Str;

class PatientAuthController extends Controller
{
    public function patientRegister(Request $request)
    {
        $data = array();
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'patient_name'      => 'required',
                    'patient_email'     => 'required|email|unique:mst_patients,patient_email',
                    'patient_mobile'    => ['required', 'regex:/^[0-9]{10}$/', 'unique:mst_patients,patient_mobile'],
                    'patient_address'      => 'required',
                    'patient_gender'      => 'required',
                    'patient_dob'      => 'required',
                    'password'          => 'required|min:6',
                    'retype_password'   => 'required|min:6|same:password',
                ],
                [
                    'patient_name.required'         => 'Name required',
                    'patient_email.required'        => 'Email address required',
                    'patient_email.email'           => 'Invalid email address',
                    'patient_email.unique'          => 'Email address is already in use',
                    'patient_mobile.required'       => 'Mobile number required',
                    'patient_mobile.regex'          => 'Invalid mobile number',
                    'patient_mobile.unique'         => 'Mobile number is already in use',
                    'patient_address.required'         => 'Address required',
                    'patient_gender.required'         => 'Gender required',
                    'patient_dob.required'         => 'Date of birth required',
                    'password.required'             => 'Password required',
                    'retype_password.required'      => 'Retype password required',
                    'retype_password.same'          => 'Passwords do not match',
                ]
            );

            if (!$validator->fails()) {
                if (isset($request->patient_name) && isset($request->patient_email) && isset($request->patient_mobile) && isset($request->patient_address) && isset($request->patient_gender) && isset($request->patient_dob) && isset($request->password)) {
                    // Check if a patient with the provided mobile number or email already exists
                    $patients = Mst_Patient::where('patient_mobile', $request->patient_mobile)
                        ->orWhere('patient_email', $request->patient_email)
                        ->first();

                    if ($request->patient_gender) {
                        $patient_gender_id = $request->patient_gender;
                    }

                    if ($request->patient_blood_group) {
                        $patient_blood_group_id = $request->patient_blood_group;
                    }

                    $patient_dob = PatientHelper::dateFormatDb($request->patient_dob);

                    if (!$patients) {
                        // Insert new patient record in the database
                        $lastInsertedId = Mst_Patient::insertGetId([
                            'patient_name'      => $request->patient_name,
                            'patient_email'     => $request->patient_email,
                            'patient_address'   => $request->patient_address,
                            'patient_gender'    => $patient_gender_id,
                            'patient_dob'       => $patient_dob,
                            'patient_blood_group_id'  => $patient_blood_group_id ?? null,
                            'patient_mobile'    => $request->patient_mobile,
                            'password'          => Hash::make($request->password),
                            'patient_registration_type'=>$request->patient_registration_type,
                            'has_credit'=>$request->has_credit??0,
                            'is_active'         => 1,
                            'is_approved'      =>0,
                            'available_membership'  => 0,
                            'patient_code'         => rand(50, 100),
                            'created_at'         => Carbon::now(),
                        ]);

                        // Generate OTP and update patient code
                        $verification_otp = rand(100000, 999999);
                        $leadingZeros = str_pad('', 3 - strlen($lastInsertedId), '0', STR_PAD_LEFT);
                        $newPatientCode = 'PAT' . $leadingZeros . $lastInsertedId;

                        // Update patient code
                        $updatePatientCode = Mst_Patient::where('id', $lastInsertedId)->update([
                            'updated_at' => Carbon::now(),
                            'patient_code' => $newPatientCode
                        ]);

                        // Create OTP record
                        $otpCreate = Trn_Patient_Otp::create([
                            'patient_id' => $lastInsertedId,
                            'otp' => $verification_otp,
                            'otp_type' => 1,
                            'verified' => 0,
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                            'otp_expire_at' => Carbon::now()->addMinutes(10),
                        ]);

                        // Save patient device token
                        if (isset($request->device_token) && isset($request->device_type)) {
                            Trn_Patient_Device_Tocken::where('patient_id', $lastInsertedId)->delete();

                            $pdt = new Trn_Patient_Device_Tocken;
                            $pdt->patient_id = $lastInsertedId;
                            $pdt->patient_device_token = $request->device_token;
                            $pdt->patient_device_type = $request->device_type;
                            $pdt->created_at = Carbon::now();
                            $pdt->updated_at = Carbon::now();
                            $pdt->save();
                        }

                        // Prepare response data
                        $data['status'] = 1;
                        $data['otp'] = $verification_otp;
                        $data['otp_type'] = 1;
                        $data['patient_id'] = $lastInsertedId;
                        $data['message'] = "OTP sent successfully.";
                        return response($data);
                    } else {

                        $data['status'] = 0;
                        $data['message'] = "Mobile number or Email address is already in use.";
                        return response($data);
                    }
                }
            } else {
                $data['status'] = 0;
                $errors = $validator->errors();
                $flattenedErrors = [];

                foreach ($errors->messages() as $field => $messages) {
                    $flattenedErrors[$field] = $messages[0];
                }

                $data['errors'] = $flattenedErrors;
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

    public function patientLogin(Request $request)
    {
        $data = array();
        try {
            $mobile = $request->input('patient_mobile');
            $passChk = $request->input('password');
            $validator = Validator::make(
                $request->all(),
                [
                    'patient_mobile'    => ['required', 'regex:/^[0-9]{10}$/'],
                    'password'          => 'required|min:6',
                ],
                [
                    'patient_mobile.required'       => 'Mobile number required',
                    'patient_mobile.regex'          => 'Invalid mobile number',
                    'password.required'             => 'Password required',
                ]
            );

            if (!$validator->fails()) {
                // Check if the patient with the provided mobile number exists
                $patient = Mst_Patient::where('patient_mobile', $mobile)->first();

                // If patient doesn't exist, return an error response
                if (!$patient) {
                    $data['status'] = 0;
                    $data['message'] = "Invalid Login Details";
                    return response($data);
                }

                // Check if the provided password matches the stored hashed password
                if (Hash::check($passChk, $patient->password)) {
                    // Create a token for the patient
                    $check_array = ['patient_mobile' => request('patient_mobile'), 'password' => request('password')];

                    // If the token is created successfully, prepare and return the success response
                    if ($check_array) {
                        $is_verified = Mst_Patient::where('patient_mobile', $mobile)->where('is_otp_verified', 1)->first();
                        if ($is_verified) {
                            $data['token'] =  $patient->createToken('Patient Token', ['patient']);
                            $data['status'] = 1;
                            $data['message'] = "Login Success";
                            $data['name'] = $patient->patient_name;
                            $data['patient_id'] = $patient->id;
                            $data['patient_mobile'] = $patient->patient_mobile;
                            $data['email_address'] = $patient->patient_email;
                            return response($data);
                        } else {
                            // Generate a new verification OTP
                            $verification_otp = rand(100000, 999999);

                            // Create a new OTP record for the patient
                            $otpCreate = Trn_Patient_Otp::create([
                                'patient_id' => $patient->id,
                                'otp' => $verification_otp,
                                'otp_type' => 4,
                                'verified' => 0,
                                'created_at' => Carbon::now(),
                                'updated_at' => Carbon::now(),
                                'otp_expire_at' => Carbon::now()->addMinutes(10),
                            ]);

                            // Prepare and return a success response
                            $data['status'] = 2;
                            $data['otp'] = $verification_otp;
                            $data['otp_type'] = 4;
                            $data['patient_id'] = $patient->id;
                            $data['message'] = "OTP not verified. Kindly verify the OTP sent to registered mobile number.";
                            return response($data);
                        }
                    } else {
                        // If token creation fails, return an error response
                        $data['status'] = 0;
                        $data['message'] = "Invalid Login Details";
                        return response($data);
                    }
                } else {
                    // If password doesn't match, return an error response
                    $data['status'] = 0;
                    $data['message'] = "Invalid Login Details";
                    return response($data);
                }
            } else {
                $data['status'] = 0;
                $errors = $validator->errors();
                $flattenedErrors = [];

                foreach ($errors->messages() as $field => $messages) {
                    $flattenedErrors[$field] = $messages[0];
                }

                $data['errors'] = $flattenedErrors;
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



    public function otpVerification(Request $request)
    {
        $data = array();
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'otp' => ['required', 'numeric', 'digits:6'],
                    'otp_type' => ['required'],
                    'patient_id' => ['required'],
                ],
                [
                    'otp.required' => 'OTP required',
                    'otp_type.required' => 'OTP type required',
                    'otp.numeric' => 'OTP must be numeric',
                    'otp.digits' => 'OTP must be 6 digits',
                    'patient_id.required' => 'Patient id required',
                ]
            );

            if (!$validator->fails()) {
                if (isset($request->patient_id, $request->otp_type, $request->otp)) {
                    // Retrieve the patient ID from the request
                    $patient_id = $request->input('patient_id');

                    // Find the patient using the patient ID
                    $patient = Mst_Patient::find($patient_id);

                    // If the patient does not exist, return an error response
                    if (!$patient) {
                        $data['status'] = 0;
                        $data['message'] = "User does not exist.";
                        return response($data);
                    }

                    // Retrieve the latest inserted OTP row for the specified patient and OTP type
                    $lastInsertedRow = Trn_Patient_Otp::where('otp_type', $request->otp_type)
                        ->where('patient_id', $patient_id)
                        ->where('otp', $request->otp)
                        ->latest('otp_id')
                        ->first();

                    // Check if the retrieved row exists and if the OTP matches
                    if ($lastInsertedRow && $lastInsertedRow->otp == $request->otp) {
                        // Check if the OTP is not expired
                        if (Carbon::now()->lessThanOrEqualTo($lastInsertedRow->otp_expire_at)) {
                            // Update the OTP row as verified
                            Trn_Patient_Otp::where('otp_id', $lastInsertedRow->otp_id)->update([
                                'updated_at' => Carbon::now(),
                                'verified' => 1,
                            ]);

                            // Update patient's OTP verification status if the OTP type is 1 (Registration OTP)
                            if ($request->otp_type == 1) {
                                Mst_Patient::where('id', $patient_id)->update([
                                    'updated_at' => Carbon::now(),
                                    'is_otp_verified' => 1,
                                    'is_approved'=>1
                                ]);
                            }

                            if ($request->otp_type == 3) {
                                $getData =  Mst_Patient::where('id', $patient_id)->first();
                                if ($getData) {
                                    Mst_Patient::where('id', $patient_id)->update([
                                        'updated_at' => Carbon::now(),
                                        'patient_mobile' => $getData->patient_mobile_new,
                                        'is_otp_verified' => 1,
                                         'is_approved'=>1
                                    ]);
                                } else {
                                    $data['status'] = 0;
                                    $data['message'] = "Something went wrong";
                                    return response($data);
                                }
                            }

                            // Update patient's OTP verification status if the OTP type is 1 (Registration OTP)
                            if ($request->otp_type == 4) {
                                Mst_Patient::where('id', $patient_id)->update([
                                    'updated_at' => Carbon::now(),
                                    'is_otp_verified' => 1,
                                ]);
                            }

                            // Prepare and return a success response
                            $data['status'] = 1;
                            if ($request->otp_type == 1) {
                                $data['message'] = "Registration completed successfully";
                            }

                            if ($request->otp_type == 2) {
                                $data['message'] = "OTP verified successfully";
                            }

                            if ($request->otp_type == 3) {
                                $data['message'] = "OTP verified successfully";
                            }
                            if ($request->otp_type == 4) {
                                $data['message'] = "OTP verified successfully";
                            }
                            return response($data);
                        } else {
                            // Return an error response if the OTP is expired
                            $data['status'] = 0;
                            $data['message'] = "OTP Expired";
                            return response($data);
                        }
                    } else {
                        // Return an error response if the OTP doesn't match
                        $data['status'] = 0;
                        $data['message'] = "OTP doesn't match";
                        return response($data);
                    }
                } else {
                    $data['status'] = 0;
                    $data['message'] = "Patient id / Otp type / Otp is required";
                    return response($data);
                }
            } else {
                $data['status'] = 0;
                $errors = $validator->errors();
                $flattenedErrors = [];

                foreach ($errors->messages() as $field => $messages) {
                    $flattenedErrors[$field] = $messages[0];
                }

                $data['errors'] = $flattenedErrors;
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

    public function reSendOtp(Request $request)
    {
        $data = array();
        try {
            $patient_id = $request->input('patient_id');

            $validator = Validator::make(
                $request->all(),
                [
                    'patient_id' => ['required'],
                ],
                [
                    'patient_id.required' => 'Patient id required',
                ]
            );

            if (!$validator->fails()) {
                // Retrieve the patient using the provided patient ID
                $patient = Mst_Patient::where('id', $patient_id)->first();

                // If the patient does not exist, return an error response
                if (!$patient) {
                    $data['status'] = 0;
                    $data['message'] = "User does not exist.";
                    return response($data);
                }

                // Retrieve the latest inserted OTP row for the specified patient
                $lastInsertedRow = Trn_Patient_Otp::where('patient_id', $request->patient_id)
                    ->latest('otp_id')
                    ->first();

                // Check if the retrieved row exists
                if ($lastInsertedRow) {

                    // Generate a new verification OTP
                    $verification_otp = rand(100000, 999999);

                    // Update the existing OTP row with the new OTP and reset verification status
                    $resendOtp = Trn_Patient_Otp::where('otp_id', $lastInsertedRow->otp_id)->update([
                        'updated_at' => Carbon::now(),
                        'otp' => $verification_otp,
                        'verified' => 0,
                        'otp_expire_at' => Carbon::now()->addMinutes(10),
                    ]);

                    // If the OTP type is for registration, reset the OTP verification status for the patient
                    if ($lastInsertedRow->otp_type == 1) {
                        Mst_Patient::where('id', $patient_id)->update([
                            'updated_at' => Carbon::now(),
                            'is_otp_verified' => 0,
                        ]);
                    }

                    // Prepare and return a success response
                    $data['status'] = 1;
                    $data['otp'] = $verification_otp;
                    $data['otp_type'] = $lastInsertedRow->otp_type;
                    $data['patient_id'] = $patient_id;
                    $data['message'] = "OTP resent successfully.";
                    return response($data);
                }
            } else {
                $data['status'] = 0;
                $errors = $validator->errors();
                $flattenedErrors = [];

                foreach ($errors->messages() as $field => $messages) {
                    $flattenedErrors[$field] = $messages[0];
                }

                $data['errors'] = $flattenedErrors;
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

    public function forgotPassword(Request $request)
    {
        $data = array();
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'patient_mobile'    => ['required', 'regex:/^[0-9]{10}$/'],
                ],
                [
                    'patient_mobile.required'       => 'Mobile number required',
                    'patient_mobile.regex'          => 'Invalid mobile number',
                ]
            );

            if (!$validator->fails()) {
                // Retrieve patient mobile number from the request
                $patient_mobile = $request->input('patient_mobile');

                // Check if a patient with the provided mobile number exists
                $patient = Mst_Patient::where('patient_mobile', $patient_mobile)->first();

                // If a patient exists, generate a new verification OTP and create an OTP record
                if ($patient) {

                    // Generate a new verification OTP
                    $verification_otp = rand(100000, 999999);

                    // Create a new OTP record for the patient
                    $otpCreate = Trn_Patient_Otp::create([
                        'patient_id' => $patient->id,
                        'otp' => $verification_otp,
                        'otp_type' => 2,
                        'verified' => 0,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                        'otp_expire_at' => Carbon::now()->addMinutes(10),
                    ]);

                    // Prepare and return a success response
                    $data['status'] = 1;
                    $data['otp'] = $verification_otp;
                    $data['otp_type'] = 2;
                    $data['patient_id'] = $patient->id;
                    $data['message'] = "Otp sent successfully.";
                    return response($data);
                } else {
                    // If no patient is found, return an error response
                    $data['status'] = 0;
                    $data['message'] = "User doesn't exist.";
                    return response($data);
                }
            } else {
                $data['status'] = 0;
                $errors = $validator->errors();
                $flattenedErrors = [];

                foreach ($errors->messages() as $field => $messages) {
                    $flattenedErrors[$field] = $messages[0];
                }

                $data['errors'] = $flattenedErrors;
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

    public function resetPassword(Request $request)
    {
        $data = array();
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'patient_id'      => 'required',
                    'password'          => 'required|min:6',
                    'confirm_password'   => 'required|min:6|same:password',
                ],
                [
                    'patient_id.required'             => 'Patient id required',
                    'password.required'             => 'Password required',
                    'confirm_password.required'      => 'Confirm password required',
                    'confirm_password.same'          => 'Passwords do not match',
                ]
            );
            if (!$validator->fails()) {
                // Retrieve patient information based on the provided patient ID
                $patient = Mst_Patient::where('id', $request->patient_id)->first();

                // Check if a patient with the provided ID exists
                if ($patient) {
                    // Check if the provided password is different from the current password
                    if (!Hash::check($request->password, $patient->password)) {
                        // Update the patient's password and set the updated timestamp
                        $patient->password = Hash::make($request->password);
                        $patient->updated_at = Carbon::now();
                        $patient->save();

                        $patientDevice = Trn_Patient_Device_Tocken::where('patient_id', $patient->id)->get();
                        if ($patientDevice) {
                            $title = 'Password Reset Successfully';
                            $body = 'Your password has been reset successfully.';
                            $clickAction = "ChangePassword";
                            $type = "Change Password";

                            // Save notification to the patient's notification table
                            $notificationCreate = Trn_Notification::create([
                                'patient_id' => $patient->id,
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
                        // Prepare and return a success response
                        $data['status'] = 1;
                        $data['message'] = "Password reset successfully.";
                        return response($data);
                    } else {
                        // If the new password is similar to the current password, return an error response
                        $data['status'] = 0;
                        $data['message'] = "Your new password is similar to the current Password. Please try another password.";
                        return response($data);
                    }
                } else {
                    // If no patient is found with the provided ID, return an error response
                    $data['status'] = 0;
                    $data['message'] = "User does not exist.";
                    return response($data);
                }
            } else {
                $data['status'] = 0;
                $errors = $validator->errors();
                $flattenedErrors = [];

                foreach ($errors->messages() as $field => $messages) {
                    $flattenedErrors[$field] = $messages[0];
                }

                $data['errors'] = $flattenedErrors;
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

    public function getUserDetails(Request $request)
    {
        $data = array();
        $accountHolderDetails=[];
        try {
            // Get the authenticated patient's ID
            $patient_id = Auth::id();

            // Check if a patient ID is available
            if ($patient_id) {
                // Retrieve the current data of the authenticated patient
                $accountHolder = Mst_Patient::join('mst_master_values as gender', 'mst_patients.patient_gender', '=', 'gender.id')
                    ->leftJoin('mst_master_values as blood_group', 'mst_patients.patient_blood_group_id', '=', 'blood_group.id')
                    ->where('mst_patients.id', $patient_id)
                    ->select('mst_patients.*', 'gender.master_value as gender_name', 'blood_group.master_value as blood_group')
                    ->first();

                // Build an array with account holder details
                if (isset($accountHolder->blood_group)) {
                    $blood_group = $accountHolder->blood_group;
                    $blood_group_id = $accountHolder->patient_blood_group_id;
                } else {
                    $blood_group = null;
                    $blood_group_id = null;
                }
                $accountHolderDetails = [
                    'patient_id' => $accountHolder->id,
                    'patient_name' => $accountHolder->patient_name,
                    'patient_dob' => Carbon::parse($accountHolder->patient_dob)->format('d-m-Y'),
                    'patient_gender' => $accountHolder->gender_name,
                    'patient_gender_id' => $accountHolder->patient_gender,
                    'patient_mobile' => $accountHolder->patient_mobile,
                    'patient_email' => $accountHolder->patient_email,
                    'patient_address' => $accountHolder->patient_address,
                    'blood_group' => $blood_group,
                    'blood_group_id' => $blood_group_id,
                ];

                // Prepare and return a success response
                $data['status'] = 1;
                $data['message'] = "Data fetched";
                $data['data'] = $accountHolderDetails;

                return response($data);
            } else {
                // If no patient ID is found, return an error response
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

    public function updateDetails(Request $request)
    {
        $data = array();
        try {
            // Get the authenticated patient's ID
            $patient_id = Auth::id();

            // Check if a patient ID is available
            if ($patient_id) {
                // Retrieve the current data of the authenticated patient
                $currentData = Mst_Patient::where('id', $patient_id)->first();
                // Check and update patient details based on the provided request data
                if ($request->patient_blood_group) {
                    $blood_group_id = $request->patient_blood_group;
                }

                if ($request->patient_gender) {
                    $patient_gender_id = $request->patient_gender;
                }

                if (isset($request->patient_dob)) {
                    $patient_dob = PatientHelper::dateFormatDb($request->patient_dob);
                }

                // Update patient profile with the provided or current data
                Mst_Patient::where('id', $patient_id)->update([
                    'patient_name'      => $request->patient_name ?? $currentData->patient_name,
                    'patient_email'     => $request->patient_email ?? $currentData->patient_email,
                    'patient_mobile'     => $currentData->patient_mobile,
                    'patient_address'   => $request->patient_address ?? $currentData->patient_address,
                    'patient_gender'    => $patient_gender_id ?? $currentData->patient_gender,
                    'patient_dob'       => $patient_dob ?? $currentData->patient_dob,
                    'patient_blood_group_id' => $blood_group_id ?? $currentData->patient_blood_group_id,
                    'created_at'        => Carbon::now(),
                ]);

                // Check if the mobile number has changed
                $isChangedMobileNumber = 0;
                if ($request->patient_mobile) {
                    $isChangedMobileNumber = ($currentData->patient_mobile != $request->patient_mobile) ? 1 : 0;
                }

                if ($isChangedMobileNumber == 1) {
                    // Generating an OTP for verification as they are attempting to update their mobile number.

                    $save_new_mobile_number = Mst_Patient::where('id', $patient_id)->update([
                        'updated_at' => Carbon::now(),
                        'patient_mobile_new' => $request->patient_mobile,
                        'is_otp_verified' => 0,
                    ]);

                    $verificationOtp = rand(100000, 999999);

                    // Create OTP record
                    $otpCreate = Trn_Patient_Otp::create([
                        'patient_id' => $patient_id,
                        'otp' => $verificationOtp,
                        'otp_type' => 3,
                        'verified' => 0,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                        'otp_expire_at' => Carbon::now()->addMinutes(10),
                    ]);

                    $data = [
                        'status' => 3,
                        'otp' => $verificationOtp,
                        'otp_type' => 3,
                        'mobile_number' => $request->patient_mobile,
                        'patient_id' => $patient_id,
                        'message' => "Please enter the OTP that was sent to the mobile number.",
                    ];
                } else {
                    // Prepare and return a success response
                    $data['status'] = 1;
                    $data['message'] = "Profile updated successfully";
                }
                return response($data);
            } else {
                // If no patient ID is found, return an error response
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

    public function changePassword(Request $request)
    {
        $data = array();
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'old_password'      => 'required',
                    'new_password'          => 'required|min:6',
                    'confirm_password'   => 'required|min:6|same:new_password',
                ],
                [
                    'old_password.required'             => 'Old password id required',
                    'new_password.required'             => 'New password required',
                    'confirm_password.required'      => 'Confirm password required',
                    'confirm_password.same'          => 'new passwords and confirm password does not match',
                ]
            );
            if (!$validator->fails()) {
                // Check if the required password parameters are set in the request
                if (isset($request->old_password) && isset($request->new_password) && isset($request->confirm_password)) {

                    // Get the authenticated patient's ID
                    $patient_id = Auth::id();

                    // Find the patient with the given ID
                    $patient = Mst_Patient::where('id', $patient_id)->first();

                    // Check if the patient exists
                    if ($patient) {
                        // Check if the old password matches the stored password
                        if (Hash::check($request->old_password, $patient->password)) {
                            // Check if the new password is different from the old password
                            if (!Hash::check($request->new_password, $patient->password)) {
                                // Update the patient's password with the new hashed password
                                $patient->password = Hash::make($request->new_password);
                                $patient->updated_at = Carbon::now();
                                $patient->save();

                                $patientDevice = Trn_Patient_Device_Tocken::where('patient_id', $patient_id)->get();
                                if ($patientDevice) {
                                    $title = 'Password Changed Successfully';
                                    $body = 'Your password has been changed successfully.';
                                    $clickAction = "ChangePassword";
                                    $type = "Change Password";

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
                                // Prepare and return a success response
                                $data['status'] = 1;
                                $data['message'] = "Password changed sussessfully.";
                                return response($data);
                            } else {
                                // If the new password is similar to the old password, return an error response
                                $data['status'] = 0;
                                $data['message'] = "Your new password is similar to the old Password. Please try another password.";
                                return response($data);
                            }
                        } else {
                            // If the old password is incorrect, return an error response
                            $data['status'] = 0;
                            $data['message'] = "Old password is incorrect.";
                            return response($data);
                        }
                    } else {
                        // If the patient does not exist, return an error response
                        $data['status'] = 0;
                        $data['message'] = "User does not exist.";
                        return response($data);
                    }
                } else {
                    // If the required parameters are not set, return an error response
                    $data['status'] = 0;
                    $data['message'] = "Invalid request parameters.";
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

    public function logout()
    {
        try {
            // Get the authenticated user using Laravel's Auth facade
            $user = Auth::user();

            // Retrieve all tokens associated with the user and delete each token
            $user->tokens->each(function ($token, $key) {
                $token->delete();
            });

            // Prepare and return a JSON response indicating successful logout
            return response()->json(['message' => 'Logged out successfully']);
        } catch (\Exception $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        } catch (\Throwable $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        }
    }

    //Tab registration
    public function patientTabRegister(Request $request)
    {
        $data = array();
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'patient_name'      => 'required',
                    'patient_email'     => 'nullable|unique:mst_patients,patient_email',
                    'patient_mobile'    => ['required', 'regex:/^[0-9]{10}$/', 'unique:mst_patients,patient_mobile'],
                    'patient_gender'      => 'required',
                    'patient_dob'      => 'required',

                ],
                [
                    'patient_name.required'         => 'Name required',

                    'patient_email.email'           => 'Invalid email address',
                    'patient_email.unique'          => 'Email address is already in use',
                    'patient_mobile.required'       => 'Mobile number required',
                    'patient_mobile.regex'          => 'Invalid mobile number',
                    'patient_mobile.unique'         => 'Mobile number is already in use',
                    'patient_gender.required'         => 'Gender required',
                    'patient_dob.required'         => 'Date of birth required',
                ]
            );

            if (!$validator->fails()) {
                if (isset($request->patient_name) && isset($request->patient_mobile)  && isset($request->patient_gender) && isset($request->patient_dob)) {
                    // Check if a patient with the provided mobile number or email already exists
                    $patients = Mst_Patient::where('patient_mobile', $request->patient_mobile)
                        //->orWhere('patient_email', $request->patient_email)
                        ->first();

                    if ($request->patient_gender) {
                        $patient_gender_id = $request->patient_gender;
                    }

                    if ($request->patient_blood_group) {
                        $patient_blood_group_id = $request->patient_blood_group;
                    }

                    if ($request->marital_status) {
                        $marital_status = $request->marital_status;
                    }

                    if ($request->whatsapp_number) {
                        $whatsapp_number = $request->whatsapp_number;
                    }

                    if ($request->emergency_contact_person) {
                        $emergency_contact_person = $request->emergency_contact_person;
                    }

                    if ($request->emergency_contact) {
                        $emergency_contact = $request->emergency_contact;
                    }

                    if ($request->patient_address) {
                        $patient_address = $request->patient_address;
                    }

                    //Generate random Password
                    $GenRandPassword = Str::random(8);
                    $HashedPassword = Hash::make($GenRandPassword);

                    $patient_dob = PatientHelper::dateFormatDb($request->patient_dob);

                    if (!$patients) {
                        // Insert new patient record in the database
                        $lastInsertedId = Mst_Patient::insertGetId([
                            'patient_name'      => $request->patient_name,
                            'patient_email'     => $request->patient_email,
                            'maritial_status'    => $request->marital_status,
                            'whatsapp_number'    => $whatsapp_number ?? 'NULL',
                            'emergency_contact_person'    => $emergency_contact_person ?? 'NULL',
                            'emergency_contact' => $emergency_contact ?? 'NULL',
                            'patient_address'   => $patient_address ?? 'NULL',
                            'patient_gender'    => $patient_gender_id,
                            'patient_dob'       => $patient_dob,
                            'patient_blood_group_id'  => $patient_blood_group_id ?? 47, // Use the null coalescing operator
                            'patient_mobile'    => $request->patient_mobile,
                            'password'          => $HashedPassword,
                            'is_active'         => 1,
                            'available_membership'  => 0,
                            'patient_code'         => rand(50, 100),
                            'created_at'         => Carbon::now(),
                        ]);

                        // Generate OTP and update patient code
                        $verification_otp = rand(100000, 999999);
                        $leadingZeros = str_pad('', 3 - strlen($lastInsertedId), '0', STR_PAD_LEFT);
                        $newPatientCode = 'PAT' . $leadingZeros . $lastInsertedId;

                        // Update patient code
                        $updatePatientCode = Mst_Patient::where('id', $lastInsertedId)->update([
                            'updated_at' => Carbon::now(),
                            'patient_code' => $newPatientCode
                        ]);

                        // Create OTP record - Not used for tablet registration. These users will have to verify the OTP during login
                        $otpCreate = Trn_Patient_Otp::create([
                            'patient_id' => $lastInsertedId,
                            'otp' => $verification_otp,
                            'otp_type' => 1,
                            'verified' => 0,
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                            'otp_expire_at' => Carbon::now()->addMinutes(10),
                        ]);

                        //Send Mail and SMS if the registration is succesfull along with username and system generated password

                        // Prepare response data
                        $data['status'] = 1;
                        $data['patient_id'] = $lastInsertedId;
                        $data['message'] = "Registration Success";
                        return response($data);
                    } else {

                        $data['status'] = 0;
                        $data['message'] = "Mobile number or Email address is already in use.";
                        return response($data);
                    }
                }
            } else {
                $data['status'] = 0;
                $errors = $validator->errors();
                $flattenedErrors = [];

                foreach ($errors->messages() as $field => $messages) {
                    $flattenedErrors[$field] = $messages[0];
                }

                $data['errors'] = $flattenedErrors;
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
