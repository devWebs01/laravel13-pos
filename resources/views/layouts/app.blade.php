<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main>
        {{ $slot }}
    </flux:main>
    <div class="border-t border-zinc-200 px-6 py-3 text-center text-xs text-zinc-400 dark:border-zinc-700 dark:text-zinc-500">
        Laravel v{{ app()->version() }}
    </div>
</x-layouts::app.sidebar>
