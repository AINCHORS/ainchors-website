@php($items = is_array($items) && count($items) ? array_values($items) : [''])

<div data-repeatable-list data-next-index="{{ count($items) }}">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm font-semibold text-ainchors-navy">{{ $listLabel }}</p>
        <button type="button" data-repeatable-add class="rounded-ainchors-button border border-ainchors-green px-3 py-1.5 text-sm font-semibold text-ainchors-green transition hover:bg-ainchors-green hover:text-white focus:outline-none focus:ring-2 focus:ring-ainchors-green">+ Add {{ $itemLabel }}</button>
    </div>
    <div class="mt-3 space-y-2" data-repeatable-rows>
        @foreach ($items as $index => $item)
            <div class="flex items-center gap-2" data-repeatable-row>
                <label class="sr-only" for="lesson-{{ $sectionKey }}-{{ $listKey }}-{{ $index }}">{{ $itemLabel }} {{ $index + 1 }}</label>
                <input id="lesson-{{ $sectionKey }}-{{ $listKey }}-{{ $index }}" name="lesson_content[{{ $sectionKey }}][{{ $listKey }}][{{ $index }}]" type="text" maxlength="1000" value="{{ $item }}" class="block min-w-0 flex-1 rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2.5 text-sm text-ainchors-navy shadow-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">
                <button type="button" data-repeatable-remove class="rounded-ainchors-button border border-red-200 px-3 py-2.5 text-sm font-semibold text-red-700 transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-300">Remove</button>
            </div>
        @endforeach
    </div>
    <template>
        <div class="flex items-center gap-2" data-repeatable-row>
            <label class="sr-only" for="lesson-{{ $sectionKey }}-{{ $listKey }}-__INDEX__">{{ $itemLabel }}</label>
            <input id="lesson-{{ $sectionKey }}-{{ $listKey }}-__INDEX__" name="lesson_content[{{ $sectionKey }}][{{ $listKey }}][__INDEX__]" type="text" maxlength="1000" class="block min-w-0 flex-1 rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2.5 text-sm text-ainchors-navy shadow-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">
            <button type="button" data-repeatable-remove class="rounded-ainchors-button border border-red-200 px-3 py-2.5 text-sm font-semibold text-red-700 transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-300">Remove</button>
        </div>
    </template>
    @error('lesson_content.'.$sectionKey.'.'.$listKey.'.*')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
</div>
