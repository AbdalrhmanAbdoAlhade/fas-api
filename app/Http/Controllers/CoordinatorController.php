<?php

namespace App\Http\Controllers;

use App\Models\Coordinator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CoordinatorController extends Controller
{
    
     protected function checkAdmin()
    {
        $user = Auth::user();

        if (!$user || $user->role != User::ROLE_ADMIN ) {
    abort(403, 'Unauthorized: Admins only');
}

    }
    
    public function index(Request $request)
    {
        
        $this->checkAdmin();

        $perPage = 5;

        $coordinators = Coordinator::latest()->paginate($perPage);

        return response()->json($coordinators);
    }

    public function store(Request $request)
    {
        // استخدام معاملة قاعدة بيانات لضمان حفظ كل من المستخدم والمنسق بنجاح.
        DB::beginTransaction();

        try {
            $validated = $request->validate([
                'name'        => 'required|string|max:255',
                'email'       => 'required|email|unique:users,email',
                'phone'       => 'nullable|string|max:20',
                'password'    => 'required|string|min:6',
                'iban_number' => 'nullable|string|max:34',
                'id_number'   => 'nullable|string|max:50',
                'id_image'    => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            // التعامل مع رفع الصورة
            if ($request->hasFile('id_image')) {
                $path = $request->file('id_image')->store('id_images', 'public');
                $validated['id_image'] = url('public/storage/' . $path);
            }

            // 1️⃣ إنشاء user أولاً
            $user = User::create([
                'name'        => $validated['name'],
                'email'       => $validated['email'],
                'phone'       => $validated['phone'] ?? null,
                'password'    => Hash::make($validated['password']), // استخدام Hash::make()
                'role'        => User::ROLE_COORDINATOR,
                'iban_number' => $validated['iban_number']?? null,
                'id_number'   => $validated['id_number']?? null,
                'id_image'    => $validated['id_image'] ?? null,
            ]);

            // 2️⃣ إنشاء coordinator بنفس id اليوزر
            $coordinator = Coordinator::create([
                'id'       => $user->id, // هذا سيعمل الآن لأننا قمنا بتعديل النموذج
                'name'     => $validated['name'],
                'email'    => $validated['email'],
                'phone'    => $validated['phone'] ?? null,
                'password' => $validated['password'], // سيتم تشفيرها تلقائيًا بواسطة النموذج
            ]);

            // إذا نجحت العمليتان، قم بحفظ التغييرات في قاعدة البيانات.
            DB::commit();

            return response()->json([
                'user'        => $user,
                'coordinator' => $coordinator,
            ], 201);

        } catch (\Exception $e) {
            // إذا حدث خطأ، قم بإلغاء كل التغييرات.
            DB::rollBack();
            // يمكنك إضافة معالجة أخطاء أكثر تفصيلاً هنا إذا احتجت.
            return response()->json(['error' => __('responses.save_data_error')], 500);
        }
    }




    public function show($id)
    {
        $coordinator = Coordinator::findOrFail($id);
        return response()->json($coordinator);
    }


    public function update(Request $request, $id)
    {
        $coordinator = Coordinator::findOrFail($id);

        $validated = $request->validate([
            'name'     => 'sometimes|string|max:255',
            'email'    => 'sometimes|email|unique:coordinators,email,' . $coordinator->id,
            'phone'    => 'nullable|string|max:20',
            'password' => 'nullable|string|min:6',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $coordinator->update($validated);

        return response()->json($coordinator);
    }


public function myLinks(Request $request)
{
    $user = auth('sanctum')->user();

    // البحث عن المنسق بناءً على الـ id
    $coordinator = \App\Models\Coordinator::where('id', $user->id)->first();

    // فحص النتيجة
    ([
        'user' => $user,
        'coordinator' => $coordinator,
    ]);

    // لو المنسق مش موجود
    if (!$coordinator) {
        return response()->json([
            'status' => false,
            'message' => __('responses.tracking_link_not_found'),
        ], 404);
    }

    // استرجاع الروابط المرتبطة بالمنسق
    $links = \App\Models\TrackingLink::where('coordinator_id', $coordinator->id)->get();

    return response()->json([
        'status' => true,
        'message' => __('responses.tracking_links_fetched'),
        'data' => $links,
    ]);
}





    public function destroy($id)
    {
        $coordinator = Coordinator::findOrFail($id);
        $coordinator->delete();

        return response()->json(['message' => __('responses.coordinator_deleted')]);
    }

   
}
