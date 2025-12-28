<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravolt\Indonesia\Models\Province;
use Laravolt\Indonesia\Models\Regency;
use Laravolt\Indonesia\Models\District;
use Laravolt\Indonesia\Models\Village;

class RegionController extends Controller
{
    public function regencies(Request $request)
    {
        $request->validate(['province_id' => 'required|string']);
        return Regency::where('province_id', $request->province_id)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    public function districts(Request $request)
    {
        $request->validate(['regency_id' => 'required|string']);
        return District::where('regency_id', $request->regency_id)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    public function villages(Request $request)
    {
        $request->validate(['district_id' => 'required|string']);
        return Village::where('district_id', $request->district_id)
            ->select('id', 'name', 'postal_code')
            ->orderBy('name')
            ->get();
    }
}
