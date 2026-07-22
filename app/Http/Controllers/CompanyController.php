<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CompanyController extends Controller
{
    /**
     * عرض كل الشركات
     */
public function index(Request $request)
{
    $query = Company::with('user')->latest();

    // لو تم إرسال user_id، نعرض فقط الشركات التابعة له
    if ($request->has('user_id')) {
        $query->where('user_id', $request->user_id);
    }

    $companies = $query->get();

    return response()->json($companies);
}


    /**
     * عرض شركة واحدة بالتفصيل
     */
    public function show($id)
    {
        $company = Company::with('user')->find($id);

        if (!$company) {
            return response()->json(['message' => __('responses.company_not_found')], 404);
        }

        return response()->json($company);
    }

    /**
     * إنشاء شركة جديدة
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'description' => 'nullable|string',
            'website' => 'nullable|url',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // رفع اللوجو إن وُجد
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('companies', 'public');
            $validated['logo'] = url('public/storage/' . $path);
        }

        $company = Company::create($validated);

        return response()->json([
            'message' => __('responses.company_created'),
            'company' => $company
        ], 201);
    }

    /**
     * تحديث بيانات الشركة (فقط الأدمن أو صاحب الشركة)
     */
    public function update(Request $request, $id)
    {
        $company = Company::find($id);

        if (!$company) {
            return response()->json(['message' => __('responses.company_not_found')], 404);
        }

        $user = Auth::user();

        // السماح فقط للأدمن أو صاحب الشركة
        if ($user->role !== 'admin' && !($user->role === 'company_owner' && $user->id === $company->user_id)) {
            return response()->json(['message' => __('responses.unauthorized_company_update')], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'address' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'website' => 'nullable|url',
            'is_active' => 'boolean',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // تحديث اللوجو إذا تم رفع ملف جديد
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('companies', 'public');
            $validated['logo'] = url('public/storage/' . $path);
        }

        $company->update($validated);

        return response()->json([
            'message' => __('responses.company_updated'),
            'company' => $company
        ]);
    }

    /**
     * حذف شركة (فقط الأدمن أو صاحب الشركة)
     */
    public function destroy($id)
    {
        $company = Company::find($id);

        if (!$company) {
            return response()->json(['message' => __('responses.company_not_found')], 404);
        }

        $user = Auth::user();

        // السماح فقط للأدمن أو صاحب الشركة
        if ($user->role !== 'admin' && !($user->role === 'company_owner' && $user->id === $company->user_id)) {
            return response()->json(['message' => __('responses.unauthorized_company_delete')], 403);
        }

        $company->delete();

        return response()->json(['message' => __('responses.deleted_successfully')]);
    }
}
