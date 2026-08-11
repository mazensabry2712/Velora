@extends('layouts.admin')

@section('title', __('Reports'))
@section('subtitle', __('View business insights and appointment statistics'))

@section('header-actions')
    @include('admin.reports.partials.filters')
@endsection

@section('content')

    @include('admin.reports.partials.stats-cards')

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @include('admin.reports.partials.appointments-by-status')
        @include('admin.reports.partials.queue-stats')
        @include('admin.reports.partials.staff-performance')
        @include('admin.reports.partials.service-types')
    </div>

@endsection
