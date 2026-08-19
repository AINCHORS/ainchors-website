<div
    x-data="{
        open: false,
        draft: '',
        unavailable: false,
        messages: [
            { role: 'assistant', text: 'Hi 👋 How can I help you today?' },
        ],
        submit() {
            const message = this.draft.trim();
            if (!message) return;
            this.messages.push({ role: 'user', text: message });
            this.draft = '';
            this.unavailable = true;
            this.$nextTick(() => { this.$refs.messages.scrollTop = this.$refs.messages.scrollHeight; });
        },
        close() {
            this.open = false;
            this.$nextTick(() => this.$refs.toggle.focus());
        },
    }"
    @keydown.escape.window="if (open) close()"
>
    <section
        id="ai-assistant-panel"
        x-cloak
        x-show="open"
        x-transition.origin.bottom.right
        role="dialog"
        aria-modal="false"
        aria-labelledby="ai-assistant-title"
        class="fixed bottom-[88px] left-4 right-4 z-50 flex h-[500px] max-h-[calc(100vh-110px)] flex-col overflow-hidden rounded-ainchors-card border border-ainchors-grey-light/25 bg-ainchors-white shadow-2xl sm:left-auto sm:right-6 sm:bottom-[92px] sm:h-[500px] sm:w-[360px] sm:max-h-[calc(100vh-130px)]"
    >
        <header class="flex items-start justify-between gap-3 border-b border-ainchors-grey-light/20 bg-ainchors-green-hero px-4 py-3">
            <div>
                <h2 id="ai-assistant-title" class="font-sans text-base font-bold text-ainchors-navy">AINCHORS AI Assistant</h2>
                <p class="mt-0.5 font-sans text-xs text-ainchors-grey-dark">Chat service not connected</p>
            </div>
            <button type="button" @click="close()" aria-label="Close AI Assistant" class="rounded p-1 text-ainchors-navy transition hover:text-ainchors-green focus:outline-none focus:ring-2 focus:ring-ainchors-green">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="m6 6 12 12M18 6 6 18"/></svg>
            </button>
        </header>

        <div x-ref="messages" class="min-h-0 flex-1 space-y-3 overflow-y-auto bg-slate-50 px-4 py-4" aria-live="polite">
            <template x-for="(message, index) in messages" :key="index">
                <div class="flex" :class="message.role === 'user' ? 'justify-end' : 'justify-start'">
                    <p class="max-w-[85%] rounded-2xl px-3 py-2 font-sans text-sm leading-relaxed" :class="message.role === 'user' ? 'bg-ainchors-green text-ainchors-white' : 'bg-ainchors-white text-ainchors-grey-dark shadow-sm ring-1 ring-ainchors-grey-light/15'" x-text="message.text"></p>
                </div>
            </template>
            <p x-cloak x-show="unavailable" class="rounded-lg border border-ainchors-grey-light/25 bg-ainchors-white px-3 py-2 font-sans text-xs leading-relaxed text-ainchors-grey-dark">AI responses are not connected yet. Your message has not been sent to an AI service.</p>
        </div>

        <form @submit.prevent="submit()" class="border-t border-ainchors-grey-light/20 bg-ainchors-white p-3">
            <label for="ai-assistant-message" class="sr-only">Ask AINCHORS something</label>
            <div class="flex items-end gap-2">
                <textarea
                    id="ai-assistant-message"
                    x-model="draft"
                    @keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); submit(); }"
                    rows="2"
                    placeholder="Ask AINCHORS something..."
                    class="min-h-11 flex-1 resize-none rounded-ainchors-button border border-ainchors-grey-light/35 px-3 py-2 font-sans text-sm text-ainchors-navy outline-none focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25"
                ></textarea>
                <button type="submit" aria-label="Send message" class="grid h-11 w-11 flex-none place-items-center rounded-ainchors-button bg-ainchors-green text-ainchors-white transition hover:bg-ainchors-green/90 focus:outline-none focus:ring-2 focus:ring-ainchors-green focus:ring-offset-2">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 14-7-4 14-3-6-7-1Z"/></svg>
                </button>
            </div>
        </form>
    </section>

    <button
        x-ref="toggle"
        type="button"
        @click="open = !open; if (open) $nextTick(() => document.getElementById('ai-assistant-message').focus())"
        :aria-expanded="open.toString()"
        aria-label="Open AI Assistant"
        :aria-label="open ? 'Close AI Assistant' : 'Open AI Assistant'"
        aria-controls="ai-assistant-panel"
        class="fixed bottom-6 right-6 z-[60] flex h-14 w-14 items-center justify-center rounded-full bg-ainchors-green text-ainchors-white shadow-lg transition hover:scale-105 focus:outline-none focus:ring-2 focus:ring-ainchors-green focus:ring-offset-2"
    >
        <svg x-show="!open" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.4-4 8-9 8-1.2 0-2.4-.2-3.4-.6L3 20l1.3-3.9C3.5 14.9 3 13.5 3 12c0-4.4 3.6-8 8-8s8 3.6 8 8z"/></svg>
        <svg x-cloak x-show="open" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="m6 18 12-12M6 6l12 12"/></svg>
    </button>
</div>
