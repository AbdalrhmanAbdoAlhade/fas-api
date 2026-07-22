<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\PropertyType;
use Illuminate\Support\Facades\Storage;

class PropertyTypeController extends Controller
{
    // عرض الكل
    public function index()
    {
        return response()->json(PropertyType::all(), 200);
    }

    // إنشاء جديد
public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string',
        'image' => 'nullable|image|max:2048',
    ]);

    $path = null;
    if ($request->hasFile('image')) {
        $stored = $request->file('image')->store('property_types', 'public');
        $path = ('/storage/' . $stored); // رابط كامل
    }

    $type = PropertyType::create([
        'name' => $request->name,
        'image' => $path,
    ]);

    return response()->json([
        'message' => __('responses.property_type_created'),
        'data' => $type
    ], 201);
}


    // عرض عنصر واحد
    public function show($id)
    {
        $type = PropertyType::findOrFail($id);
        return response()->json($type);
    }

    // تحديث
public function update(Request $request, $id)
{
    $type = PropertyType::findOrFail($id);

    $request->validate([
        'name' => 'sometimes|string',
        'image' => 'nullable|image|max:2048',
    ]);

    if ($request->has('name')) {
        $type->name = $request->name;
    }

    if ($request->hasFile('image')) {
        // حذف الصورة القديمة
        if ($type->image) {
            $relativePath = str_replace('/storage/', '', $type->image);
            Storage::disk('public')->delete($relativePath);
        }

        $stored = $request->file('image')->store('property_types', 'public');
        $type->image = '/storage/' . $stored;
    }

    $type->save();

    return response()->json(['message' => __('responses.property_type_updated'), 'data' => $type]);
}


    // حذف
  public function destroy($id)
{
    $type = PropertyType::findOrFail($id);

    if ($type->image) {
        $relativePath = str_replace('/storage/', '', $type->image);
        Storage::disk('public')->delete($relativePath);
    }

    $type->delete();

    return response()->json(['message' => __('responses.property_type_deleted')]);
}

    
}
