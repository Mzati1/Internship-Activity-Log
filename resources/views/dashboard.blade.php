@extends('layouts.app')

@section('content')
@section('content')
<div class="px-4 sm:px-0">
    @if(session('success'))
        <div class="mb-6 rounded-lg bg-emerald-50 p-4 border border-emerald-100 flex items-center">
            <svg class="h-5 w-5 text-emerald-400 mr-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
            <p class="text-sm font-medium text-emerald-800">{{ session('success') }}</p>
        </div>
    @endif
    @if($errors->any())
        <div class="mb-6 rounded-lg bg-red-50 p-4 border border-red-100">
            <ul class="list-disc pl-5 text-sm text-red-700">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Student Profile Section -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 mb-10">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <h2 class="text-3xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-indigo-700 to-violet-700">
                    {{ $studentDetails['name'] }}
                </h2>
                <div class="flex flex-wrap items-center gap-4 mt-3 text-slate-500 font-medium">
                    <span class="flex items-center">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"></path></svg>
                        {{ $studentDetails['reg_number'] }}
                    </span>
                    <span class="text-slate-300">|</span>
                    <span class="flex items-center">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        {{ $studentDetails['company'] }}
                    </span>
                    <span class="text-slate-300">|</span>
                    <span class="flex items-center">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2-2v12a2 2 0 002 2z"></path></svg>
                        Started: {{ $studentDetails['start_date'] }}
                    </span>
                </div>
            </div>
            <div class="flex items-center gap-4 text-sm">
                <button onclick="document.getElementById('profile-modal').classList.remove('hidden')" class="px-4 py-2 bg-indigo-50 text-indigo-700 rounded-lg hover:bg-indigo-100 font-medium transition-colors">
                    Edit Profile
                </button>
                <div class="px-5 py-3 bg-slate-50 rounded-xl border border-slate-100 hidden lg:block">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wide">Supervisor</p>
                    <p class="font-semibold text-slate-700">{{ $studentDetails['supervisor'] }}</p>
                    <p class="text-xs text-slate-400">{{ $studentDetails['supervisor_email'] }}</p>
                    <p class="text-xs text-slate-400">Signature: {{ $studentDetails['supervisor_signature'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div id="profile-modal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 overflow-y-auto h-full w-full flex items-center justify-center">
        <div class="relative bg-white rounded-2xl shadow-xl border border-slate-200 p-8 w-full max-w-lg mx-4">
            <h3 class="text-xl font-bold text-slate-900 mb-6">Edit Student Profile</h3>
            
            <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Student Name</label>
                        <input type="text" name="name" value="{{ $studentDetails['name'] !== 'Not Set' ? $studentDetails['name'] : '' }}" class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-base py-3 px-4" required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Registration Number</label>
                        <input type="text" name="reg_number" value="{{ $studentDetails['reg_number'] !== 'Not Set' ? $studentDetails['reg_number'] : '' }}" class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-base py-3 px-4" required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Internship Start Date</label>
                        <input type="date" name="start_date" value="{{ $studentDetails['start_date'] !== 'Not Set' ? $studentDetails['start_date'] : '' }}" class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-base py-3 px-4" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Company/Organization</label>
                        <input type="text" name="company" value="{{ $studentDetails['company'] !== 'Not Set' ? $studentDetails['company'] : '' }}" class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-base py-3 px-4" required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Supervisor Name (Optional)</label>
                        <input type="text" name="supervisor" value="{{ $studentDetails['supervisor'] !== 'Not Set' ? $studentDetails['supervisor'] : '' }}" class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-base py-3 px-4">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Supervisor Email (Optional)</label>
                        <input type="email" name="supervisor_email" value="{{ $studentDetails['supervisor_email'] !== 'Not Set' ? $studentDetails['supervisor_email'] : '' }}" class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-base py-3 px-4">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Supervisor Signature (Optional)</label>
                        <input type="file" name="supervisor_signature" accept="image/png,image/jpeg,image/jpg" class="block w-full rounded-lg border border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-base py-3 px-4 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100">
                    </div>
                </div>
                
                <div class="mt-8 flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('profile-modal').classList.add('hidden')" class="px-4 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50 font-medium transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium transition-colors">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="flex items-center justify-between mb-8">
        <div>
            <h2 class="text-2xl font-bold leading-tight text-slate-900">Weekly Progress</h2>
            <p class="mt-1 text-slate-500">Track and manage your internship activities.</p>
        </div>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        @foreach($weeks as $week)
            @php $isLocked = $week['is_locked'] ?? false; @endphp
            <div class="relative">
                <a href="{{ $isLocked ? '#' : route('weekly-log.create', ['week' => $week['number']]) }}" 
                   class="group relative block bg-white rounded-xl shadow-sm border border-slate-200 {{ $isLocked ? 'opacity-60 cursor-not-allowed filter grayscale-[0.5]' : 'hover:shadow-xl hover:border-indigo-200 cursor-pointer' }} transition-all duration-300 overflow-hidden">
                    
                    <div class="absolute top-0 left-0 w-1 h-full 
                        @if($week['status'] == 'Completed') bg-emerald-500 
                        @elseif($week['status'] == 'In Progress') bg-amber-400
                        @else bg-slate-200 @endif 
                        group-hover:h-full transition-all duration-300"></div>

                    <div class="px-6 py-6 pl-8">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xl font-semibold text-slate-800 {{ !$isLocked ? 'group-hover:text-indigo-600' : '' }} transition-colors">
                                Week {{ $week['number'] }}
                            </h3>
                            @if($isLocked)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-400 border border-slate-200">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-4-4h-4a4 4 0 00-4 4v2m6 4h.01"></path></svg>
                                    Locked
                                </span>
                            @elseif($week['status'] == 'Completed')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    Completed
                                </span>
                            @elseif($week['status'] == 'In Progress')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-100">
                                    In Progress
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-50 text-slate-500 border border-slate-200">
                                    Pending
                                </span>
                            @endif
                        </div>
                        
                        <div class="text-sm text-slate-500 h-10 overflow-hidden mb-4">
                            @if($isLocked)
                                <span class="italic text-slate-400">Complete previous week to unlock.</span>
                            @elseif($week['preview'])
                                {{ $week['preview'] }}...
                            @else
                                <span class="italic text-slate-400">No activity recorded yet.</span>
                            @endif
                        </div>

                        <div class="flex items-center text-sm font-medium {{ $isLocked ? 'text-slate-400' : 'text-indigo-600 group-hover:translate-x-1' }} transition-transform duration-200">
                            @if($isLocked)
                                Locked
                            @else
                                {{ $week['status'] == 'Completed' ? 'Edit Log' : 'Start Log' }} 
                            @endif
                            <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
</div>
@endsection
