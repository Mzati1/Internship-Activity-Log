@extends('layouts.app')

@section('content')
@section('content')
<div class="w-full">
    <div class="bg-white shadow-xl shadow-slate-200 sm:rounded-2xl overflow-hidden border border-slate-100">
        <div class="px-6 py-6 bg-gradient-to-r from-indigo-600 to-violet-600">
            <h3 class="text-xl leading-6 font-bold text-white">
                Record Activity
            </h3>
            <p class="mt-2 text-indigo-100">
                Update your internship activity log for the chosen week.
            </p>
        </div>
        
        <div class="px-6 py-6 sm:p-8">
            @if(session('success'))
                <div class="rounded-lg bg-emerald-50 p-4 mb-6 border border-emerald-100">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-emerald-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-emerald-800">
                                {{ session('success') }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <form action="{{ route('weekly-log.store') }}" method="POST">
                @csrf
                
                <div class="grid grid-cols-1 gap-y-8 gap-x-6 sm:grid-cols-6">
                    <div class="sm:col-span-6 bg-indigo-50 border border-indigo-200 rounded-lg px-4 py-3">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-indigo-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            <span class="text-sm font-semibold text-slate-700">Logging Activities for:</span>
                            <span class="ml-2 text-lg font-bold text-indigo-700">Week {{ $week }}</span>
                        </div>
                    </div>
                    <input type="hidden" name="week" id="week" value="{{ $week }}">

                    <div class="sm:col-span-6 border-t border-slate-100"></div>

                    <div class="sm:col-span-3">
                        <label for="start_date" class="block text-sm font-semibold text-slate-700 mb-1">From Date</label>
                        <input id="start_date" name="start_date" type="date" class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-base py-3 px-4" required>
                    </div>

                    <div class="sm:col-span-3">
                        <label for="end_date" class="block text-sm font-semibold text-slate-700 mb-1">To Date</label>
                        <input id="end_date" name="end_date" type="date" class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-base py-3 px-4" required>
                    </div>

                    <div class="sm:col-span-3">
                        <label for="days_present" class="block text-sm font-semibold text-slate-700 mb-1">Days Present</label>
                        <input id="days_present" name="days_present" type="number" min="0" class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-base py-3 px-4" required>
                    </div>

                    <div class="sm:col-span-3">
                        <label for="days_absent" class="block text-sm font-semibold text-slate-700 mb-1">Days Absent</label>
                        <input id="days_absent" name="days_absent" type="number" min="0" class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-base py-3 px-4" required>
                    </div>

                    <div class="sm:col-span-6">
                        <label for="summary" class="block text-sm font-semibold text-slate-700 mb-1">Summary of Activities</label>
                        <textarea id="summary" name="summary" rows="8" class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-base py-3 px-4" placeholder="Describe your main activities, learnings, and tasks for the week..." required></textarea>
                    </div>
                </div>

                <!-- Daily Reports Section -->
                <div class="mt-10 border-t border-slate-100 pt-8">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h4 class="text-lg font-bold text-slate-900">Daily Activities</h4>
                            <p class="text-sm text-slate-500">Log detailed tasks for each day.</p>
                        </div>
                    </div>

                    <div class="bg-slate-50 rounded-xl p-5 border border-slate-100 mb-6">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-12 items-end">
                            <div class="sm:col-span-3">
                                <label for="daily_date" class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Date</label>
                                <input type="date" id="daily_date" class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-base py-3 px-4">
                            </div>
                            <div class="sm:col-span-7">
                                <label for="daily_activity" class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Activity</label>
                                <input type="text" id="daily_activity" class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-base py-3 px-4" placeholder="e.g. Debugged login module">
                            </div>
                            <div class="sm:col-span-2 flex space-x-2">
                                <input type="hidden" id="edit_row_index" value="">
                                <button type="button" id="add_daily_btn" class="flex-1 inline-flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors shadow-sm shadow-indigo-200">
                                    Add
                                </button>
                                <button type="button" id="cancel_edit_btn" class="hidden flex-1 inline-flex justify-center py-2 px-4 border border-slate-300 rounded-lg text-sm font-medium text-slate-700 bg-white hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Date</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Activity</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="daily_logs_table" class="bg-white divide-y divide-slate-200">
                                <!-- Ajax Content -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-8 border-t border-slate-200 pt-6">
                    <div class="flex justify-end gap-3">
                        <a href="{{ route('dashboard') }}" class="py-2.5 px-5 border border-slate-300 rounded-lg text-sm font-medium text-slate-700 bg-white hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                            Cancel
                        </a>
                        <button type="submit" class="inline-flex justify-center py-2.5 px-5 border border-transparent text-sm font-medium rounded-lg text-white bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-md shadow-indigo-200 transition-all">
                            Save Weekly Log
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
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

        // Pre-fill today's date
        const today = new Date().toISOString().split('T')[0];
        dailyDateInput.value = today;

        function loadWeekData(week) {
            // Clear table
            dailyLogsTable.innerHTML = '';
            resetDailyForm();
            
            fetch(`/weekly-log-data/${week}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error(data.error);
                        return;
                    }

                    // Date Restrictions
                    if (data.internship_start && data.internship_start !== 'Not Set') {
                        const start = new Date(data.internship_start);
                        const weekStart = new Date(start);
                        weekStart.setDate(start.getDate() + (week - 1) * 7);
                        
                        const weekEnd = new Date(weekStart);
                        weekEnd.setDate(weekStart.getDate() + 4); // Friday

                        const today = new Date();
                        const maxDate = weekEnd < today ? weekEnd : today;

                        dailyDateInput.min = weekStart.toISOString().split('T')[0];
                        dailyDateInput.max = maxDate.toISOString().split('T')[0];
                        
                        // Ensure current value is within range
                        if (dailyDateInput.value < dailyDateInput.min) dailyDateInput.value = dailyDateInput.min;
                        if (dailyDateInput.value > dailyDateInput.max) dailyDateInput.value = dailyDateInput.max;
                    }

                    startDateInput.value = data.start_date || '';
                    endDateInput.value = data.end_date || '';
                    daysPresentInput.value = data.days_present || '';
                    daysAbsentInput.value = data.days_absent || '';
                    summaryInput.value = data.summary || '';

                    // Populate Daily Logs
                    if (data.daily_logs && data.daily_logs.length > 0) {
                        data.daily_logs.forEach(log => {
                            addDailyRow(log.date, log.activity, log.row_index);
                        });
                    } else {
                        dailyLogsTable.innerHTML = '<tr><td colspan="3" class="px-6 py-4 whitespace-no-wrap text-sm leading-5 text-gray-500 text-center">No daily logs found for this week.</td></tr>';
                    }
                })
                .catch(error => console.error('Error fetching data:', error));
        }

        function addDailyRow(date, activity, rowIndex) {
            // Remove "No logs" message if exists
            if (dailyLogsTable.innerHTML.includes('No daily logs found')) {
                dailyLogsTable.innerHTML = '';
            }
            const row = `
                <tr data-row-index="${rowIndex}">
                    <td class="px-6 py-4 whitespace-no-wrap text-sm leading-5 text-gray-900">${date}</td>
                    <td class="px-6 py-4 whitespace-no-wrap text-sm leading-5 text-gray-900">${activity}</td>
                    <td class="px-6 py-4 whitespace-no-wrap text-right text-sm leading-5 font-medium">
                        <button type="button" class="text-indigo-600 hover:text-indigo-900 mr-3 edit-daily-btn">Edit</button>
                        <button type="button" class="text-red-600 hover:text-red-900 delete-daily-btn">Delete</button>
                    </td>
                </tr>
            `;
            dailyLogsTable.insertAdjacentHTML('beforeend', row);
        }

        function resetDailyForm() {
            const today = new Date().toISOString().split('T')[0];
            dailyDateInput.value = today;
            dailyActivityInput.value = '';
            editRowIndexInput.value = '';
            addDailyBtn.innerText = 'Add';
            addDailyBtn.classList.remove('bg-indigo-600', 'hover:bg-indigo-500');
            addDailyBtn.classList.add('bg-green-600', 'hover:bg-green-500');
            cancelEditBtn.classList.add('hidden');
        }

        dailyLogsTable.addEventListener('click', function(e) {
            if (e.target.classList.contains('edit-daily-btn')) {
                const tr = e.target.closest('tr');
                const rowIndex = tr.getAttribute('data-row-index');
                const date = tr.children[0].innerText;
                const activity = tr.children[1].innerText;

                dailyDateInput.value = date;
                dailyActivityInput.value = activity;
                editRowIndexInput.value = rowIndex;
                
                addDailyBtn.innerText = 'Update';
                addDailyBtn.classList.remove('bg-green-600', 'hover:bg-green-500');
                addDailyBtn.classList.add('bg-indigo-600', 'hover:bg-indigo-500');
                cancelEditBtn.classList.remove('hidden');
            }
            
            if (e.target.classList.contains('delete-daily-btn')) {
                if (!confirm('Are you sure you want to delete this log?')) return;
                
                const tr = e.target.closest('tr');
                const rowIndex = tr.getAttribute('data-row-index');
                
                fetch('{{ route("weekly-log.daily.delete") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ row_index: rowIndex })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        loadWeekData(weekInput.value); // Reload to reset indices
                    } else {
                        alert('Error deleting: ' + (data.error || 'Unknown'));
                    }
                });
            }
        });

        cancelEditBtn.addEventListener('click', resetDailyForm);

        addDailyBtn.addEventListener('click', function() {
            const week = weekInput.value;
            const date = dailyDateInput.value;
            const activity = dailyActivityInput.value;
            const rowIndex = editRowIndexInput.value;

            if (!date || !activity) {
                alert('Please fill in both Date and Activity.');
                return;
            }

            let url = '{{ route("weekly-log.daily.store") }}';
            let body = { week, date, activity };

            if (rowIndex) {
                url = '{{ route("weekly-log.daily.update") }}';
                body = { row_index: rowIndex, date, activity };
            }

            // Send to backend
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(body)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadWeekData(weekInput.value); // Reload to reflect changes and new indices
                    resetDailyForm();
                } else {
                    alert('Error saving daily log: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => console.error('Error:', error));
        });

        // Load initial week
        if (weekInput.value) {
            loadWeekData(weekInput.value);
        }
    });
</script>
@endsection
