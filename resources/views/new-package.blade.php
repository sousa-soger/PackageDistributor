@extends('layouts.app')

@section('title', 'New Package')

@section('content')
    <div
        class="max-w-6xl mx-auto space-y-8 pt-4"
        x-data="{
            currentStep: 1,
            selectedRepository: '',
            selectedVersion: '',
            versionSearch: '',
            versionTypeFilter: '',
            allVersions: @js($versions)
        }"
    >
        <div class="flex items-start justify-between">
            <div>
                <h1 class="text-3xl font-bold text-slate-900">Create Distribution Package</h1>
            </div>

            <div class="text-sm text-slate-400 font-medium">
                <span x-text="`Step ${currentStep} of 5`"></span>
            </div>
        </div>

    <!-- Step Progress -->
    <div class="flex items-center">
        <div x-show="currentStep === 1" >
            <x-ui.step-item number="1" label="Project Selection" :active="true" />
        </div>
        <button x-show="currentStep > 1" @click="currentStep = 1">
            <x-ui.step-item number="✓" label="Project Selection" :completed="true" />
        </button>

        <div x-show="currentStep < 2" >
            <x-ui.step-item number="2" label="Project Selection"  />
        </div>
        <div x-show="currentStep === 2" >
            <x-ui.step-item number="2" label="Project Selection"  :active="true"/>
        </div>
        <div x-show="currentStep > 2">
            <x-ui.step-item number="✓" label="Project Selection" :completed="true" />
        </div>
        
        <div x-show="currentStep < 3" >
            <x-ui.step-item number="3" label="Packaging Options"  />
        </div>
        <div x-show="currentStep === 3" >
            <x-ui.step-item number="3" label="Packaging Options" :active="true" />
        </div>
        <div x-show="currentStep > 3">
            <x-ui.step-item number="✓" label="Packaging Options" :completed="true" />
        </div>

        <div x-show="currentStep < 4" >
            <x-ui.step-item number="4" label="Packaging" />
        </div>
        <div x-show="currentStep === 4" >
            <x-ui.step-item number="4" label="Packaging" :active="true" />
        </div>
        <div x-show="currentStep > 4">
            <x-ui.step-item number="✓" label="Packaging" :completed="true" />
        </div>

        <div x-show="currentStep < 5" >
            <x-ui.step-item number="5" label="Download" :last="true"/>
        </div>
        <div x-show="currentStep === 5" >
            <x-ui.step-item number="5" label="Download" :active="true" :last="true"/>
        </div>
        <div x-show="currentStep > 5">
            <x-ui.step-item number="✓" label="Download" :completed="true" :last="true" />
        </div> 

    </div>
        
        <div x-show="currentStep === 1" x-cloak>
            @include('components.step-card-new-package.1')
        </div>

        <!-- Step 2 -->
        <div x-show="currentStep === 2" x-cloak>
            @include('components.step-card-new-package.2')
        </div>

        <!-- Step 3 -->
        <div x-show="currentStep === 3" x-cloak>
            @include('components.step-card-new-package.3')
        </div>
        
        <!-- Step 4 -->
        <div x-show="currentStep === 4" x-cloak>
            @include('components.step-card-new-package.4')
        </div>

        <!-- Step 5 -->
        <div x-show="currentStep === 5" x-cloak>
            @include('components.step-card-new-package.5')
        </div>
    </div>
    
@endsection

@push('scripts')
<script>
    const progressBar = document.getElementById('progressBar');
    const progressPercent = document.getElementById('progressPercent');
    const progressLabel = document.getElementById('progressLabel');
    const logBox = document.getElementById('logBox');
    const nextBtn = document.getElementById('nextBtn');

    function setProgress(percent, label) {
        progressBar.style.width = percent + '%';
        progressPercent.textContent = percent + '%';
        if (label) progressLabel.textContent = label;
    }

    function addLog(text) {
        const line = document.createElement('div');
        line.textContent = text;
        logBox.appendChild(line);
    }

    async function startPackaging() {
        try {
            // Step 1
            addLog('Initializing process... (Done)');
            setProgress(10);

            await delay(800);

            // Step 2
            addLog('Pulling source code... (Done)');
            setProgress(30);

            await delay(1000);

            // Step 3
            addLog('Optimizing assets (CSS/JS)... (65% complete)');
            setProgress(65);

            await delay(1200);

            // Step 4
            addLog('Compressing files... (Done)');
            setProgress(90);

            await delay(800);

            // Final request (simulate backend packaging)
            const response = await axios.get('/dummy-package');

            addLog('Finalizing package... (Done)');
            setProgress(100, 'Completed');

            nextBtn.disabled = false;
            nextBtn.classList.remove('opacity-50');
            nextBtn.textContent = 'Continue';

        } catch (error) {
            addLog('Error occurred during packaging.');
            console.error(error);
        }
    }

    function delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    // auto start when page loads
    document.addEventListener('DOMContentLoaded', startPackaging);
</script>
@endpush