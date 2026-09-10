@extends('layouts.app')

@section('title', 'Week ' . $week . ' · CSIT Internship Activity Log')

@section('content')
<div class="mb-6">
    <a href="{{ route('dashboard') }}" class="text-sm font-medium text-accent hover:text-accent-hover">← All weeks</a>
    <h1 class="mt-3 text-3xl font-semibold tracking-tight text-ink">Week {{ $week }}</h1>
    <p class="mt-1 max-w-2xl text-sm text-ink-muted">
        Add daily activities first (saved immediately). Then fill the weekly summary and click <strong>Save weekly summary</strong> to write it to the Excel workbook.
    </p>
</div>

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

<div id="daily-error" class="flash-err hidden" role="alert"></div>

<section class="panel mb-8 p-5 sm:p-6" aria-labelledby="daily-heading">
    <div class="mb-4">
        <h2 id="daily-heading" class="text-lg font-semibold text-ink">Daily activities</h2>
        <p class="mt-1 text-sm text-ink-muted">One entry per workday. Need five distinct dates for the week to count as complete.</p>
    </div>

    <div class="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-12 sm:items-end">
        <div class="sm:col-span-3">
            <label class="label" for="daily_date">Date</label>
            <input type="date" id="daily_date" class="field">
        </div>
        <div class="sm:col-span-7">
            <label class="label" for="daily_activity">What you did</label>
            <input type="text" id="daily_activity" class="field" placeholder="e.g. Configured backup for pharmacy systems">
        </div>
        <div class="flex gap-2 sm:col-span-2">
            <input type="hidden" id="edit_row_index" value="">
            <button type="button" id="add_daily_btn" class="btn-primary flex-1">Add day</button>
            <button type="button" id="cancel_edit_btn" class="btn-secondary hidden flex-1">Cancel</button>
        </div>
    </div>

    <div class="overflow-x-auto rounded-md border border-line">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-paper text-ink-muted">
                <tr>
                    <th scope="col" class="px-4 py-2.5 font-medium">Date</th>
                    <th scope="col" class="px-4 py-2.5 font-medium">Activity</th>
                    <th scope="col" class="px-4 py-2.5 text-right font-medium">Actions</th>
                </tr>
            </thead>
            <tbody id="daily_logs_table" class="divide-y divide-line bg-surface">
                <tr>
                    <td colspan="3" class="px-4 py-6 text-center text-ink-muted">Loading daily entries…</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<form action="{{ route('weekly-log.store') }}" method="POST" class="panel p-5 sm:p-6" aria-labelledby="weekly-heading">
    @csrf
    <input type="hidden" name="week" id="week" value="{{ $week }}">

    <div class="mb-5">
        <h2 id="weekly-heading" class="text-lg font-semibold text-ink">Weekly summary</h2>
        <p class="mt-1 text-sm text-ink-muted">Writes to the WEEK-{{ $week }} sheet (dates, attendance, summary).</p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="label" for="start_date">From</label>
            <input id="start_date" name="start_date" type="date" class="field" required>
        </div>
        <div>
            <label class="label" for="end_date">To</label>
            <input id="end_date" name="end_date" type="date" class="field" required>
        </div>
        <div>
            <label class="label" for="days_present">Days present</label>
            <input id="days_present" name="days_present" type="number" min="0" class="field" required>
        </div>
        <div>
            <label class="label" for="days_absent">Days absent</label>
            <input id="days_absent" name="days_absent" type="number" min="0" class="field" required>
        </div>
        <div class="sm:col-span-2">
            <label class="label" for="summary">Summary of activities</label>
            <textarea id="summary" name="summary" rows="8" class="field" placeholder="Main tasks, systems you worked on, and what you learned this week." required></textarea>
        </div>
    </div>

    <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
        <a href="{{ route('dashboard') }}" class="btn-secondary text-center">Back to weeks</a>
        <button type="submit" class="btn-primary">Save weekly summary</button>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const weekInput = document.getElementById('week');
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');
        const daysPresentInput = document.getElementById('days_present');
        const daysAbsentInput = document.getElementById('days_absent');
        const summaryInput = document.getElementById('summary');
        const dailyLogsTable = document.getElementById('daily_logs_table');
        const addDailyBtn = document.getElementById('add_daily_btn');
        const cancelEditBtn = document.getElementById('cancel_edit_btn');
        const dailyDateInput = document.getElementById('daily_date');
        const dailyActivityInput = document.getElementById('daily_activity');
        const editRowIndexInput = document.getElementById('edit_row_index');
        const dailyError = document.getElementById('daily-error');

        dailyDateInput.value = new Date().toISOString().split('T')[0];

        function showDailyError(message) {
            dailyError.textContent = message;
            dailyError.classList.remove('hidden');
        }

        function clearDailyError() {
            dailyError.textContent = '';
            dailyError.classList.add('hidden');
        }

        function emptyState() {
            dailyLogsTable.innerHTML = '<tr><td colspan="3" class="px-4 py-6 text-center text-ink-muted">No daily entries yet. Add Mon–Fri above.</td></tr>';
        }

        function loadWeekData(week) {
            dailyLogsTable.innerHTML = '<tr><td colspan="3" class="px-4 py-6 text-center text-ink-muted">Loading daily entries…</td></tr>';
            resetDailyForm();
            clearDailyError();

            fetch(`/weekly-log-data/${week}`)
                .then((response) => response.json())
                .then((data) => {
                    if (data.error) {
                        showDailyError(data.error);
                        emptyState();
                        return;
                    }

                    if (data.internship_start && !['Not Set', 'Not set'].includes(data.internship_start)) {
                        const start = new Date(data.internship_start);
                        if (!Number.isNaN(start.getTime())) {
                            const weekStart = new Date(start);
                            weekStart.setDate(start.getDate() + (week - 1) * 7);
                            const weekEnd = new Date(weekStart);
                            weekEnd.setDate(weekStart.getDate() + 4);
                            const today = new Date();
                            const maxDate = weekEnd < today ? weekEnd : today;
                            dailyDateInput.min = weekStart.toISOString().split('T')[0];
                            dailyDateInput.max = maxDate.toISOString().split('T')[0];
                            if (dailyDateInput.value < dailyDateInput.min) dailyDateInput.value = dailyDateInput.min;
                            if (dailyDateInput.value > dailyDateInput.max) dailyDateInput.value = dailyDateInput.max;
                        }
                    }

                    startDateInput.value = data.start_date || '';
                    endDateInput.value = data.end_date || '';
                    daysPresentInput.value = data.days_present || '';
                    daysAbsentInput.value = data.days_absent || '';
                    summaryInput.value = data.summary || '';

                    dailyLogsTable.innerHTML = '';
                    if (data.daily_logs && data.daily_logs.length > 0) {
                        data.daily_logs.forEach((log) => addDailyRow(log.date, log.activity, log.row_index));
                    } else {
                        emptyState();
                    }
                })
                .catch(() => {
                    showDailyError('Could not load week data. Check that the Excel files are closed and try again.');
                    emptyState();
                });
        }

        function addDailyRow(date, activity, rowIndex) {
            if (dailyLogsTable.innerHTML.includes('No daily entries')) {
                dailyLogsTable.innerHTML = '';
            }
            dailyLogsTable.insertAdjacentHTML('beforeend', `
                <tr data-row-index="${rowIndex}">
                    <td class="px-4 py-3 text-ink">${date}</td>
                    <td class="px-4 py-3 text-ink">${activity}</td>
                    <td class="px-4 py-3 text-right">
                        <button type="button" class="btn-ghost edit-daily-btn">Edit</button>
                        <button type="button" class="btn-ghost text-danger delete-daily-btn">Delete</button>
                    </td>
                </tr>
            `);
        }

        function resetDailyForm() {
            dailyDateInput.value = new Date().toISOString().split('T')[0];
            dailyActivityInput.value = '';
            editRowIndexInput.value = '';
            addDailyBtn.textContent = 'Add day';
            cancelEditBtn.classList.add('hidden');
        }

        dailyLogsTable.addEventListener('click', function (e) {
            if (e.target.classList.contains('edit-daily-btn')) {
                const tr = e.target.closest('tr');
                dailyDateInput.value = tr.children[0].innerText;
                dailyActivityInput.value = tr.children[1].innerText;
                editRowIndexInput.value = tr.getAttribute('data-row-index');
                addDailyBtn.textContent = 'Update day';
                cancelEditBtn.classList.remove('hidden');
            }

            if (e.target.classList.contains('delete-daily-btn')) {
                if (!confirm('Delete this daily entry? This cannot be undone.')) return;
                const rowIndex = e.target.closest('tr').getAttribute('data-row-index');
                fetch('{{ route('weekly-log.daily.delete') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ row_index: rowIndex })
                })
                    .then((r) => r.json())
                    .then((data) => {
                        if (data.success) loadWeekData(weekInput.value);
                        else showDailyError(data.error || 'Could not delete that entry.');
                    });
            }
        });

        cancelEditBtn.addEventListener('click', resetDailyForm);

        addDailyBtn.addEventListener('click', function () {
            clearDailyError();
            const week = weekInput.value;
            const date = dailyDateInput.value;
            const activity = dailyActivityInput.value;
            const rowIndex = editRowIndexInput.value;

            if (!date || !activity) {
                showDailyError('Enter both a date and what you did.');
                return;
            }

            let url = '{{ route('weekly-log.daily.store') }}';
            let body = { week, date, activity };
            if (rowIndex) {
                url = '{{ route('weekly-log.daily.update') }}';
                body = { row_index: rowIndex, date, activity };
            }

            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify(body)
            })
                .then(async (response) => {
                    const data = await response.json();
                    if (data.success) {
                        loadWeekData(weekInput.value);
                        resetDailyForm();
                    } else {
                        showDailyError(data.error || 'Could not save that daily entry.');
                    }
                })
                .catch(() => showDailyError('Network error while saving. Close Excel if it is open, then retry.'));
        });

        if (weekInput.value) loadWeekData(weekInput.value);
    });
</script>
@endsection
