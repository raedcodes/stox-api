<x-filament-panels::page>
    <div class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        {{-- Block-level stacking (not flex-col) so vertical layout never
             depends on that specific utility class making it into the
             compiled CSS bundle — each message row is still an inline flex
             for its own left/right alignment. --}}
        <div class="space-y-4">
            @forelse ($conversation as $message)
                <div class="flex {{ $message['role'] === 'user' ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-[85%] whitespace-pre-line rounded-xl px-4 py-2.5 text-sm leading-relaxed
                        {{ $message['role'] === 'user'
                            ? 'bg-indigo-500 text-white'
                            : 'border-s-4 border-indigo-400 bg-indigo-50/60 text-gray-800 dark:border-indigo-500 dark:bg-indigo-500/10 dark:text-gray-200' }}">
                        {{ $message['content'] }}
                    </div>
                </div>
            @empty
                <div class="flex flex-col items-center gap-3 py-10 text-center">
                    <x-filament::icon icon="heroicon-o-sparkles" class="h-8 w-8 text-gray-300 dark:text-gray-600" />
                    <p class="max-w-sm text-sm text-gray-500 dark:text-gray-400">
                        Ask about sales, profit, stock, or a customer's balance — or try one of these:
                    </p>
                    <div class="flex flex-wrap items-center justify-center gap-2">
                        @foreach ($this->suggestedQuestions() as $key => $label)
                            <button
                                type="button"
                                wire:click="askSuggestion('{{ $key }}')"
                                wire:loading.attr="disabled"
                                wire:target="ask,askSuggestion"
                                class="rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-medium text-indigo-700 transition hover:bg-indigo-100 disabled:opacity-50 dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-300 dark:hover:bg-indigo-500/20"
                            >
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </div>
            @endforelse

            <div wire:loading wire:target="ask,askSuggestion" class="flex justify-start">
                <div class="flex items-center gap-2 rounded-xl border-s-4 border-indigo-300 bg-indigo-50/40 px-4 py-2.5 text-sm text-gray-500 dark:border-indigo-500/50 dark:bg-indigo-500/5 dark:text-gray-400">
                    <x-filament::icon icon="heroicon-o-arrow-path" class="h-4 w-4 flex-shrink-0 animate-spin text-indigo-500" />
                    Thinking&hellip;
                </div>
            </div>
        </div>
    </div>

    <form wire:submit.prevent="ask" class="fi-section flex items-stretch gap-3 rounded-xl bg-white p-3 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <textarea
            wire:model="question"
            rows="1"
            placeholder="Ask a question about your business…"
            class="fi-input block w-full resize-none rounded-lg border-none bg-gray-50 px-3 py-2.5 text-sm text-gray-950 ring-1 ring-gray-200 placeholder:text-gray-400 focus:ring-2 focus:ring-indigo-400 dark:bg-white/5 dark:text-white dark:ring-white/10 dark:placeholder:text-gray-500"
            @keydown.enter.prevent="if (!$event.shiftKey) $wire.ask()"
        ></textarea>

        <x-filament::button
            type="submit"
            icon="heroicon-o-paper-airplane"
            wire:loading.attr="disabled"
            wire:target="ask,askSuggestion"
            class="flex-shrink-0"
        >
            Send
        </x-filament::button>
    </form>
</x-filament-panels::page>
