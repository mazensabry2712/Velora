@php
    $specializations = \App\Models\User::role('Staff')
        ->whereNotNull('specialization')
        ->where('specialization', '!=', '')
        ->select('specialization')
        ->distinct()
        ->get();
@endphp
