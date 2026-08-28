@extends('layouts.admin')

@section('title', 'Edit '.$product->name.' | AINCHORS Admin')

@section('content')
    <div class="mx-auto max-w-4xl">
        <a href="{{ route('admin.products.show', $product) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-ainchors-green transition hover:text-ainchors-navy focus:outline-none focus:ring-2 focus:ring-ainchors-green"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7"/></svg>Product management</a>
        <div class="mt-5 rounded-ainchors-card border border-ainchors-navy/10 bg-white p-6 shadow-sm sm:p-8">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"><div><p class="text-xs font-bold uppercase tracking-[0.16em] text-ainchors-green">Catalogue management</p><h1 class="mt-2 font-heading text-3xl font-bold text-ainchors-navy">Edit product</h1><p class="mt-2 text-sm leading-relaxed text-ainchors-grey-dark">Update catalogue information and pricing. Content, package courses and status are managed from their dedicated areas.</p></div>@include('admin.partials.status-badge', ['status' => $product->status])</div>
            <form method="POST" action="{{ route('admin.products.update', $product) }}" class="mt-7 space-y-7">
                @csrf
                @method('PUT')
                @include('admin.products.partials.fields', ['product' => $product])
                <div class="flex flex-col-reverse gap-3 border-t border-ainchors-navy/10 pt-6 sm:flex-row sm:justify-end"><a href="{{ route('admin.products.show', $product) }}" class="inline-flex w-full items-center justify-center rounded-ainchors-button border border-ainchors-navy/15 px-4 py-2.5 text-sm font-semibold text-ainchors-grey-dark transition hover:border-ainchors-green hover:text-ainchors-green focus:outline-none focus:ring-2 focus:ring-ainchors-green sm:w-auto">Cancel</a><button type="submit" class="w-full rounded-ainchors-button bg-ainchors-green px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-ainchors-navy focus:outline-none focus:ring-2 focus:ring-ainchors-green focus:ring-offset-2 sm:w-auto">Save changes</button></div>
            </form>
        </div>
    </div>
@endsection
