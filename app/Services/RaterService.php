<?php

namespace App\Services;

use App\Http\Requests\RatersRegisterRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RaterService
{
    /**
     * Create a new class instance.
     */
    // public function __construct()
    // {
    //     //
    // }


    //create account and register rater account
    public function create($validated,$request)
    {
        $authUser = Auth::user(); // The currently logged-in admin (who is creating the rater)

        // Create the new rater user
        $rater = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'position' => $validated['position'],
            'office' => $validated['office'],
            'password' => Hash::make($validated['password']),
            'active' => true, // Always set new raters as active
            'role_id' => 2,   // 2 = Rater
            'remember_token' => Str::random(32),
            'must_change_password' => true, // ← Force password change
        ]);

        // Attach job batches
        $rater->job_batches_rsp()->attach($validated['job_batches_rsp_id']);

        // ✅ Log the activity using Spatie Activity Log
        activity('Create')
            ->causedBy($authUser)               // The admin who created the rater
            ->performedOn($rater)               // The new rater account created
            ->withProperties([
                'created_by' => $authUser?->name,
                'new_rater_name' => $rater->name,
                'username' => $rater->username,
                'position' => $rater->position,
                'office' => $rater->office,
                'role' => 'Rater',
                'assigned_job_batches' => $validated['job_batches_rsp_id'],
                'ip' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ])
            ->log("Rater {$rater->name} was Created successfully by '{$authUser?->name}'.");

        return response()->json([
            'status' => true,
            'message' => 'Rater Registered Successfully',
            'data' => $rater->load('job_batches_rsp'),
        ], 201);
    }

    // update rater
    public function update($validated, $id, $request)
    {
        $authUser = Auth::user(); // The admin who performs the update


        // Find the user (rater) by ID
        $rater = User::findOrFail($id);

        // Keep old values for logging comparison
        $oldData = [
            'office' => $rater->office,
            'active' => $rater->active,
            'job_batches_rsp_id' => $rater->job_batches_rsp()->pluck('job_batches_rsp.id')->toArray(),

        ];

        // Update new values
        $rater->update([
            'office' => $validated['office'],
            'active' => $validated['active'],
        ]);

        // Sync job_batches_rsp if provided
        // if (isset($validated['job_batches_rsp_id'])) {
        //     $rater->job_batches_rsp()->sync($validated['job_batches_rsp_id']);
        // }

        if (isset($validated['job_batches_rsp_id'])) {
            $newJobPosts = collect($validated['job_batches_rsp_id']);

            foreach ($newJobPosts as $jobPostId) {
                // Check if pivot already exists
                $pivot = \App\Models\Job_batches_user::where('user_id', $rater->id)
                    ->where('job_batches_rsp_id', $jobPostId)
                    ->first();

                if ($pivot) {
                    // ✅ Keep existing status (complete or pending)
                    continue;
                } else {
                    // 🆕 New assignment, default to pending
                    \App\Models\Job_batches_user::create([
                        'user_id' => $rater->id,
                        'job_batches_rsp_id' => $jobPostId,
                        'status' => 'pending',
                    ]);
                }
            }

            // Optional: Remove assignments that are no longer selected but preserve completed ones
            $rater->job_batches_rsp()
                ->wherePivotNotIn('job_batches_rsp_id', $newJobPosts)
                ->wherePivot('status', '!=', 'complete')
                ->detach();
        }


        // Load updated relations
        $rater->load('job_batches_rsp');

        // ✅ Log the update activity
        activity('Update')
            ->causedBy($authUser)                // The admin who made the change
            ->performedOn($rater)                // The rater whose account was edited
            ->withProperties([
                'updated_by' => $authUser?->name,
                'rater_name' => $rater->name,
                'rater_username' => $rater->username,
                'old_data' => $oldData,
                'new_data' => [
                    'office' => $rater->office,
                    'active' => $rater->active,
                    'job_batches_rsp_id' => $validated['job_batches_rsp_id'] ?? $oldData['job_batches_rsp_id'],
                ],
                'ip' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ])
            ->log("Rater {$rater->name} was updated by '{$authUser?->name}'.");

        return response()->json([
            'status' => true,
            'message' => 'Rater Updated Successfully',
            'data' => $rater,
        ]);
    }



    // login function for rater
    public function login($request)
    {
        // First check if username and password are provided
        if (empty($request->username) || empty($request->password)) {
            return response([
                'status' => false,
                'message' => 'Invalid Credentials',
                'errors' => [
                    'username' => empty($request->username) ? ['Please enter username'] : [],
                    'password' => empty($request->password) ? ['Please enter password'] : []
                ]
            ], 401);
        }

        $user = User::where('username', $request->username)->first();
        if (!$user) {
            return response([
                'status' => false,
                'message' => 'Invalid Credentials',
                'errors' => [
                    'username' => ['Username does not exist'],
                    'password' => ['Please enter password']
                ]
            ], 401);
        }

        // Then check if the password is correct
        if (!Hash::check($request->password, $user->password)) {
            return response([
                'status' => false,
                'message' => 'Invalid Credentials',
                'errors' => [
                    'password' => ['Wrong password']
                ]
            ], 401);
        }

        // check if the active or  inactive
        if ($user->active != 1) {
            return response([
                'status' => false,
                'errors' => [
                    'active' => ['Access Denied: Your account is inactive. Please contact the administrator']
                ]
            ], 403);
        }

        // Only allow users with role_id == 1
        if ($user->role_id != 2) {
            return response([
                'status' => false,
                'message' => 'Access Denied: You do not have permission to login.',
                'errors' => [
                    'role_id' => ['Only Rater admin can login.']
                ]
            ], 403);
        }

        // Authenticate the user
        Auth::login($user);

        $user = Auth::user();

        // Check if the user is active
        if (!$user->active) {
            return response([
                'status' => false,
                'message' => 'Your account is inactive. Please contact the administrator.',
            ], 403);
        }

        // Generate a token for the user
        // $token = $user->createToken('my-secret-token')->plainTextToken;

        $user->tokens()->delete();

        $token = $user->createToken('rater_token')->plainTextToken;
        // Set the token in a secure cookie
        $cookie = cookie('rater_token', $token, 60 * 24, null, null, true, true, false, 'None');

        //  Log the activity using Spatie Activity Log
        // ✅ Fix: Ensure the correct type for Spatie activity log
        if ($user instanceof \App\Models\User) {
            activity('Login')
                ->causedBy($user)
                ->performedOn($user)
                ->withProperties([
                    'rater_name' => $user->name,
                    'rater_username' => $user->username,
                    'role' => $user->role?->role_name,
                    'office' => $user->office,
                    'ip' => $request->ip(),
                    'user_agent' => $request->header('User-Agent'),
                ])
                ->log("Rater {$user->name} logged in successfully.");
        }


        return response([
            'status' => true,
            'message' => 'Login Successfully',
            'user' => [
                'name' => $user->name,
                'position' => $user->position,
                'role_id' => (int)$user->role_id, // Always integer
                'role_name' => $user->role?->name, // Optional chaining in case it's null

            ],
            'token' => $token,
        ])->withCookie($cookie);
    }


    // change password for the rater
    public function updatePassword($validated,$request)
    {

        if ($validated->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validated->errors()
            ], 422);
        }

        // Get authenticated rater
        $rater = $request->user();

        // Verify old password
        if (!Hash::check($request->old_password, $rater->password)) {
            return response()->json([
                'status' => false,
                'errors' => ['old_password' => ['The current password is incorrect']]
            ], 422);
        }

        // Update password
        $rater->password = Hash::make($request->new_password);
        $rater->save();


        // ✅ Log activity using Spatie Activitylog
        if ($rater instanceof \App\Models\User) {
            activity('Change Credentials')
                ->causedBy($rater)
                ->performedOn($rater)
                ->withProperties([
                    'rater_name' => $rater->name,
                    'rater_username' => $rater->username,
                    'role' => $rater->role?->role_name,
                    'office' => $rater->office,
                    'ip' => $request->ip(),
                    'user_agent' => $request->header('User-Agent'),
                ])
                ->log("Rater {$rater->name} changed their password.");
        }


        return response()->json([
            'status' => true,
            'message' => 'Password changed successfully'
        ]);
    }

    // change password rater account if first time login
    public function changePassword($validator,$request)
    {

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        // Verify current password
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect'
            ], 422);
        }

        // Check if new password is same as old
        if (Hash::check($request->new_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'New password must be different from current password'
            ], 422);
        }

        // Update password
        $user->password = Hash::make($request->new_password);
        $user->must_change_password = false;
        $user->password_changed_at = now();
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
    }
}
