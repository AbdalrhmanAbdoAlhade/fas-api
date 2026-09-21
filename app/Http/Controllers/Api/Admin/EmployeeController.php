<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    /**
     * GET /api/admin/employees
     */
    public function index(Request $request)
    {
        $query = User::where('role', 'employee')
            ->select('id', 'name', 'email', 'phone', 'role', 'created_at')
            ->with('permissions:id,name,module,action,description_ar,description_en');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $employees = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json([
            'status' => true,
            'data'   => $employees,
        ]);
    }

    /**
     * POST /api/admin/employees
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'phone'    => 'nullable|string|max:20',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $employee = User::create([
            'name'     => $request->input('name'),
            'email'    => $request->input('email'),
            'phone'    => $request->input('phone'),
            'password' => Hash::make($request->input('password')),
            'role'     => 'employee',
        ]);

        // ✅ إرجاع بيانات مختصرة فقط
        return response()->json([
            'status'  => true,
            'message' => __('responses.employee_created_successfully'),
            'data'    => [
                'id'    => $employee->id,
                'name'  => $employee->name,
                'email' => $employee->email,
                'phone' => $employee->phone,
                'role'  => $employee->role,
            ],
        ], 201);
    }

    /**
     * GET /api/admin/employees/{id}
     */
    public function show(int $id)
    {
        $employee = User::where('role', 'employee')
            ->select('id', 'name', 'email', 'phone', 'role', 'created_at', 'updated_at')
            ->with('permissions:id,name,module,action,description_ar,description_en')
            ->find($id);

        if (!$employee) {
            return response()->json([
                'status'  => false,
                'message' => __('responses.employee_not_found'),
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data'   => $employee,
        ]);
    }

    /**
     * PUT /api/admin/employees/{id}
     */
    public function update(Request $request, int $id)
    {
        $employee = User::where('role', 'employee')->find($id);

        if (!$employee) {
            return response()->json([
                'status'  => false,
                'message' => __('responses.employee_not_found'),
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'     => 'sometimes|string|max:255',
            'email'    => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($id)],
            'phone'    => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $request->only(['name', 'email', 'phone']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->input('password'));
        }

        $employee->update($data);

        // ✅ إرجاع بيانات مختصرة
        return response()->json([
            'status'  => true,
            'message' => __('responses.employee_updated_successfully'),
            'data'    => [
                'id'    => $employee->fresh()->id,
                'name'  => $employee->fresh()->name,
                'email' => $employee->fresh()->email,
                'phone' => $employee->fresh()->phone,
                'role'  => $employee->fresh()->role,
            ],
        ]);
    }

    /**
     * DELETE /api/admin/employees/{id}
     */
    public function destroy(int $id)
    {
        $employee = User::where('role', 'employee')->find($id);

        if (!$employee) {
            return response()->json([
                'status'  => false,
                'message' => __('responses.employee_not_found'),
            ], 404);
        }

        $employee->permissions()->detach();
        $employee->delete();

        return response()->json([
            'status'  => true,
            'message' => __('responses.employee_deleted_successfully'),
        ]);
    }

    /**
     * GET /api/admin/permissions
     * عرض كل الصلاحيات المتاحة (مجمعة بالـ module)
     */
    public function allPermissions()
    {
        $permissions = Permission::all()->groupBy('module');

        return response()->json([
            'status' => true,
            'data'   => $permissions,
        ]);
    }

    /**
     * POST /api/admin/employees/{id}/permissions
     * تعيين صلاحيات لموظف
     */
    public function assignPermissions(Request $request, int $id)
    {
        $employee = User::where('role', 'employee')->find($id);

        if (!$employee) {
            return response()->json([
                'status'  => false,
                'message' => __('responses.employee_not_found'),
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'permissions'   => 'required|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $permissionIds = Permission::whereIn('name', $request->input('permissions'))
            ->pluck('id');

        $admin = Auth::user();
        $syncData = $permissionIds->mapWithKeys(function ($id) use ($admin) {
            return [$id => [
                'granted_by' => $admin->id,
                'granted_at' => now(),
            ]];
        })->toArray();

        $employee->permissions()->sync($syncData);

        // ✅ إرجاع بيانات مختصرة
        return response()->json([
            'status'  => true,
            'message' => __('responses.permissions_assigned_successfully'),
            'data'    => [
                'id'          => $employee->id,
                'name'        => $employee->name,
                'email'       => $employee->email,
                'phone'       => $employee->phone,
                'role'        => $employee->role,
                'permissions' => $employee->fresh()->load('permissions:id,name,module,action')->permissions,
            ],
        ]);
    }

    /**
     * DELETE /api/admin/employees/{id}/permissions
     * إزالة كل الصلاحيات
     */
    public function revokeAll(int $id)
    {
        $employee = User::where('role', 'employee')->find($id);

        if (!$employee) {
            return response()->json([
                'status'  => false,
                'message' => __('responses.employee_not_found'),
            ], 404);
        }

        $employee->permissions()->detach();

        return response()->json([
            'status'  => true,
            'message' => __('responses.all_permissions_revoked'),
        ]);
    }
}