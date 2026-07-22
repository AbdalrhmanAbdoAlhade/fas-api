<?php

namespace App\Http\Controllers;

use App\Models\ServiceRequest;
use App\Models\Service;
use App\Models\Hotel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class ServiceController extends Controller
{
    /**
     * Display a listing of the services.
     */
    public function index()
    {
        $services = Service::all();
        return response()->json(['services' => $services], 200);
    }

    /**
     * Store a newly created service in storage.
     */
public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
        'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        'status' => 'nullable|string',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $data = $request->only(['name', 'status', 'room_number', 'national_id']);
    
    // Handle image upload
    if ($request->hasFile('image')) {
        $image = $request->file('image');
        $imageName = time().'.'.$image->extension();
        $imagePath = $image->storeAs('services/images', $imageName, 'public');
        $data['image'] = '/storage/'.$imagePath;
    } else {
        return response()->json(['error' => __('responses.image_required')], 400);
    }

    $service = Service::create($data);
    
    return response()->json([
        'service' => $service,
        'message' => __('responses.service_created')
    ], 201);
}
    /**
     * Display the specified service.
     */
    public function show($id)
    {
        $service = Service::find($id);

        if (!$service) {
            return response()->json(['message' => __('responses.service_not_found')], 404);
        }

        return response()->json(['service' => $service], 200);
    }

    /**
     * Update the specified service in storage.
     */
public function update(Request $request, $id)
{
    $service = Service::find($id);

    if (!$service) {
        return response()->json(['message' => __('responses.service_not_found')], 404);
    }

    $validator = Validator::make($request->all(), [
        'name' => 'sometimes|string|max:255',
        'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        'status' => 'sometimes|string',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $data = $request->only(['name', 'status']);

    // ✅ تحديث الصورة إذا تم رفع واحدة جديدة
    if ($request->hasFile('image')) {
        // حذف الصورة القديمة من السيرفر إن وجدت
        if ($service->image && file_exists(public_path($service->image))) {
            @unlink(public_path($service->image));
        }

        // رفع الصورة الجديدة
        $image = $request->file('image');
        $imageName = time() . '.' . $image->extension();
        $imagePath = $image->storeAs('services/images', $imageName, 'public');
        $data['image'] = '/storage/' . $imagePath;
    }

    $service->update($data);

    return response()->json([
        'service' => $service,
        'message' => __('responses.service_updated'),
    ], 200);
}


    /**
     * Remove the specified service from storage.
     */
    public function destroy($id)
    {
        $service = Service::find($id);

        if (!$service) {
            return response()->json(['message' => __('responses.service_not_found')], 404);
        }

        $service->delete();
        return response()->json(['message' => __('responses.service_deleted')], 200);
    }

    
     
public function createRequest(Request $request)
{
    $validated = $request->validate([
        'hotel_id' => 'required|integer',
        'service_id' => 'required|integer|exists:services,id',
        'room_number' => 'required|string',
        'guest_name' => 'required|string',
        'request_time' => 'required|date',
        'notes' => 'nullable|string',
    ]);

    $serviceRequest = ServiceRequest::create([
        'user_id'     => auth()->id(),
        'hotel_id'    => $validated['hotel_id'],
        'service_id'  => $validated['service_id'],
        'room_number' => $validated['room_number'],
        'guest_name'  => $validated['guest_name'],
        'request_time'=> $validated['request_time'],
        'notes'       => $validated['notes'] ?? null,
    ]);

    return response()->json([
        'status' => true,
        'message' => __('responses.service_request_created'),
        'data' => $serviceRequest,
    ]);
}


    /**
     * عرض جميع طلبات الخدمات
     */
public function getAllRequests()
{
    $requests = ServiceRequest::with(['service', 'hotel', 'user'])
        ->orderBy('created_at', 'desc')
        ->get();

    return response()->json([
        'status' => true,
        'service_requests' => $requests
    ], 200);
}

    /**
     * عرض طلبات خدمة معينة
     */
    public function getServiceRequests($serviceId)
    {
        $service = Service::with('requests')->find($serviceId);

        if (!$service) {
            return response()->json(['message' => __('responses.service_not_found')], 200);
        }

        return response()->json([
            'service' => $service->name,
            'requests' => $service->requests
        ], 200);
    }

    /**
     * تحديث حالة طلب الخدمة
     */
    public function updateRequestStatus(Request $request, $requestId)
    {
        $serviceRequest = ServiceRequest::find($requestId);

        if (!$serviceRequest) {
            return response()->json(['message' => __('responses.service_request_not_found')], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:pending,processing,completed,canceled',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $serviceRequest->update(['status' => $request->status]);

        return response()->json([
            'service_request' => $serviceRequest,
            'message' => __('responses.service_request_status_updated')
        ], 200);
    }
    
    /**
 * عرض جميع طلبات الخدمات للعميل الحالي (بناءً على التوكن)
 */
public function getClientRequests()
{
    // الحصول على ID المستخدم المصادق عليه من التوكن
    $userId = auth()->id();

    // جلب الطلبات مع بيانات الفندق والخدمة
    $requests = ServiceRequest::with(['service', 'hotel'])
        ->where('user_id', $userId)
        ->orderBy('created_at', 'desc')
        ->get();

    return response()->json([
        'status' => 'success',
        'requests' => $requests
    ], 200);
}


/**
 * عرض جميع طلبات الخدمات للفندق الحالي (بناءً على التوكن)
 */
public function getHotelRequests()
{
    // ✅ جلب الفندق المرتبط بالمستخدم الحالي
    $hotel = Hotel::where('user_id', auth()->id())->first();

    if (!$hotel) {
        return response()->json([
            'status' => 'error',
            'message' => __('responses.hotel_not_linked_to_user')
        ], 200);
    }

    // ✅ جلب الطلبات المرتبطة بالفندق مع بيانات الخدمة والفندق والمستخدم
    $requests = ServiceRequest::with(['service', 'user', 'hotel'])
        ->where('hotel_id', $hotel->id)
        ->orderBy('created_at', 'desc')
        ->get();

    return response()->json([
        'status' => 'success',
        'requests' => $requests
    ], 200);
}


    /**
     * حذف طلب خدمة
     */
    public function deleteRequest($requestId)
    {
        $request = ServiceRequest::find($requestId);

        if (!$request) {
            return response()->json(['message' => __('responses.service_request_not_found')], 404);
        }

        $request->delete();
        return response()->json(['message' => __('responses.service_request_deleted')], 200);
    }
}
