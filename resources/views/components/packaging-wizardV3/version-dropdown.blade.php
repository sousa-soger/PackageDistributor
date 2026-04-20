{{-- === GLOBAL FLOATING DROPDOWN (lives once, outside x-for) === --}}
{{-- Backdrop: catches outside clicks to close --}}
<div x-show="floatDd.open" @click="floatDd.open = false" style=" position:fixed;inset:0;z-index:99998" x-cloak></div>

{{-- Panel: anchored via JS-computed fixed coords --}}
<div x-show="floatDd.open" :style="floatDd.style" @scroll.window="floatDd.open = false" x-transition:enter="transition ease-out duration-100"
    x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
    x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-95"
    class="rounded-xl border border-slate-200 bg-white shadow-xl overflow-hidden" style="z-index:99999" x-cloak>
    <div class="p-3 border-b border-slate-100 bg-blue-50 space-y-2" @wheel.prevent="$refs.versionList.scrollTop += $event.deltaY">
        <!-- Search Input -->
        <div class="relative">
            <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input type="text" x-model="floatDd.searchQuery" x-ref="searchBar"
                class="w-full rounded-lg border border-slate-200 bg-white pl-9 pr-3 py-2 text-sm text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 placeholder-slate-400"
                placeholder="Search versions...">
        </div>

        <select x-model="floatDd.typeFilter"
            class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
            <option value="">All Types</option>
            <option value="branch">Branches</option>
            <option value="tag">Tags</option>
            <option value="release">Releases</option>
        </select>
    </div>
    <div x-ref="versionList" class="max-h-60 overflow-y-auto py-1">
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
