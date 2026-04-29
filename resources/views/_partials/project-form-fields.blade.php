{{-- Shared fields for both create and edit project forms --}}
{{-- Expects Alpine context: selectedColor, editName (edit mode), editDescription (edit mode) --}}

<div class="space-y-2">
    <label for="project-name" class="text-sm font-medium leading-none">Project Name</label>
    <input
        id="project-name"
        name="name"
        type="text"
        :value="modalMode === 'edit' ? editName : '{{ old('name') }}'"
        placeholder="Atlas Web"
        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
    >
    @error('name')
        <p class="text-xs text-failed">{{ $message }}</p>
    @enderror
</div>

<div class="space-y-2">
    <label for="project-description" class="text-sm font-medium leading-none">Description</label>
    <textarea
        id="project-description"
        name="description"
        rows="3"
        placeholder="Customer-facing storefront and marketing site"
        class="flex w-full rounded-xl border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
        x-bind:value="modalMode === 'edit' ? editDescription : '{{ old('description') }}'"
    >{{ old('description') }}</textarea>
    @error('description')
        <p class="text-xs text-failed">{{ $message }}</p>
    @enderror
</div>

<div class="space-y-3">
    <label class="text-sm font-medium leading-none">Accent Gradient</label>
    <input type="hidden" name="color" :value="selectedColor">
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        @foreach ($colorOptions as $colorOption)
            <button
                type="button"
                @click="selectedColor = '{{ $colorOption }}'"
                class="rounded-2xl border p-3 text-left transition-base"
                :class="selectedColor === '{{ $colorOption }}' ? 'border-primary/50 bg-accent shadow-soft' : 'border-border hover:border-primary/30 hover:bg-secondary/40'"
            >
                <div class="h-10 rounded-xl bg-linear-to-br {{ $colorOption }}"></div>
                <div class="mt-2 text-[11px] font-medium text-muted-foreground">{{ str_replace(['from-', 'to-'], ['', '→ '], $colorOption) }}</div>
            </button>
        @endforeach
    </div>
    @error('color')
        <p class="text-xs text-failed">{{ $message }}</p>
    @enderror
</div>
