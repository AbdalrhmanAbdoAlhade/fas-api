<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blocking;
use App\Models\Hotel;
use App\Models\Property;
use App\Models\Company;
use App\Models\Offer;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class BlockingController extends Controller
{
    /**
     * خريطة الأنواع للـ Models
     */
    private array $typeMap = [
        'hotels'     => Hotel::class,
        'properties' => Property::class,
        'companies'  => Company::class,
        'offers'     => Offer::class,
        'rooms'      => Room::class,
    ];

    private function resolveModel(string $type, int $id)
    {
        if (!isset($this->typeMap[$type])) {
            return null;
        }

        $modelClass = $this->typeMap[$type];
        return $modelClass::find($id);
    }

    /**
     * POST /api/admin/{type}/{id}/block
     */
    public function block(Request $request, string $type, int $id)
    {
        $user = Auth::user();

        if ($user->role !== 'admin') {
            return response()->json([
                'status'  => false,
                'message' => __('responses.unauthorized'),
            ], 403);
        }

        $item = $this->resolveModel($type, $id);

        if (!$item) {
            return response()->json([
                'status'  => false,
                'message' => __('responses.item_not_found'),
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'type'          => 'required|in:temporary,permanent',
            'reason'        => 'nullable|string|max:1000',
            'blocked_until' => 'required_if:type,temporary|nullable|date|after:now',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($item->isBlocked()) {
            return response()->json([
                'status'  => false,
                'message' => __('responses.item_already_blocked'),
            ], 422);
        }

        $blockType = $request->input('type');
        $reason    = $request->input('reason');
        $until     = $blockType === 'temporary'
            ? Carbon::parse($request->input('blocked_until'))
            : null;

        $blocking = $item->block($blockType, $reason, $until, $user->id);

        return response()->json([
            'status'   => true,
            'message'  => __('responses.item_blocked_successfully'),
            'blocking' => $blocking->load('blockedBy:id,name,email'),
            'item'     => $item->fresh()->load('activeBlocking.blockedBy:id,name,email'),
        ], 201);
    }

    /**
     * POST /api/admin/{type}/{id}/unblock
     */
    public function unblock(string $type, int $id)
    {
        $user = Auth::user();

        if ($user->role !== 'admin') {
            return response()->json([
                'status'  => false,
                'message' => __('responses.unauthorized'),
            ], 403);
        }

        $item = $this->resolveModel($type, $id);

        if (!$item) {
            return response()->json([
                'status'  => false,
                'message' => __('responses.item_not_found'),
            ], 404);
        }

        if (!$item->isBlocked()) {
            return response()->json([
                'status'  => false,
                'message' => __('responses.item_not_blocked'),
            ], 422);
        }

        $item->unblock($user->id);

        return response()->json([
            'status'  => true,
            'message' => __('responses.item_unblocked_successfully'),
            'item'    => $item->fresh(),
        ]);
    }

    /**
     * GET /api/admin/blockings
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'admin') {
            return response()->json([
                'status'  => false,
                'message' => __('responses.unauthorized'),
            ], 403);
        }

        $query = Blocking::with([
            'blockedBy:id,name,email',
            'unblockedBy:id,name,email',
            'blockable',
        ]);

        if ($request->filled('type')) {
            $type = $request->input('type');
            if (isset($this->typeMap[$type])) {
                $query->where('blockable_type', $this->typeMap[$type]);
            }
        }

        if ($request->filled('status')) {
            switch ($request->input('status')) {
                case 'active':
                    $query->active();
                    break;
                case 'inactive':
                    $query->where('is_active', false);
                    break;
                case 'expired':
                    $query->where('is_active', true)
                          ->where('type', 'temporary')
                          ->where('blocked_until', '<=', now());
                    break;
            }
        }

        if ($request->filled('block_type')) {
            $query->where('type', $request->input('block_type'));
        }

        $blockings = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json([
            'status' => true,
            'data'   => $blockings,
        ]);
    }

    /**
     * GET /api/admin/{type}/{id}/blocking
     */
    public function show(string $type, int $id)
    {
        $user = Auth::user();

        if ($user->role !== 'admin') {
            return response()->json([
                'status'  => false,
                'message' => __('responses.unauthorized'),
            ], 403);
        }

        $item = $this->resolveModel($type, $id);

        if (!$item) {
            return response()->json([
                'status'  => false,
                'message' => __('responses.item_not_found'),
            ], 404);
        }

        return response()->json([
            'status'          => true,
            'item'            => $item,
            'is_blocked'      => $item->isBlocked(),
            'active_blocking' => $item->activeBlocking()
                                     ->with('blockedBy:id,name,email')
                                     ->first(),
            'history'         => $item->blockings()
                                     ->with(['blockedBy:id,name,email', 'unblockedBy:id,name,email'])
                                     ->get(),
        ]);
    }
}