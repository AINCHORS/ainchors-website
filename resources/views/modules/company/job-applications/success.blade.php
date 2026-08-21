@extends('layouts.app')

@section('title', 'Application Received | AINCHORS')

@section('content')
    <section class="relative grid min-h-[34rem] place-items-center overflow-hidden bg-[linear-gradient(135deg,#e8fff7_0%,#ffffff_52%,#c1eff5_100%)] px-5 py-16 sm:px-6">
        <div aria-hidden="true" class="absolute -left-24 top-16 h-72 w-72 rounded-full bg-ainchors-card-green/60 blur-3xl"></div>
        <div aria-hidden="true" class="absolute -right-20 bottom-0 h-80 w-80 rounded-full bg-ainchors-card-blue/60 blur-3xl"></div>
        <p class="relative max-w-2xl text-center font-sans text-3xl font-semibold leading-snug tracking-[-0.02em] text-ainchors-navy sm:text-4xl">Thank you for applying to AINCHORS! We have received your application and our team will review it shortly.</p>
    </section>
@endsection
