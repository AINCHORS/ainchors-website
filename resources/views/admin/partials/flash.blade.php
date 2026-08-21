@if (session('status') || session('success') || session('error'))
    @php
        $message = session('success') ?? session('status') ?? session('error');
        $isError = (bool) session('error');
    @endphp
    <div role="{{ $isError ? 'alert' : 'status' }}" class="mb-6 flex items-start gap-3 rounded-ainchors-card border px-4 py-3 font-sans text-sm {{ $isError ? 'border-red-200 bg-red-50 text-red-800' : 'border-ainchors-green/30 bg-ainchors-green-hero text-ainchors-navy' }}">
        <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $isError ? 'M12 9v4m0 4h.01M4.9 19h14.2c1.4 0 2.3-1.5 1.6-2.7L13.6 4c-.7-1.2-2.5-1.2-3.2 0L3.3 16.3C2.6 17.5 3.5 19 4.9 19Z' : 'm5 12 4 4L19 6' }}"/></svg>
        <p>{{ $message }}</p>
    </div>
@endif
