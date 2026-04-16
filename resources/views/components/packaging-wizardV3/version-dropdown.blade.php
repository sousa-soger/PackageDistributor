{{-- === GLOBAL FLOATING DROPDOWN (lives once, outside x-for) === --}}
{{-- Backdrop: catches outside clicks to close --}}
<div x-show="floatDd.open" @click="floatDd.open = false" style="position:fixed;inset:0;z-index:99998" x-cloak></div>

{{-- Panel: anchored via JS-computed fixed coords --}}
<div x-show="floatDd.open" :style="floatDd.style" x-transition:enter="transition ease-out duration-100"
    x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
    x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-95"
    class="rounded-xl border border-slate-200 bg-white shadow-xl overflow-hidden" style="z-index:99999" x-cloak>
    <div class="p-3 border-b border-slate-100 bg-slate-50">
        <select x-model="floatDd.typeFilter"
            class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
            <option value="">All Types</option>
            <option value="branch">Branches</option>
            <option value="tag">Tags</option>
            <option value="release">Releases</option>
        </select>
    </div>
    <div class="max-h-60 overflow-y-auto py-1">
        <template x-for="v in floatDdVersions" :key="v.unique_key">
            <button type="button" @click="selectFloatVersion(v.unique_key)"
                class="w-full px-4 py-2.5 text-left text-sm hover:bg-blue-50 flex items-center justify-between transition"
                :class="floatDdCurrentValue === v.unique_key ? 'bg-blue-50/70 font-medium text-blue-700' : 'text-slate-700'">
                <span class="truncate" x-text="v.name"></span>
                <span class="ml-2 text-[10px] font-semibold text-slate-400 uppercase tracking-wider"
                    x-text="v.type"></span>
            </button>
        </template>
        <div x-show="floatDdVersions.length === 0" class="px-4 py-4 text-center text-sm text-slate-500">
            No versions found
        </div>
    </div>
</div>
