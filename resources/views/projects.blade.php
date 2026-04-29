@extends('layouts.app')

@section('title', 'Projects')
@section('subtitle', 'Create and organize projects, see their repositories and the teams behind them.')

@section('topbar_actions')
    <div
        x-data="{
            showModal: @js($errors->any()),
            modalMode: 'create',
            selectedColor: @js(old('color', $colorOptions[0] ?? 'from-brand-rose to-brand-iris')),
            editId: null,
            editName: '',
            editDescription: '',
            openCreate() {
                this.modalMode = 'create';
                this.editId = null;
                this.editName = '';
                this.editDescription = '';
                this.selectedColor = @js($colorOptions[0] ?? 'from-brand-rose to-brand-iris');
                this.showModal = true;
            },
            openEdit(project) {
                this.modalMode = 'edit';
                this.editId = project.id;
                this.editName = project.name;
                this.editDescription = project.description === 'No description added yet.' ? '' : project.description;
                this.selectedColor = project.color;
                this.showModal = true;
            },
        }"
        @open-create-project.window="openCreate()"
        @open-edit-project.window="openEdit($event.detail)"
    >
        <button
            type="button"
            @click="openCreate()"
            class="inline-flex items-center justify-center gap-2 whitespace-nowrap text-sm font-medium brand-gradient-bg text-[hsl(var(--on-brand))] shadow-soft hover:brightness-[1.03] active:brightness-95 transition-base h-9 rounded-md px-3"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 12h14"/><path d="M12 5v14"/>
            </svg>
            New Project
        </button>

        {{-- Create / Edit Modal --}}
        <template x-teleport="body">
            <div x-show="showModal" x-cloak class="relative z-50">
                <div
                    x-show="showModal"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 bg-background/80 backdrop-blur-sm"
                ></div>

                <div class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-0">
                    <div
                        x-show="showModal"
                        @click.away="showModal = false"
                        @keydown.escape.window="showModal = false"
                        x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave="ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        class="w-full max-w-2xl border border-border bg-background shadow-lg sm:rounded-2xl overflow-hidden"
                        role="dialog"
                        aria-modal="true"
                    >
                        <div class="brand-soft-bg px-6 py-5 border-b border-border/60">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h2 class="text-xl font-semibold tracking-tight" x-text="modalMode === 'edit' ? 'Edit Project' : 'Create Project'"></h2>
                                    <p class="mt-1 text-sm text-muted-foreground">
                                        <span x-show="modalMode === 'create'">Create a project bucket, then link repositories to it from the repositories page.</span>
                                        <span x-show="modalMode === 'edit'">Update the project name, description, or accent colour.</span>
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    @click="showModal = false"
                                    class="rounded-sm opacity-70 ring-offset-background transition-colors hover:bg-muted hover:text-foreground hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 p-1.5"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M18 6 6 18"/><path d="m6 6 12 12"/>
                                    </svg>
                                    <span class="sr-only">Close</span>
                                </button>
                            </div>
                        </div>

                        {{-- Create form --}}
                        <form
                            x-show="modalMode === 'create'"
                            method="POST"
                            action="{{ route('projects.store') }}"
                            class="px-6 py-5 space-y-5"
                        >
                            @csrf
                            @include('_partials.project-form-fields')
                            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 border-t border-border/60 pt-4">
                                <button type="button" @click="showModal = false" class="inline-flex h-9 items-center justify-center rounded-md border border-input bg-background px-3 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground">Cancel</button>
                                <button type="submit" class="inline-flex h-9 items-center justify-center rounded-md brand-gradient-bg px-4 text-sm font-medium text-[hsl(var(--on-brand))] shadow-soft transition-base hover:brightness-[1.03]">Save Project</button>
                            </div>
                        </form>

                        {{-- Edit form --}}
                        <form
                            x-show="modalMode === 'edit'"
                            method="POST"
                            :action="`/projects/${editId}`"
                            class="px-6 py-5 space-y-5"
                        >
                            @csrf
                            @method('PATCH')
                            @include('_partials.project-form-fields')
                            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 border-t border-border/60 pt-4">
                                <button type="button" @click="showModal = false" class="inline-flex h-9 items-center justify-center rounded-md border border-input bg-background px-3 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground">Cancel</button>
                                <button type="submit" class="inline-flex h-9 items-center justify-center rounded-md brand-gradient-bg px-4 text-sm font-medium text-[hsl(var(--on-brand))] shadow-soft transition-base hover:brightness-[1.03]">Update Project</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </template>
    </div>
@endsection

@section('content')
    {{-- Safelist gradient classes --}}
    <div class="hidden from-brand-rose to-brand-iris from-brand-teal to-brand-iris from-brand-iris to-brand-teal to-brand-rose to-brand-teal"></div>

    <div
        class="space-y-6"
        x-data="projectsPage({
            projects: @js($projects),
            teamMembers: @js($teamMembers),
        })"
    >
        {{-- Search header --}}
        <div class="mb-5 relative max-w-md">
            <div class="pointer-events-none absolute inset-y-0 left-3 flex items-center">
                <svg class="h-4 w-4 text-muted-foreground" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0Z"/>
                </svg>
            </div>
            <input
                type="search"
                x-model="search"
                placeholder="Search projects…"
                class="w-full rounded-xl border border-border/70 bg-background py-2.5 pl-10 pr-4 text-sm text-foreground placeholder:text-muted-foreground shadow-sm outline-none transition focus:border-primary/40 focus:ring-2 focus:ring-ring/20"
            >
        </div>

        {{-- Empty state: no projects --}}
        <div x-show="projects.length === 0" x-cloak class="section-card p-10 text-center">
            <div class="mx-auto h-12 w-12 rounded-xl brand-soft-bg flex items-center justify-center mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"/>
                </svg>
            </div>
            <div class="text-sm font-semibold">No projects found</div>
            <p class="text-xs text-muted-foreground mt-1">Create your first project to start grouping repositories.</p>
            <button
                type="button"
                @click="window.dispatchEvent(new CustomEvent('open-create-project'))"
                class="mt-4 inline-flex h-9 items-center justify-center gap-1.5 rounded-md brand-gradient-bg px-4 text-sm font-medium text-[hsl(var(--on-brand))] shadow-soft transition-base hover:brightness-[1.03]"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                New Project
            </button>
        </div>

        {{-- Empty state: search returned nothing --}}
        <div x-show="projects.length > 0 && filteredProjects.length === 0" x-cloak class="section-card text-center py-12">
            <h3 class="text-lg font-semibold">No projects match your search</h3>
            <p class="mt-2 text-sm text-muted-foreground">Try a different keyword or clear the search field.</p>
            <button
                type="button"
                @click="clearSearch()"
                class="mt-4 inline-flex h-9 items-center justify-center rounded-md border border-input bg-background px-3 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground"
            >
                Clear Search
            </button>
        </div>

        {{-- Project cards grid --}}
        {{-- Each card wrapper uses display:contents when active so the placeholder + article
             become direct grid children — this lets col-span-full actually span the full row. --}}
{{-- Project cards grid --}}
<div
    x-show="filteredProjects.length > 0"
    x-cloak
    class="space-y-4"
>
    {{-- We render rows manually so the expanded panel can inject between rows --}}
    <template x-for="(row, rowIndex) in projectRows" :key="rowIndex">
        <div class="space-y-4">
            {{-- The row of cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 items-start">
                <template x-for="project in row" :key="project.id">
                    <article
                        @click="selectedId !== project.id && setSelected(project.id)"
                        :class="selectedId === project.id
                            ? 'p-5 ring-[1px] ring-primary shadow-[0_0_0_4px_hsl(var(--primary)/ 0.3)] opacity-60 pointer-events-none'
                            : 'p-5 cursor-pointer hover:shadow-soft'"
                        class="section-card text-left group relative overflow-hidden transition-all duration-300"
                    >
                        {{-- Background glow --}}
                        <div
                            class="absolute -top-12 -right-12 h-32 w-32 rounded-full bg-linear-to-br opacity-20 blur-2xl pointer-events-none"
                            :class="project.color"
                        ></div>

                        <div class="relative">
                            <div class="flex items-start justify-between gap-3 mb-3">
                                <div
                                    class="rounded-lg bg-gradient-to-br shadow-soft flex items-center justify-center shrink-0 h-10 w-10"
                                    :class="project.color"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 on-brand" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"></path>
                                        <path d="M8 10v4"></path><path d="M12 10v2"></path><path d="M16 10v6"></path>
                                    </svg>
                                </div>
                                <div
                                    class="flex items-center gap-1 transition-all opacity-0 group-hover:opacity-100"
                                    @click.stop
                                >
                                    <button type="button" @click="window.dispatchEvent(new CustomEvent('open-edit-project', { detail: project }))" class="inline-flex h-7 w-7 items-center justify-center rounded-md text-sm hover:bg-accent hover:text-accent-foreground transition-colors" title="Edit project">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.85 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                                    </button>
                                    <button type="button" @click="deleteId = project.id; deleteName = project.name; showDeleteDialog = true;" class="inline-flex h-7 w-7 items-center justify-center rounded-md text-sm text-failed hover:bg-failed/10 transition-colors" title="Delete project">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                                    </button>
                                </div>
                            </div>
                            <div class="text-sm font-semibold" x-text="project.name"></div>
                            <div class="text-xs text-muted-foreground mt-1 line-clamp-2 min-h-[32px]" x-text="project.description"></div>
                            <div class="mt-3 flex items-center gap-3 text-[11px] text-muted-foreground">
                                <span class="inline-flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="6" x2="6" y1="3" y2="15"/><circle cx="18" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="M18 9a9 9 0 0 1-9 9"/></svg>
                                    <span x-text="project.repoCount + ' repos'"></span>
                                </span>
                                <span class="inline-flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                    <span x-text="teamMembers.length + ' members'"></span>
                                </span>
                                <span class="ml-auto" x-text="project.lastDeployedAt"></span>
                            </div>
                        </div>
                    </article>
                </template>
            </div>

            {{-- Expanded panel: renders below this row if selected project is in this row --}}
<template x-if="selectedProjectInRow(row)">
    <article
        class="section-card text-left relative overflow-hidden ring-primary shadow-soft p-0 z-10"
    >
        {{-- Background glow --}}
        <div
            class="absolute -top-12 -right-12 h-32 w-32 rounded-full bg-linear-to-br opacity-20 blur-2xl pointer-events-none"
            :class="selectedProjectInRow(row).color"
        ></div>

        {{-- Header --}}
        <div class="relative p-5 border-b border-border/60 brand-soft-bg">
            <div class="flex items-start justify-between gap-3 mb-3">
                <div
                    class="rounded-lg bg-gradient-to-br shadow-soft flex items-center justify-center shrink-0 h-12 w-12"
                    :class="selectedProjectInRow(row).color"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 on-brand" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"></path>
                        <path d="M8 10v4"></path><path d="M12 10v2"></path><path d="M16 10v6"></path>
                    </svg>
                </div>
                <div class="flex items-center gap-1" @click.stop>
                    <button type="button"
                        @click="window.dispatchEvent(new CustomEvent('open-edit-project', { detail: selectedProjectInRow(row) }))"
                        class="inline-flex h-7 w-7 items-center justify-center rounded-md text-sm hover:bg-accent hover:text-accent-foreground transition-colors" title="Edit project">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.85 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                    </button>
                    <button type="button"
                        @click="deleteId = selectedProjectInRow(row).id; deleteName = selectedProjectInRow(row).name; showDeleteDialog = true;"
                        class="inline-flex h-7 w-7 items-center justify-center rounded-md text-sm text-failed hover:bg-failed/10 transition-colors" title="Delete project">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                    </button>
                    <button type="button" @click="selectedId = null"
                        class="inline-flex h-7 w-7 items-center justify-center rounded-md text-sm hover:bg-accent hover:text-accent-foreground transition-colors" title="Collapse">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                </div>
            </div>
            <div class="text-base font-semibold" x-text="selectedProjectInRow(row).name"></div>
            <div class="text-xs text-muted-foreground mt-1" x-text="selectedProjectInRow(row).description"></div>
            <div class="mt-3 flex items-center gap-3 text-[11px] text-muted-foreground">
                <span class="inline-flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="6" x2="6" y1="3" y2="15"/><circle cx="18" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="M18 9a9 9 0 0 1-9 9"/></svg>
                    <span x-text="selectedProjectInRow(row).repoCount + ' repos'"></span>
                </span>
                <span class="inline-flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    <span x-text="teamMembers.length + ' members'"></span>
                </span>
                <span class="ml-auto" x-text="selectedProjectInRow(row).lastDeployedAt"></span>
            </div>
        </div>

        {{-- Repos + Team panels --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 divide-y lg:divide-y-0 lg:divide-x divide-border/60">
            {{-- Repositories panel --}}
            <div class="p-5">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-sm font-semibold inline-flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="6" x2="6" y1="3" y2="15"/><circle cx="18" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="M18 9a9 9 0 0 1-9 9"/></svg>
                        Connected repositories
                    </h4>
                    <span class="text-[11px] text-muted-foreground" x-text="selectedProjectInRow(row).repositories.length"></span>
                </div>
                <template x-if="selectedProjectInRow(row).repositories.length === 0">
                    <p class="text-xs text-muted-foreground py-6 text-center">No repositories connected to this project yet.</p>
                </template>
                <template x-if="selectedProjectInRow(row).repositories.length > 0">
                    <ul class="space-y-2">
                        <template x-for="repo in selectedProjectInRow(row).repositories" :key="repo.id">
                            <li class="flex items-center gap-3 rounded-lg border border-border/60 p-3 hover:shadow-soft transition-base">
                                <div class="h-8 w-8 rounded-md brand-soft-bg flex items-center justify-center text-primary shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 22v-4a4.8 4.8 0 0 0-1-3.5c3 0 6-2 6-5.5.08-1.25-.27-2.48-1-3.5.28-1.15.28-2.35 0-3.5 0 0-1 0-3 1.5-2.64-.5-5.36-.5-8 0C6 2 5 2 5 2c-.3 1.15-.3 2.35 0 3.5A5.403 5.403 0 0 0 4 9c0 3.5 3 5.5 6 5.5-.39.49-.68 1.05-.85 1.65-.17.6-.22 1.23-.15 1.85v4"/><path d="M9 18c-4.51 2-5-2-7-2"/></svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="text-xs font-semibold font-mono truncate" x-text="repo.name"></div>
                                    <div class="text-[11px] text-muted-foreground" x-text="`${repo.branchCount} branches · ${repo.tagCount} tags · default ${repo.defaultBranch}`"></div>
                                </div>
                                <span
                                    class="text-[10px] font-medium px-2 py-0.5 rounded-md border whitespace-nowrap"
                                    :class="{
                                        'bg-success/10 text-success border-success/30': repo.status === 'connected',
                                        'bg-queued/10 text-queued border-queued/30': repo.status === 'expired',
                                        'bg-failed/10 text-failed border-failed/30': repo.status !== 'connected' && repo.status !== 'expired',
                                    }"
                                    x-text="repo.status === 'connected' ? 'Connected' : repo.status === 'expired' ? 'Expired' : 'Needs auth'"
                                ></span>
                            </li>
                        </template>
                    </ul>
                </template>
            </div>

            {{-- Team members panel --}}
            <div class="p-5">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-sm font-semibold inline-flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        Teams involved
                    </h4>
                    <span class="text-[11px] text-muted-foreground" x-text="teamMembers.length"></span>
                </div>
                <template x-if="teamMembers.length === 0">
                    <p class="text-xs text-muted-foreground py-6 text-center">No team members have been assigned yet.</p>
                </template>
                <template x-if="teamMembers.length > 0">
                    <ul class="space-y-2">
                        <template x-for="member in teamMembers" :key="member.id">
                            <li class="flex items-center gap-3 rounded-lg border border-border/60 p-3 hover:shadow-soft transition-base">
                                <div class="h-9 w-9 rounded-lg brand-gradient-bg shadow-soft flex items-center justify-center text-[11px] font-semibold on-brand shrink-0" x-text="member.initials"></div>
                                <div class="min-w-0 flex-1">
                                    <div class="text-xs font-semibold truncate" x-text="member.name"></div>
                                    <div class="text-[11px] text-muted-foreground capitalize" x-text="member.role"></div>
                                </div>
                                <span class="text-[11px] text-muted-foreground inline-flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 16.5c-1.5 1.26-1.5 2.74-1.5 4.5 1.76 0 3.24 0 4.5-1.5"/><path d="m12 15-3-3a21.7 21.7 0 0 1 3-7.5c2.1-3.27 5.13-4.5 9-4.5 0 3.87-1.23 6.9-4.5 9a21.7 21.7 0 0 1-7.5 3"/><path d="M9 12H4.5C3 12 2 13 2 14.5c0 1.34.63 2.77 1.5 3.5L6 20.5c.73.87 2.16 1.5 3.5 1.5C11 22 12 21 12 19.5V15"/><path d="M9 9V4.5C9 3 10 2 11.5 2c1.34 0 2.77.63 3.5 1.5L17.5 6c.87.73 1.5 2.16 1.5 3.5C19 11 18 12 16.5 12H12"/></svg>
                                    active
                                </span>
                            </li>
                        </template>
                    </ul>
                </template>
            </div>
        </div>
    </article>
</template>
        </div>
    </template>
</div>

        {{-- Delete confirmation dialog --}}
        <template x-teleport="body">
            <div x-show="showDeleteDialog" x-cloak class="relative z-50">
                <div
                    x-show="showDeleteDialog"
                    x-transition:enter="ease-out duration-200"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-150"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 bg-background/80 backdrop-blur-sm"
                ></div>

                <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div
                        x-show="showDeleteDialog"
                        @keydown.escape.window="showDeleteDialog = false"
                        x-transition:enter="ease-out duration-200"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="ease-in duration-150"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="w-full max-w-md border border-border bg-background rounded-2xl shadow-lg p-6"
                        role="alertdialog"
                        aria-modal="true"
                    >
                        <h2 class="text-lg font-semibold">Delete this project?</h2>
                        <p class="mt-2 text-sm text-muted-foreground">
                            This removes <strong x-text="deleteName"></strong> from the workspace. Connected repositories and packages will keep their history but will no longer be grouped under it.
                        </p>
                        <div class="mt-5 flex justify-end gap-2">
                            <button
                                type="button"
                                @click="showDeleteDialog = false"
                                class="inline-flex h-9 items-center justify-center rounded-md border border-input bg-background px-3 text-sm font-medium shadow-sm transition-colors hover:bg-accent"
                            >
                                Cancel
                            </button>
                            <form :action="`/projects/${deleteId}`" method="POST" @submit="showDeleteDialog = false">
                                @csrf
                                @method('DELETE')
                                <button
                                    type="submit"
                                    class="inline-flex h-9 items-center justify-center rounded-md bg-failed px-4 text-sm font-medium text-white shadow-sm transition-colors hover:bg-failed/90"
                                >
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
@endsection

@push('scripts')
<script>
function projectsPage({ projects, teamMembers }) {
    return {
        search: '',
        projects,
        teamMembers,
        selectedId: null,
        showDeleteDialog: false,
        deleteId: null,
        deleteName: '',
        colCount: 3, // updated reactively

        get filteredProjects() {
            const query = this.search.trim().toLowerCase();
            if (!query) return this.projects;
            return this.projects.filter(p =>
                p.name.toLowerCase().includes(query) ||
                p.slug.toLowerCase().includes(query) ||
                p.description.toLowerCase().includes(query)
            );
        },

        // Splits filteredProjects into rows of colCount
        get projectRows() {
            const cols = this.colCount;
            const rows = [];
            const list = this.filteredProjects;
            for (let i = 0; i < list.length; i += cols) {
                rows.push(list.slice(i, i + cols));
            }
            return rows;
        },

        // Returns the selected project if it lives in this row, else null
        selectedProjectInRow(row) {
            if (!this.selectedId) return null;
            return row.find(p => p.id === this.selectedId) ?? null;
        },

        // Call this on mount + resize to sync colCount with actual CSS breakpoints
        updateColCount() {
            const w = window.innerWidth;
            if (w >= 1280) this.colCount = 3;       // xl:grid-cols-3
            else if (w >= 640) this.colCount = 2;   // sm:grid-cols-2
            else this.colCount = 1;
        },

        setSelected(id) {
            this.selectedId = id;
        },

        clearSearch() { this.search = ''; },

        init() {
            this.updateColCount();
            window.addEventListener('resize', () => this.updateColCount());
        },
    };
}
</script>
@endpush