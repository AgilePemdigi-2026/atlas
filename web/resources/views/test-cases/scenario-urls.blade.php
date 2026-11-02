@extends('layouts.app')

@section('title', $testCase->name.' – ATLAS')

@section('content')
    <section class="mx-auto w-full max-w-[860px] px-6 py-10">
        <h1 class="text-lg/[1.55] font-semibold text-body dark:text-slate-100">{{ $testCase->name }}</h1>

        <table class="mt-6 w-full text-left text-[13px] text-body dark:text-slate-200">
            <thead>
                <tr class="text-[11px] uppercase tracking-wide text-subtle dark:text-slate-400">
                    <th class="px-2 py-2">Skenario</th>
                    <th class="px-2 py-2">URL Awal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($testCase->scenarios as $scenario)
                    <tr class="border-t border-line dark:border-slate-700">
                        <td class="px-2 py-2">{{ $scenario->name }}</td>
                        <td class="px-2 py-2">{{ $scenario->scenario_url ?? 'Belum ada URL' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
@endsection
