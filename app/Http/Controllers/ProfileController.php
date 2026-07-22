<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User; 
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function show()
    {
        return response()->json(Auth::user());
    }

  public function update(Request $request)
    {
        try {
            $user = Auth::user();

            // قواعد التحقق من الحقول المرنة
            $rules = [
                'name'       => 'string|max:255',
                'image'      => 'image|mimes:jpeg,png,jpg|max:2048',
                'full_name'  => 'string|max:255',
                'last_name'  => 'string|max:255',
                'birth_date' => 'date',
                'email'      => 'email|unique:users,email,' . $user->id,
                'phone'      => 'string|max:20',
                'gender'     => 'in:male,female',
            ];

            // التحقق فقط من الحقول المرسلة
            $validated = $request->validate(array_intersect_key($rules, $request->all()));

            // رفع الصورة بنفس مسار نظام الأوث
            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('users/images', 'public');
                $validated['image'] = '/storage/' . $path;
            }

            // تحديث المستخدم
            $user->update($validated);

            // تحديث نسخة المستخدم بعد الحفظ
            $user->refresh();

            // تعديل مسار الصورة في الاستجابة
            if ($user->image && !str_starts_with($user->image, '/storage/')) {
                $user->image = '/storage/' . ltrim($user->image, '/');
            }

            return response()->json([
                'message' => __('responses.profile_updated'),
                'user'    => $user
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => __('responses.validation_error'),
                'errors'  => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('responses.profile_update_error'),
                'error'   => $e->getMessage()
            ], 500);
        }
    }


}
