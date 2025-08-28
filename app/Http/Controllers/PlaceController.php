<?php

namespace App\Http\Controllers;

use App\Models\Place;

class PlaceController extends Controller
{
    /**
     * GET /places
     * إرجاع جميع الأماكن لخيارات الفلترة في التغريدات
     */
    public function index()
    {
        // رجّعها مرتبة بالاسم لواجهة المستخدم
        $places = Place::orderBy('location_name')->get(['id', 'location_name']);

        return response()->json($places);
    }
}
