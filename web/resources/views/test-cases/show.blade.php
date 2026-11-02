@extends('layouts.app')

@section('title', $testCase->name.' – ATLAS')

@section('content')
    <section class="mx-auto flex w-full max-w-[860px] flex-col gap-6 px-6 py-10">
        <div class="flex flex-wrap items-center gap-3">
            <h1 class="text-lg/[1.55] font-semibold text-body dark:text-slate-100">{{ $testCase->name }}</h1>
            <x-console.status-badge :status="$testCase->status->value" />
        </div>

        <p class="text-[13px]/[1.55] text-subtle dark:text-slate-400">
            {{ $testCase->scenarios->count() }} skenario · hasil generate tersimpan di bawah.
        </p>

        <a href="{{ route('console') }}" class="text-xs font-semibold text-accent hover:underline dark:text-indigo-300">Kembali ke console</a>
    </section>
@endsection
