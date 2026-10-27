@extends('layouts.app')

@section('bare', true)

@section('content')
    @php
        $lines = [
            ['keyword' => 'Scenario:', 'text' => 'Add product to cart', 'color' => 'text-[#B8A5FF]'],
            ['keyword' => 'Given', 'text' => 'I am signed in', 'color' => 'text-[#82E889]'],
            ['keyword' => 'When', 'text' => 'I click “Add to cart”', 'color' => 'text-[#82E889]'],
            ['keyword' => 'Then', 'text' => 'the cart count is 1', 'color' => 'text-[#82E889]'],
        ];
    @endphp

    <div class="auth-bg relative flex min-h-screen items-center justify-center overflow-hidden px-4 py-10 font-dm">
        <div aria-hidden="true" class="pointer-events-none absolute inset-0">
            <div class="absolute -top-32 -left-48 size-[620px] rounded-full bg-[#5650DA]/30 blur-2xl dark:bg-[#5650DA]/25"></div>
            <div class="absolute -top-4 -left-20 size-[430px] rounded-full border-[20px] border-white/70 dark:border-white/10"></div>
            <div class="absolute -right-40 -bottom-40 size-[560px] rounded-full bg-[#65BE6A]/30 blur-2xl dark:bg-[#65BE6A]/20"></div>
            <div class="absolute -right-16 -bottom-16 size-[360px] rounded-full border-[20px] border-white/70 dark:border-white/10"></div>

            {{-- Cincin titik dari Figma: bentuk didefinisikan sekali, dipakai 3x --}}
            <svg width="0" height="0" class="absolute"><defs><path id="atlas-dots" d="M262.1 123.0h0 M272.8 123.0h0 M294.2 112.3h0 M283.5 123.0h0 M294.2 123.0h0 M304.9 112.3h0 M315.6 112.3h0 M304.9 123.0h0 M315.6 123.0h0 M326.3 112.3h0 M337.0 112.3h0 M326.3 123.0h0 M337.0 123.0h0 M347.8 112.3h0 M358.4 112.3h0 M347.8 123.0h0 M358.4 123.0h0 M369.1 112.3h0 M379.8 112.3h0 M369.1 123.0h0 M379.8 123.0h0 M390.5 112.3h0 M401.2 112.3h0 M390.5 123.0h0 M401.2 123.0h0 M411.9 123.0h0 M187.2 165.8h0 M197.9 155.1h0 M208.6 155.1h0 M197.9 165.8h0 M208.6 165.8h0 M219.3 144.4h0 M219.3 155.1h0 M230.0 144.4h0 M230.0 155.1h0 M219.3 165.8h0 M240.7 133.8h0 M240.7 144.4h0 M240.7 155.1h0 M251.4 133.8h0 M251.4 144.4h0 M251.4 155.1h0 M262.1 133.8h0 M262.1 144.4h0 M272.8 133.8h0 M283.5 133.8h0 M283.5 144.4h0 M294.2 133.8h0 M294.2 144.4h0 M304.9 133.8h0 M315.6 133.8h0 M326.3 133.8h0 M369.1 133.8h0 M379.8 133.8h0 M390.5 133.8h0 M401.2 133.8h0 M411.9 133.8h0 M411.9 144.4h0 M422.6 133.8h0 M422.6 144.4h0 M433.3 133.8h0 M433.3 144.4h0 M433.3 155.1h0 M444.0 133.8h0 M444.0 144.4h0 M444.0 155.1h0 M454.7 144.4h0 M454.7 155.1h0 M465.4 144.4h0 M465.4 155.1h0 M454.7 165.8h0 M465.4 165.8h0 M476.1 165.8h0 M486.8 165.8h0 M144.4 197.9h0 M133.8 208.6h0 M144.4 208.6h0 M155.1 187.2h0 M155.1 197.9h0 M165.8 176.5h0 M165.8 187.2h0 M165.8 197.9h0 M155.1 208.6h0 M165.8 208.6h0 M176.5 176.5h0 M176.5 187.2h0 M176.5 197.9h0 M187.2 176.5h0 M187.2 187.2h0 M197.9 176.5h0 M272.8 208.6h0 M283.5 197.9h0 M283.5 208.6h0 M294.2 208.6h0 M304.9 197.9h0 M315.6 197.9h0 M304.9 208.6h0 M315.6 208.6h0 M326.3 197.9h0 M337.0 197.9h0 M326.3 208.6h0 M337.0 208.6h0 M347.8 197.9h0 M358.4 197.9h0 M347.8 208.6h0 M358.4 208.6h0 M369.1 208.6h0 M379.8 208.6h0 M465.4 176.5h0 M476.1 176.5h0 M476.1 187.2h0 M486.8 176.5h0 M486.8 187.2h0 M486.8 197.9h0 M497.5 176.5h0 M497.5 187.2h0 M497.5 197.9h0 M508.2 197.9h0 M497.5 208.6h0 M508.2 208.6h0 M518.9 208.6h0 M101.6 240.7h0 M90.9 251.4h0 M101.6 251.4h0 M112.3 240.7h0 M123.0 219.3h0 M123.0 230.0h0 M123.0 240.7h0 M112.3 251.4h0 M123.0 251.4h0 M133.8 219.3h0 M133.8 230.0h0 M133.8 240.7h0 M144.4 219.3h0 M144.4 230.0h0 M208.6 240.7h0 M197.9 251.4h0 M208.6 251.4h0 M219.3 240.7h0 M230.0 230.0h0 M230.0 240.7h0 M219.3 251.4h0 M230.0 251.4h0 M240.7 219.3h0 M240.7 230.0h0 M240.7 240.7h0 M251.4 219.3h0 M251.4 230.0h0 M262.1 219.3h0 M262.1 230.0h0 M262.1 240.7h0 M272.8 219.3h0 M272.8 230.0h0 M283.5 219.3h0 M294.2 219.3h0 M304.9 219.3h0 M315.6 219.3h0 M337.0 219.3h0 M347.8 219.3h0 M358.4 219.3h0 M369.1 219.3h0 M369.1 230.0h0 M379.8 219.3h0 M379.8 230.0h0 M379.8 240.7h0 M390.5 219.3h0 M390.5 230.0h0 M390.5 240.7h0 M401.2 230.0h0 M401.2 240.7h0 M390.5 251.4h0 M401.2 251.4h0 M411.9 240.7h0 M411.9 251.4h0 M422.6 251.4h0 M497.5 219.3h0 M508.2 219.3h0 M508.2 230.0h0 M508.2 240.7h0 M518.9 219.3h0 M518.9 230.0h0 M518.9 240.7h0 M529.6 230.0h0 M529.6 240.7h0 M518.9 251.4h0 M529.6 251.4h0 M540.3 251.4h0 M80.2 283.5h0 M69.5 294.2h0 M80.2 294.2h0 M90.9 262.1h0 M90.9 272.8h0 M90.9 283.5h0 M101.6 262.1h0 M101.6 272.8h0 M101.6 283.5h0 M90.9 294.2h0 M112.3 262.1h0 M165.8 294.2h0 M176.5 272.8h0 M176.5 283.5h0 M187.2 262.1h0 M187.2 272.8h0 M187.2 283.5h0 M176.5 294.2h0 M187.2 294.2h0 M197.9 262.1h0 M197.9 272.8h0 M197.9 283.5h0 M208.6 262.1h0 M208.6 272.8h0 M219.3 262.1h0 M272.8 283.5h0 M262.1 294.2h0 M272.8 294.2h0 M283.5 283.5h0 M294.2 283.5h0 M283.5 294.2h0 M294.2 294.2h0 M304.9 272.8h0 M304.9 283.5h0 M315.6 272.8h0 M315.6 283.5h0 M304.9 294.2h0 M326.3 283.5h0 M337.0 283.5h0 M326.3 294.2h0 M337.0 294.2h0 M347.8 283.5h0 M358.4 283.5h0 M347.8 294.2h0 M358.4 294.2h0 M369.1 294.2h0 M379.8 294.2h0 M401.2 262.1h0 M411.9 262.1h0 M411.9 272.8h0 M422.6 262.1h0 M422.6 272.8h0 M422.6 283.5h0 M422.6 294.2h0 M433.3 272.8h0 M433.3 283.5h0 M444.0 283.5h0 M433.3 294.2h0 M444.0 294.2h0 M518.9 262.1h0 M518.9 272.8h0 M518.9 283.5h0 M529.6 262.1h0 M529.6 272.8h0 M529.6 283.5h0 M518.9 294.2h0 M540.3 262.1h0 M540.3 272.8h0 M540.3 283.5h0 M540.3 294.2h0 M58.8 337.0h0 M69.5 304.9h0 M69.5 315.6h0 M69.5 326.3h0 M80.2 304.9h0 M80.2 315.6h0 M80.2 326.3h0 M69.5 337.0h0 M80.2 337.0h0 M144.4 337.0h0 M155.1 315.6h0 M155.1 326.3h0 M165.8 304.9h0 M165.8 315.6h0 M165.8 326.3h0 M155.1 337.0h0 M165.8 337.0h0 M176.5 304.9h0 M176.5 315.6h0 M187.2 304.9h0 M240.7 315.6h0 M240.7 326.3h0 M251.4 304.9h0 M251.4 315.6h0 M251.4 326.3h0 M240.7 337.0h0 M251.4 337.0h0 M262.1 304.9h0 M262.1 315.6h0 M262.1 326.3h0 M272.8 304.9h0 M315.6 326.3h0 M304.9 337.0h0 M315.6 337.0h0 M326.3 326.3h0 M326.3 337.0h0 M337.0 337.0h0 M347.8 304.9h0 M358.4 304.9h0 M369.1 304.9h0 M369.1 315.6h0 M379.8 304.9h0 M379.8 315.6h0 M379.8 326.3h0 M379.8 337.0h0 M390.5 315.6h0 M390.5 326.3h0 M390.5 337.0h0 M401.2 337.0h0 M422.6 304.9h0 M433.3 304.9h0 M433.3 315.6h0 M433.3 326.3h0 M444.0 304.9h0 M444.0 315.6h0 M444.0 326.3h0 M433.3 337.0h0 M444.0 337.0h0 M454.7 326.3h0 M454.7 337.0h0 M529.6 304.9h0 M529.6 315.6h0 M529.6 326.3h0 M529.6 337.0h0 M540.3 304.9h0 M540.3 315.6h0 M540.3 326.3h0 M551.0 326.3h0 M540.3 337.0h0 M58.8 347.8h0 M58.8 358.4h0 M58.8 369.1h0 M48.1 379.8h0 M58.8 379.8h0 M69.5 347.8h0 M69.5 358.4h0 M69.5 369.1h0 M80.2 347.8h0 M69.5 379.8h0 M144.4 347.8h0 M144.4 358.4h0 M144.4 369.1h0 M144.4 379.8h0 M155.1 347.8h0 M155.1 358.4h0 M155.1 369.1h0 M165.8 347.8h0 M155.1 379.8h0 M230.0 347.8h0 M230.0 358.4h0 M230.0 369.1h0 M219.3 379.8h0 M230.0 379.8h0 M240.7 347.8h0 M240.7 358.4h0 M240.7 369.1h0 M240.7 379.8h0 M283.5 369.1h0 M294.2 347.8h0 M294.2 358.4h0 M294.2 369.1h0 M294.2 379.8h0 M304.9 347.8h0 M304.9 358.4h0 M304.9 369.1h0 M315.6 347.8h0 M304.9 379.8h0 M326.3 347.8h0 M326.3 358.4h0 M337.0 347.8h0 M337.0 358.4h0 M337.0 369.1h0 M347.8 358.4h0 M379.8 347.8h0 M379.8 358.4h0 M379.8 369.1h0 M379.8 379.8h0 M390.5 347.8h0 M390.5 358.4h0 M390.5 369.1h0 M401.2 347.8h0 M401.2 369.1h0 M390.5 379.8h0 M444.0 347.8h0 M444.0 358.4h0 M444.0 369.1h0 M433.3 379.8h0 M444.0 379.8h0 M454.7 347.8h0 M454.7 358.4h0 M454.7 369.1h0 M454.7 379.8h0 M529.6 347.8h0 M529.6 358.4h0 M529.6 369.1h0 M518.9 379.8h0 M529.6 379.8h0 M540.3 347.8h0 M540.3 358.4h0 M540.3 369.1h0 M58.8 390.5h0 M58.8 401.2h0 M58.8 411.9h0 M58.8 422.6h0 M69.5 390.5h0 M69.5 401.2h0 M69.5 411.9h0 M69.5 422.6h0 M80.2 422.6h0 M133.8 390.5h0 M133.8 401.2h0 M133.8 411.9h0 M144.4 390.5h0 M144.4 401.2h0 M144.4 411.9h0 M144.4 422.6h0 M155.1 390.5h0 M155.1 401.2h0 M155.1 411.9h0 M155.1 422.6h0 M219.3 401.2h0 M230.0 390.5h0 M230.0 411.9h0 M240.7 390.5h0 M240.7 401.2h0 M240.7 411.9h0 M251.4 401.2h0 M251.4 411.9h0 M240.7 422.6h0 M251.4 422.6h0 M294.2 390.5h0 M304.9 390.5h0 M304.9 401.2h0 M315.6 390.5h0 M315.6 401.2h0 M315.6 411.9h0 M326.3 390.5h0 M326.3 401.2h0 M326.3 411.9h0 M337.0 401.2h0 M337.0 411.9h0 M326.3 422.6h0 M337.0 422.6h0 M347.8 401.2h0 M347.8 411.9h0 M358.4 390.5h0 M358.4 401.2h0 M358.4 411.9h0 M347.8 422.6h0 M369.1 390.5h0 M369.1 401.2h0 M369.1 411.9h0 M379.8 390.5h0 M379.8 401.2h0 M390.5 390.5h0 M422.6 411.9h0 M422.6 422.6h0 M433.3 390.5h0 M433.3 401.2h0 M433.3 411.9h0 M444.0 390.5h0 M444.0 401.2h0 M444.0 411.9h0 M433.3 422.6h0 M454.7 390.5h0 M508.2 401.2h0 M508.2 411.9h0 M508.2 422.6h0 M518.9 390.5h0 M518.9 401.2h0 M518.9 411.9h0 M529.6 390.5h0 M529.6 401.2h0 M529.6 411.9h0 M518.9 422.6h0 M540.3 390.5h0 M69.5 433.3h0 M69.5 444.0h0 M69.5 454.7h0 M80.2 433.3h0 M80.2 444.0h0 M80.2 454.7h0 M80.2 465.4h0 M90.9 444.0h0 M90.9 454.7h0 M90.9 465.4h0 M101.6 465.4h0 M144.4 433.3h0 M155.1 433.3h0 M155.1 444.0h0 M155.1 454.7h0 M165.8 433.3h0 M165.8 444.0h0 M165.8 454.7h0 M155.1 465.4h0 M165.8 465.4h0 M176.5 454.7h0 M176.5 465.4h0 M240.7 433.3h0 M240.7 444.0h0 M251.4 433.3h0 M251.4 444.0h0 M251.4 454.7h0 M262.1 433.3h0 M262.1 444.0h0 M262.1 454.7h0 M272.8 444.0h0 M272.8 454.7h0 M262.1 465.4h0 M272.8 465.4h0 M283.5 454.7h0 M283.5 465.4h0 M294.2 465.4h0 M304.9 465.4h0 M315.6 465.4h0 M326.3 465.4h0 M337.0 465.4h0 M347.8 465.4h0 M358.4 465.4h0 M379.8 454.7h0 M369.1 465.4h0 M379.8 465.4h0 M390.5 454.7h0 M401.2 444.0h0 M401.2 454.7h0 M390.5 465.4h0 M401.2 465.4h0 M411.9 433.3h0 M411.9 444.0h0 M411.9 454.7h0 M422.6 433.3h0 M422.6 444.0h0 M422.6 454.7h0 M433.3 433.3h0 M476.1 454.7h0 M486.8 444.0h0 M486.8 454.7h0 M476.1 465.4h0 M486.8 465.4h0 M497.5 433.3h0 M497.5 444.0h0 M497.5 454.7h0 M508.2 433.3h0 M508.2 444.0h0 M508.2 454.7h0 M518.9 433.3h0 M80.2 476.1h0 M90.9 476.1h0 M90.9 486.8h0 M101.6 476.1h0 M101.6 486.8h0 M101.6 497.5h0 M101.6 508.2h0 M112.3 476.1h0 M112.3 486.8h0 M112.3 497.5h0 M123.0 497.5h0 M112.3 508.2h0 M123.0 508.2h0 M165.8 476.1h0 M165.8 486.8h0 M176.5 476.1h0 M176.5 486.8h0 M176.5 497.5h0 M187.2 476.1h0 M187.2 486.8h0 M187.2 497.5h0 M197.9 486.8h0 M197.9 497.5h0 M208.6 486.8h0 M208.6 497.5h0 M197.9 508.2h0 M208.6 508.2h0 M219.3 497.5h0 M219.3 508.2h0 M230.0 508.2h0 M272.8 476.1h0 M294.2 476.1h0 M304.9 476.1h0 M304.9 486.8h0 M315.6 476.1h0 M315.6 486.8h0 M326.3 476.1h0 M326.3 486.8h0 M337.0 476.1h0 M337.0 486.8h0 M347.8 476.1h0 M347.8 486.8h0 M358.4 476.1h0 M358.4 486.8h0 M369.1 476.1h0 M369.1 486.8h0 M379.8 476.1h0 M433.3 497.5h0 M444.0 497.5h0 M433.3 508.2h0 M444.0 508.2h0 M454.7 486.8h0 M454.7 497.5h0 M465.4 476.1h0 M465.4 486.8h0 M465.4 497.5h0 M454.7 508.2h0 M476.1 476.1h0 M476.1 486.8h0 M486.8 476.1h0 M112.3 518.9h0 M112.3 529.6h0 M123.0 518.9h0 M208.6 518.9h0 M219.3 518.9h0 M230.0 518.9h0 M230.0 529.6h0 M230.0 540.3h0 M240.7 518.9h0 M240.7 529.6h0 M240.7 540.3h0 M251.4 529.6h0 M251.4 540.3h0 M262.1 529.6h0 M262.1 540.3h0 M272.8 540.3h0 M262.1 551.0h0 M272.8 551.0h0 M283.5 540.3h0 M294.2 540.3h0 M283.5 551.0h0 M294.2 551.0h0 M304.9 540.3h0 M315.6 540.3h0 M304.9 551.0h0 M315.6 551.0h0 M326.3 540.3h0 M337.0 540.3h0 M326.3 551.0h0 M337.0 551.0h0 M347.8 540.3h0 M358.4 540.3h0 M347.8 551.0h0 M358.4 551.0h0 M369.1 540.3h0 M379.8 529.6h0 M379.8 540.3h0 M369.1 551.0h0 M390.5 529.6h0 M390.5 540.3h0 M401.2 518.9h0 M401.2 529.6h0 M401.2 540.3h0 M411.9 518.9h0 M411.9 529.6h0 M411.9 540.3h0 M422.6 518.9h0 M422.6 529.6h0 M433.3 518.9h0 M433.3 529.6h0 M444.0 518.9h0 M294.2 561.8h0 M304.9 561.8h0 M326.3 561.8h0"/></defs></svg>
            <svg viewBox="0 0 642 642" fill="none" stroke="currentColor" stroke-width="4.6" stroke-linecap="round" class="absolute top-0 -left-[12.9%] w-[44.6%] text-white dark:text-white/15"><use href="#atlas-dots"/></svg>
            <svg viewBox="0 0 642 642" fill="none" stroke="currentColor" stroke-width="4.6" stroke-linecap="round" class="absolute top-[9.6%] left-[72.8%] w-[44.6%] text-white dark:text-white/15"><use href="#atlas-dots"/></svg>
            <svg viewBox="0 0 642 642" fill="none" stroke="currentColor" stroke-width="4.6" stroke-linecap="round" class="absolute top-[56.5%] left-[77.9%] w-[36.4%] origin-top-left rotate-12 text-white dark:text-white/15"><use href="#atlas-dots"/></svg>
        </div>

        <div class="relative grid w-full max-w-[900px] [zoom:.9] overflow-hidden rounded-[32px] bg-white shadow-[0_3px_14px_rgba(29,46,30,.09),0_32px_80px_rgba(45,38,92,.18),22px_28px_0_rgba(85,80,218,.08)] ring-1 ring-[#5358B9]/20 min-[900px]:grid-cols-[1fr_385px] dark:bg-night dark:ring-white/10">

            {{-- Kiri: form --}}
            <div class="flex flex-col bg-[radial-gradient(ellipse_at_5%_100%,rgba(114,207,109,.14),transparent_45%)] px-6 py-10 min-[900px]:px-12 dark:bg-none">
                <a href="{{ route('home') }}" class="mb-7 flex items-center gap-2 self-start font-display text-lg font-bold text-[#183B22] dark:text-white">
                    <svg class="size-6 text-[#4CAF41]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3.5 20 11 4h2l7.5 16"/><path d="M7.5 14h9"/></svg>
                    ATLAS
                </a>

                @yield('intro')

                <button type="button" disabled title="Belum tersedia"
                    class="mt-8 flex h-12 w-full cursor-not-allowed items-center justify-center gap-2.5 rounded-[11px] border border-[#E5EEE2] bg-white/70 text-sm font-semibold text-[#171B17] opacity-70 dark:border-white/15 dark:bg-white/5 dark:text-white">
                    <svg class="size-[18px]" viewBox="0 0 48 48" aria-hidden="true">
                        <path fill="#FFC107" d="M43.6 20.1H42V20H24v8h11.3C33.7 32.7 29.2 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.8 1.2 8 3l5.7-5.7C34 6.1 29.3 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.1-2.6-.4-3.9z"/>
                        <path fill="#FF3D00" d="m6.3 14.7 6.6 4.8C14.7 15.1 19 12 24 12c3.1 0 5.8 1.2 8 3l5.7-5.7C34 6.1 29.3 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/>
                        <path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.2 35.1 26.7 36 24 36c-5.2 0-9.6-3.3-11.3-8l-6.5 5C9.5 39.6 16.2 44 24 44z"/>
                        <path fill="#1976D2" d="M43.6 20.1H42V20H24v8h11.3c-.8 2.2-2.2 4.2-4.1 5.6l6.2 5.2C37 39.2 44 34 44 24c0-1.3-.1-2.6-.4-3.9z"/>
                    </svg>
                    Continue with Google
                </button>

                <div class="my-6 flex items-center justify-center gap-3.5 text-[10px] text-[#667568] dark:text-slate-400">
                    <span class="h-px w-6 bg-[#7F9B82]"></span>
                    or continue with email
                    <span class="h-px w-6 bg-[#7F9B82]"></span>
                </div>

                @yield('form')
            </div>

            {{-- Kanan: panel ungu (disembunyikan di layar kecil) --}}
            <aside class="relative hidden flex-col justify-between gap-10 overflow-hidden bg-[linear-gradient(152deg,#5D56DF_0%,#4842C2_48%,#302C8C_100%)] p-[30px] text-white min-[900px]:flex">
                <div aria-hidden="true" class="absolute top-24 left-52 size-[420px] rounded-full border border-[#A4FFA8]/30"></div>
                <div aria-hidden="true" class="absolute bottom-12 -left-44 size-80 rounded-full border border-[#A4FFA8]/30"></div>

                <div class="relative flex items-center justify-between">
                    <span class="font-display text-[9px] font-bold tracking-[.16em] text-[#C8C6FF]">ATLAS / CONSOLE</span>
                    <span class="flex items-center gap-1.5 rounded-full border border-white/15 bg-white/10 px-2.5 py-1.5 text-[8px] font-bold text-[#DCFFDC]">
                        <span class="size-1.5 rounded-full bg-[#72E679] shadow-[0_0_8px_#72E679]"></span>LIVE
                    </span>
                </div>

                <div class="relative rotate-1 overflow-hidden rounded-[14px] bg-[#171B38] shadow-[0_20px_40px_rgba(18,14,73,.32)] ring-1 ring-white/15">
                    <div class="flex items-center gap-1.5 border-b border-white/10 bg-[#202544] px-3 py-3">
                        <span class="size-1.5 rounded-full bg-[#FF7A66]"></span>
                        <span class="size-1.5 rounded-full bg-[#F5C75D]"></span>
                        <span class="size-1.5 rounded-full bg-[#69D779]"></span>
                        <span class="ml-auto font-cousine text-[10px] text-[#8E94AF]">test_checkout.py</span>
                    </div>
                    <div class="space-y-2 px-4 py-4 font-cousine text-[11px]">
                        @foreach ($lines as $i => $line)
                            <div class="flex gap-3">
                                <span class="text-[#5E6689]">{{ sprintf('%02d', $i + 1) }}</span>
                                <span class="text-[#C8CCE0]"><span class="{{ $line['color'] }}">{{ $line['keyword'] }}</span> {{ $line['text'] }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="flex items-center gap-2.5 border-t border-[#6FE073]/15 bg-[#64D668]/10 px-4 py-3">
                        <span class="grid size-6 place-items-center rounded-full bg-[#62CF69] text-[10px] font-bold text-[#172E1A]">✓</span>
                        <div>
                            <div class="text-[10px] font-bold text-[#E8FFE8]">Test generated</div>
                            <div class="text-[9px] text-[#849B88]">4 steps · ready to run</div>
                        </div>
                    </div>
                </div>

                <div class="relative">
                    <p class="text-[10px] font-bold tracking-[.14em] text-[#8DE28C]">FROM USER STORY TO SELENIUM</p>
                    <h2 class="mt-3 font-display text-[28px]/[1.2] font-medium tracking-[-.03em]">Build tests at the speed of thought.</h2>
                    <p class="mt-3 text-xs text-[#B6B6DE]">AI-assisted. Human controlled.</p>
                </div>
            </aside>
        </div>
    </div>
@endsection