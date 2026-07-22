<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use App\Models\TrackingLink;
use App\Models\Coordinator;
use Illuminate\Http\Request;

class TrackingLinkController extends Controller
{
    
    public function trackingupdate($id, Request $request)
{
    $trackingLink = TrackingLink::find($id);

    if (!$trackingLink) {
        Log::error('رابط التتبع غير موجود', ['id' => $id]);
        return response()->json(['message' => __('responses.tracking_link_not_found')], 404);
    }

    // تحديث عدد الزيارات إذا كانت موجودة في الطلب
    if ($request->has('visits')) {
        $visits = $request->input('visits', 0);
        $trackingLink->increment('visits', $visits);
        Log::info('تم تحديث عدد الزيارات', ['tracking_link_id' => $id, 'visits' => $trackingLink->visits]);
    }

    // تحديث عدد عمليات الشراء إذا كانت موجودة في الطلب
    if ($request->has('purchases_count')) {
        $purchasesCount = $request->input('purchases_count', 0);
        $trackingLink->increment('purchases_count', $purchasesCount);
        Log::info('تم تحديث عدد عمليات الشراء', ['tracking_link_id' => $id, 'purchases_count' => $trackingLink->purchases_count]);
    }

    // تحديث معلومات التبرعات إذا كانت موجودة في الطلب
    if ($request->has('donation_id')) {
        $donationId = $request->input('donation_id');
        $donationAmount = $request->input('donation_amount', 0);

        $trackingLink->increment('donations');
        $trackingLink->update([
            'last_donation_id' => $donationId,
            'last_donation_amount' => $donationAmount,
        ]);

        Log::info('تم تحديث تفاصيل التبرع', [
            'tracking_link_id' => $id,
            'donation_id' => $donationId,
            'donation_amount' => $donationAmount,
            'donations' => $trackingLink->donations,
        ]);
    }

    return response()->json([
        'message' => __('responses.updated_successfully'),
        'data' => $trackingLink,
    ], 200);
}

    
    
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'coordinator_id' => 'required|exists:coordinators,id',
            'custom_keyword' => 'nullable|string|max:255',
            'added_date' => 'required|date',
            'base_url' => 'required|url',
        ]);

        $coordinator = Coordinator::findOrFail($validatedData['coordinator_id']);


        $baseUrl = rtrim($validatedData['base_url'], '/');


        $trackingUrl = "{$baseUrl}?coordinator_id={$coordinator->id}&custom_keyword=" . urlencode($validatedData['custom_keyword'] ?? '');


        $trackingLink = TrackingLink::create([
            'title' => $validatedData['title'],
            'custom_keyword' => $validatedData['custom_keyword'] ?? '',
            'added_date' => $validatedData['added_date'],
            'url' => $trackingUrl,
            'coordinator_id' => $coordinator->id,
        ]);


        $trackingLinkUrl =     $request->base_url . "/api/tracking/{$trackingLink->id}";

        return response()->json(['tracking_link' => $trackingLinkUrl]);
    }


    public function track($id, Request $request)
    {
        $trackingLink = TrackingLink::find($id);

        if (!$trackingLink) {

            return response()->json(['message' => __('responses.tracking_link_not_found')], 404);
        }


        $trackingLink->increment('visits');


        // إذا كان هناك عملية شراء
        if ($request->input('purchase') === 'true') {
            $trackingLink->increment('purchases_count');
        }


        $redirectUrl = $trackingLink->url;

        if (!filter_var($redirectUrl, FILTER_VALIDATE_URL)) {

            return response()->json(['message' => __('responses.invalid_url')], 400);
        }

        return redirect()->to($redirectUrl);
    }


    public function tracking($id, Request $request)
    {
        $trackingLink = TrackingLink::find($id);

        if (!$trackingLink) {

            return response()->json(['message' => __('responses.tracking_link_not_found')], 404);
        }

        // تحديث عدد الزيارات إذا كانت موجودة في الطلب
        if ($request->has('visits')) {
            $visits = $request->input('visits', 0);
            $trackingLink->increment('visits', $visits);
        }

        // تحديث عدد عمليات الشراء إذا كانت موجودة في الطلب
        if ($request->has('purchases_count')) {
            $purchasesCount = $request->input('purchases_count', 0);
            $trackingLink->increment('purchases_count', $purchasesCount);
        }


        return response()->json([
            'message' => __('responses.updated_successfully'),
            'data' => $trackingLink,
        ], 200);
    }

    public function show($id)
    {
        $trackingLink = TrackingLink::find($id);

        if (!$trackingLink) {
            return response()->json(['message' => __('responses.tracking_link_not_found')], 404);
        }
        return response()->json([
            'id' => $trackingLink->id,
            'title' => $trackingLink->title,
            'custom_keyword' => $trackingLink->custom_keyword,
            'added_date' => $trackingLink->added_date,
            'visits' => $trackingLink->visits,
            'new_donors' => $trackingLink->new_donors,
            'donation_transactions' => $trackingLink->donation_transactions,
            'donations' => $trackingLink->donations,
            'url' => $trackingLink->url,
            'purchases_count' => $trackingLink->purchases_count,
            'is_archived' => $trackingLink->is_archived,
            'created_at' => $trackingLink->created_at->toDateTimeString(),
            'updated_at' => $trackingLink->updated_at->toDateTimeString(),
            'coordinator_id' => $trackingLink->coordinator_id,
        ], 200);
    }

    public function index()
    {
        $trackingLinks = TrackingLink::with('coordinator')->get();

        $trackingLinks->transform(function ($link) {
            $link->url = url("api/tracking/{$link->id}");
            return $link;
        });



        return response()->json($trackingLinks, 200);
    }

    public function archive($id)
    {
        $trackingLink = TrackingLink::findOrFail($id);
        $trackingLink->is_archived = true;
        $trackingLink->save();

        Log::info('تم أرشفة الرابط بنجاح', ['tracking_link_id' => $id]);

        return response()->json(['message' => __('responses.tracking_link_archived')]);
    }

    public function unarchive($id)
    {
        $trackingLink = TrackingLink::findOrFail($id);
        $trackingLink->is_archived = false;
        $trackingLink->save();

        return response()->json(['message' => __('responses.tracking_link_unarchived')]);
    }
    
    
    
    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'coordinator_id' => 'required|exists:coordinators,id',
            'custom_keyword' => 'nullable|string|max:255',
            'added_date' => 'required|date',
            'base_url' => 'required|url',
        ]);


        $trackingLink = TrackingLink::findOrFail($id);

        $coordinator = Coordinator::findOrFail($validatedData['coordinator_id']);


        $baseUrl = rtrim($validatedData['base_url'], '/');


        $trackingUrl = "{$baseUrl}?coordinator_id={$coordinator->id}&custom_keyword=" . urlencode($validatedData['custom_keyword'] ?? '');

        // Update tracking link
        $trackingLink->update([
            'title' => $validatedData['title'],
            'custom_keyword' => $validatedData['custom_keyword'] ?? '',
            'added_date' => $validatedData['added_date'],
            'url' => $trackingUrl,
            'coordinator_id' => $coordinator->id,
        ]);

        $trackingLinkUrl = $request->base_url . "/api/tracking/{$trackingLink->id}";

        return response()->json([
            'message' => __('responses.tracking_link_updated'),
            'tracking_link' => $trackingLinkUrl,
        ]);
    }
    public function destroy($id)
    {
        $trackingLink = TrackingLink::findOrFail($id);

        $trackingLink->delete();

        return response()->json([
            'message' => __('responses.deleted_successfully')
        ], 200);
    }
}
