@extends('layouts.app')

@section('title', 'Weeks · CSIT Internship Activity Log')

@section('content')
@php
    $unset = fn ($v) => in_array($v, ['Not set', 'Not Set', '', null], true);
    $nextWeek = collect($weeks)->first(fn ($w) => !($w['is_locked'] ?? false) && ($w['status'] ?? '') !== 'Completed');
    $completedCount = collect($weeks)->where('status', 'Completed')->count();
    $hasWorkbook = collect($studentDetails)->contains(fn ($v) => !$unset($v));
@endphp

@if(session('success'))
    <div class="flash-ok" role="status">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="flash-err" role="alert">
        <ul class="list-disc space-y-1 pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<section class="panel mb-8 p-5 sm:p-6" aria-labelledby="profile-heading">
    <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <h1 id="profile-heading" class="text-3xl font-semibold tracking-tight text-ink">
                {{ $unset($studentDetails['name']) ? 'Add your student profile' : $studentDetails['name'] }}
            </h1>
            <dl class="mt-3 flex flex-col gap-1 text-sm text-ink-muted sm:flex-row sm:flex-wrap sm:gap-x-4 sm:gap-y-1">
                <div><dt class="inline font-medium text-ink">Reg</dt> <dd class="inline">{{ $unset($studentDetails['reg_number']) ? '—' : $studentDetails['reg_number'] }}</dd></div>
                <div><dt class="inline font-medium text-ink">Placement</dt> <dd class="inline">{{ $unset($studentDetails['company']) ? '—' : $studentDetails['company'] }}</dd></div>
                <div><dt class="inline font-medium text-ink">Start</dt> <dd class="inline">{{ $unset($studentDetails['start_date']) ? '—' : $studentDetails['start_date'] }}</dd></div>
            </dl>
            <p class="mt-3 text-sm text-ink-muted">
                Supervisor:
                <span class="text-ink">{{ $unset($studentDetails['supervisor']) ? 'Not added' : $studentDetails['supervisor'] }}</span>
                @if(!$unset($studentDetails['supervisor_email']))
                    · {{ $studentDetails['supervisor_email'] }}
                @endif
                · Signature: {{ $studentDetails['supervisor_signature'] === 'Uploaded' ? 'Uploaded' : 'Not uploaded' }}
            </p>
        </div>
        <button type="button" class="btn-secondary shrink-0" data-open-profile>
            Edit profile
        </button>
    </div>
</section>

@if($nextWeek)
    <div class="mb-6 flex flex-col gap-3 rounded-lg border border-accent/25 bg-accent-soft px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-ink">
            @if($completedCount === 0)
                Weeks are empty. Start with <strong>Week {{ $nextWeek['number'] }}</strong>: add Mon–Fri dailies, then save the weekly summary.
            @else
                Continue with <strong>Week {{ $nextWeek['number'] }}</strong> ({{ $completedCount }} of 16 weeks done).
            @endif
        </p>
        <a href="{{ route('weekly-log.create', ['week' => $nextWeek['number']]) }}" class="btn-primary shrink-0">
            Open week {{ $nextWeek['number'] }}
        </a>
    </div>
@elseif(!$hasWorkbook)
    <div class="flash-err mb-6" role="alert">
        Activity log workbook not found in the project root. Place <code class="text-xs">CSIT-Internship Activity Log - 1.xlsx</code> next to <code class="text-xs">artisan</code>, then refresh.
    </div>
@endif

<section aria-labelledby="weeks-heading">
    <div class="mb-4">
        <h2 id="weeks-heading" class="text-xl font-semibold text-ink">Sixteen weeks</h2>
        <p class="mt-1 text-sm text-ink-muted">A week is done when it has a summary and five daily logs. Later weeks stay locked until the previous week is done.</p>
    </div>

    <div class="panel overflow-hidden">
        <ul class="divide-y divide-line" role="list">
            @foreach($weeks as $week)
                @php
                    $isLocked = $week['is_locked'] ?? false;
                    $status = $week['status'] ?? 'Pending';
                    $label = match($status) {
                        'Completed' => 'Done',
                        'In Progress' => 'In progress',
                        default => 'Not started',
                    };
                    if ($isLocked) {
                        $label = 'Locked';
                    }
                    $chip = $isLocked ? 'status-locked' : match($status) {
                        'Completed' => 'status-done',
                        'In Progress' => 'status-progress',
                        default => 'status-pending',
                    };
                @endphp
                <li class="flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:gap-6">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-base font-semibold text-ink">Week {{ $week['number'] }}</span>
                            <span class="{{ $chip }}">{{ $label }}</span>
                        </div>
                        <p class="mt-1 text-sm text-ink-muted">
                            @if($isLocked)
                                Finish week {{ $week['number'] - 1 }} first.
                            @elseif(!empty($week['preview']))
                                {{ $week['preview'] }}
                            @else
                                No summary yet.
                            @endif
                        </p>
                    </div>
                    <div class="shrink-0">
                        @if($isLocked)
                            <span class="text-sm text-locked">Unavailable</span>
                        @else
                            <a
                                href="{{ route('weekly-log.create', ['week' => $week['number']]) }}"
                                class="{{ $status === 'Completed' ? 'btn-secondary' : 'btn-primary' }}"
                            >
                                {{ $status === 'Completed' ? 'Edit week' : 'Log week' }}
                            </a>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
</section>

<dialog id="profile-modal" class="w-[min(100%,32rem)] rounded-lg border border-line bg-surface p-0 text-ink shadow-lg backdrop:bg-ink/40">
    <form method="dialog" class="border-b border-line px-5 py-4">
        <div class="flex items-center justify-between gap-3">
            <h2 class="text-lg font-semibold">Edit profile</h2>
            <button value="cancel" class="btn-ghost" aria-label="Close">Close</button>
        </div>
    </form>
    <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="px-5 py-5">
        @csrf
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="label" for="profile-name">Student name</label>
                <input id="profile-name" class="field" type="text" name="name" value="{{ $unset($studentDetails['name']) ? '' : $studentDetails['name'] }}" required>
            </div>
            <div>
                <label class="label" for="profile-reg">Registration number</label>
                <input id="profile-reg" class="field" type="text" name="reg_number" value="{{ $unset($studentDetails['reg_number']) ? '' : $studentDetails['reg_number'] }}" required>
            </div>
            <div>
                <label class="label" for="profile-start">Internship start date</label>
                <input id="profile-start" class="field" type="date" name="start_date" value="{{ $unset($studentDetails['start_date']) ? '' : $studentDetails['start_date'] }}" required>
            </div>
            <div class="sm:col-span-2">
                <label class="label" for="profile-company">Company / organization</label>
                <input id="profile-company" class="field" type="text" name="company" value="{{ $unset($studentDetails['company']) ? '' : $studentDetails['company'] }}" required>
            </div>
            <div>
                <label class="label" for="profile-supervisor">Supervisor name <span class="font-normal text-ink-muted">(optional)</span></label>
                <input id="profile-supervisor" class="field" type="text" name="supervisor" value="{{ $unset($studentDetails['supervisor']) ? '' : $studentDetails['supervisor'] }}">
            </div>
            <div>
                <label class="label" for="profile-email">Supervisor email <span class="font-normal text-ink-muted">(optional)</span></label>
                <input id="profile-email" class="field" type="email" name="supervisor_email" value="{{ $unset($studentDetails['supervisor_email']) ? '' : $studentDetails['supervisor_email'] }}">
            </div>
            <div class="sm:col-span-2">
                <label class="label" for="profile-signature">Supervisor signature image <span class="font-normal text-ink-muted">(optional, JPG/PNG)</span></label>
                <input id="profile-signature" class="field" type="file" name="supervisor_signature" accept="image/png,image/jpeg,image/jpg">
            </div>
        </div>
        <div class="mt-6 flex justify-end gap-2">
            <button type="button" class="btn-secondary" data-close-profile>Cancel</button>
            <button type="submit" class="btn-primary">Save profile</button>
        </div>
    </form>
</dialog>

<script>
    (function () {
        const modal = document.getElementById('profile-modal');
        if (!modal) return;
        document.querySelectorAll('[data-open-profile]').forEach((btn) => {
            btn.addEventListener('click', () => modal.showModal());
        });
        document.querySelectorAll('[data-close-profile]').forEach((btn) => {
            btn.addEventListener('click', () => modal.close());
        });
    })();
</script>
@endsection
