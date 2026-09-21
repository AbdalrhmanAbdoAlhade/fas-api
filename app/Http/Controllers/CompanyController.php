<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CompanyController extends Controller
{
    /**
     * helper: توحيد شكل الحقل المترجم
     */
    private function normalizeTranslation($value): array
    {
        if (is_array($value)) {
            return array_filter([
                'ar' => $value['ar'] ?? null,
                'en' => $value['en'] ?? null,
            ]);
        }

        return [app()->getLocale() => $value];
    }

    /* ============================================================
     |  INDEX
     * ============================================================ */
    public function index(Request $request)
    {
        $query = Company::visibleTo(Auth::user())
            ->with('user')
            ->latest();

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $companies = $query->get();

        return response()->json($companies);
    }

    /* ============================================================
     |  SHOW
     * ============================================================ */
    public function show($id)
    {
        $company = Company::visibleTo(Auth::user())
            ->with('user')
            ->find($id);

        if (!$company) {
            return response()->json(['message' => __('responses.company_not_found')], 404);
        }

        return response()->json($company);
    }

    /* ============================================================
     |  STORE
     * ============================================================ */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id'     => 'required|exists:users,id',
            'name'        => 'required',      // string OR array
            'address'     => 'required|string|max:255',
            'description' => 'nullable',      // string OR array
            'website'     => 'nullable|url',
            'logo'        => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',

            // ✅ الحقول الجديدة (national_id صورة/PDF)
            'national_id'         => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'phone'               => 'nullable|string|max:30',
            'ownership_deed'      => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'commercial_register' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'tax_certificate'     => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $company = new Company();

        $company->setTranslations('name', $this->normalizeTranslation($request->input('name')));

        if ($request->filled('description')) {
            $company->setTranslations('description', $this->normalizeTranslation($request->input('description')));
        }

        $company->user_id = $request->user_id;
        $company->address = $request->address;
        $company->website = $request->website;
        $company->status  = 'pending';   // ← الحالة الابتدائية

        // ✅ phone (نص عادي)
        if ($request->filled('phone')) {
            $company->phone = $request->input('phone');
        }

        // ✅ رفع الوثائق (national_id + الباقي)
        $docFields = ['national_id', 'ownership_deed', 'commercial_register', 'tax_certificate'];
        foreach ($docFields as $field) {
            if ($request->hasFile($field)) {
                $path = $request->file($field)->store('companies/documents', 'public');
                $company->$field = '/storage/' . $path;
            }
        }

        // اللوجو
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('companies', 'public');
            $company->logo = url('public/storage/' . $path);
        }

        $company->save();

        return response()->json([
            'message' => __('responses.company_created'),
            'company' => $company,
        ], 201);
    }

    /* ============================================================
     |  UPDATE
     * ============================================================ */
    public function update(Request $request, $id)
    {
        $company = Company::find($id);

        if (!$company) {
            return response()->json(['message' => __('responses.company_not_found')], 404);
        }

        $user = Auth::user();

        if ($user->role !== 'admin' && !($user->role === 'company_owner' && $user->id === $company->user_id)) {
            return response()->json(['message' => __('responses.unauthorized_company_update')], 403);
        }

        $validator = Validator::make($request->all(), [
            'name'        => 'sometimes',   // string OR array
            'address'     => 'sometimes|string|max:255',
            'description' => 'nullable',     // string OR array
            'website'     => 'nullable|url',
            'is_active'   => 'boolean',
            'logo'        => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',

            // ✅ الحقول الجديدة (national_id صورة/PDF)
            'national_id'         => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'phone'               => 'nullable|string|max:30',
            'ownership_deed'      => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'commercial_register' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'tax_certificate'     => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        /* -------- 1) الحقول المترجمة -------- */
        if ($request->has('name')) {
            $existing = $company->getTranslations('name');
            $incoming = $this->normalizeTranslation($request->input('name'));
            $company->setTranslations('name', array_merge($existing, $incoming));
        }

        if ($request->has('description')) {
            $existing = $company->getTranslations('description');
            $incoming = $this->normalizeTranslation($request->input('description'));
            $company->setTranslations('description', array_merge($existing, $incoming));
        }

        /* -------- 2) اللوجو -------- */
        if ($request->hasFile('logo')) {
            if ($company->logo) {
                Storage::disk('public')->delete(
                    str_replace(url('public/storage/') . '/', '', $company->logo)
                );
            }
            $path = $request->file('logo')->store('companies', 'public');
            $company->logo = url('public/storage/' . $path);
        }

        /* -------- 3) الوثائق الفردية (national_id + الباقي) -------- */
        $docFields = ['national_id', 'ownership_deed', 'commercial_register', 'tax_certificate'];
        foreach ($docFields as $field) {
            if ($request->hasFile($field)) {
                // حذف القديم
                if ($company->$field) {
                    Storage::disk('public')->delete(str_replace('/storage/', '', $company->$field));
                }
                $company->$field = '/storage/' . $request->file($field)->store('companies/documents', 'public');
            }
        }

        /* -------- 4) باقي الحقول -------- */
        if ($request->filled('address')) {
            $company->address = $request->address;
        }
        if ($request->has('website')) {
            $company->website = $request->website;
        }
        if ($request->has('is_active')) {
            $company->is_active = $request->boolean('is_active');
        }

        // ✅ phone (نص عادي)
        if ($request->filled('phone')) {
            $company->phone = $request->input('phone');
        }

        $company->save();

        return response()->json([
            'message' => __('responses.company_updated'),
            'company' => $company->fresh(),
        ]);
    }

    /* ============================================================
     |  PENDING COMPANIES (للأدمن)
     * ============================================================ */
    public function pendingCompanies(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'admin') {
            return response()->json([
                'status'  => false,
                'message' => __('responses.unauthorized'),
            ], 403);
        }

        $companies = Company::where('status', 'pending')
            ->with('user:id,name,email,phone,status,registration_role')
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'total'  => $companies->count(),
            'data'   => $companies,
        ]);
    }

    /* ============================================================
     |  UPDATE STATUS (للأدمن)
     * ============================================================ */
    public function updateStatus(Request $request, $id)
    {
        $user = Auth::user();

        if ($user->role !== 'admin') {
            return response()->json([
                'status'  => false,
                'message' => __('responses.unauthorized'),
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:approved,rejected',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $company = Company::find($id);

        if (!$company) {
            return response()->json([
                'status'  => false,
                'message' => __('responses.company_not_found'),
            ], 404);
        }

        $newStatus = $request->input('status');
        $company->update(['status' => $newStatus]);

        // ✅ تحويل role و status الـ user (لو اتقبل)
        if ($newStatus === 'approved' && $company->user) {
            if (in_array($company->user->role, ['user', 'employee'])) {
                $company->user->update([
                    'role'   => 'company_owner',
                    'status' => 'active',
                ]);
            }
        }

        return response()->json([
            'status'  => true,
            'message' => $newStatus === 'approved'
                ? 'تم قبول الشركة بنجاح.'
                : 'تم رفض الشركة.',
            'data'    => $company->fresh()->load('user:id,name,email,role,status'),
        ]);
    }

    /* ============================================================
     |  DESTROY
     * ============================================================ */
    public function destroy($id)
    {
        $company = Company::find($id);

        if (!$company) {
            return response()->json(['message' => __('responses.company_not_found')], 404);
        }

        $user = Auth::user();

        if ($user->role !== 'admin' && !($user->role === 'company_owner' && $user->id === $company->user_id)) {
            return response()->json(['message' => __('responses.unauthorized_company_delete')], 403);
        }

        // حذف اللوجو
        if ($company->logo) {
            Storage::disk('public')->delete(
                str_replace(url('public/storage/') . '/', '', $company->logo)
            );
        }

        // ✅ حذف الوثائق (national_id + الباقي)
        foreach (['national_id', 'ownership_deed', 'commercial_register', 'tax_certificate'] as $field) {
            if ($company->$field) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $company->$field));
            }
        }

        $company->delete();

        return response()->json(['message' => __('responses.deleted_successfully')]);
    }
}