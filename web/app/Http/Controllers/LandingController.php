<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class LandingController extends Controller
{
    public function __invoke(): View
    {
        return view('landing', [
            'steps' => [
                ['label' => 'Step 01: Write Your User Story', 'body' => 'Describe your requirements and acceptance criteria in Gherkin format, then add the target application URL, along with login details if your application requires authentication.'],
                ['label' => 'Step 02: Generate Your Test Script', 'body' => "ATLAS analyzes the target page's DOM elements, processes your requirements using an LLM, and validates the extracted information before generating a Selenium script."],
                ['label' => 'Step 03: Review and Run Your Script', 'body' => 'Review the generated script, run it against your target application, and check the execution status along with failure details if a test does not pass.'],
            ],
            'scenarios' => [
                ['title' => 'Menambahkan produk', 'url' => 'https://toko.example/products/tas-kanvas'],
                ['title' => 'Menghapus produk', 'url' => 'https://toko.example/cart'],
            ],
        ]);
    }
}
