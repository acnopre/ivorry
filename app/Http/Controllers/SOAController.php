<?php

namespace App\Http\Controllers;

use App\Models\Procedure;
use Illuminate\Http\Request;

class SOAController extends Controller
{
    public function generate(Request $request)
    {
        $from = $request->query('from');
        $to = $request->query('to');

        $claims = Procedure::with(['member', 'clinic'])
            ->whereBetween('availment_date', [$from, $to])
            ->where('status', 'approved')
            ->get();

        return view('soa.index', compact('claims', 'from', 'to'));
    }
}
