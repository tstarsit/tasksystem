@extends('layouts.app')

@section('content')

    <div class="container mx-auto p-5">
        <!-- Header Section with Toggle Button -->
        <header class="border-b-2 border-gray-300 pb-4 mb-4 flex items-center justify-between">



            <figure class="w-full">
                <img src="{{ asset('assets/img').'/'.$hospital->header }}" alt="Lab Logo" class="w-full">
            </figure>
        </header>

        <!-- Personal Data Section -->
        <section class="mb-4 mt-4 px-3">

            <div class=" mb-4">
                @php
                    $url = $LabTrans->url;
$parsedUrl = parse_url($url, PHP_URL_QUERY); // Get the query string (r=e7b0a6c82347eb1)
parse_str($parsedUrl, $queryParams); // Convert to array
$code = $queryParams['r'] ?? null;
                @endphp

                <a href="{{ route('report.download', ['code' => $code]) }}"
                   class="bg-white p-2 rounded-md shadow-xl border-[#dfdfe2] border hover:shadow-lg inline-flex items-center justify-center">
                    <i class="fas fa-file-pdf text-xl text-red-600"></i>
                </a>




            </div>
            <h2 class="text-center text-lg font-bold py-2 bg-gray-300 underline my-4">PERSONAL DATA</h2>
            <article class="border text-sm rounded-xl mb-2">
                <div class="grid grid-cols-2 sm:grid-cols-4 bg-gray-100 p-2 rounded-t-xl">
                    <strong>Name</strong>
                    <span>{{$LabTrans->patient_desc}}</span>
                    <strong>Age</strong>
                    <span>{{$LabTrans->patient_age}}</span>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 p-2">
                    <strong>Gender</strong>
                    <span>{{$LabTrans->gender==1?'Male':'Female'}}</span>
                    <strong>Order No.</strong>
                    <span>{{$LabTrans->request_no}}</span>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 bg-gray-100 p-2 rounded-b-xl">
                    <strong>Collected</strong>
                    <time datetime="{{ $LabTrans->result_date }}">
                        {{ \Carbon\Carbon::parse($LabTrans->result_date)->format('Y-m-d') }}
                    </time>
                    <strong>Doctor Name</strong>
                    <span>{{$LabTrans->emp_desc}}</span>
                </div>
            </article>
        </section>

        <!-- Examination Data -->
        <section>
            <h2 class="text-center my-4 text-lg font-bold py-2 bg-gray-300 underline">EXAMINATION DATA</h2>

            @foreach($LabTransDtls as $group => $tests)
                <header class="bg-sky-200 text-center my-3 py-2 font-bold">
                    <h3>{{ $group }}</h3>
                </header>

                <div class="grid grid-cols-3 bg-gray-100 font-medium">
                    <span class="text-center border p-2 text-xs sm:text-sm md:text-base lg:text-lg xl:text-xl" style="border-color: #dfdfe2;">Test</span>
                    <span class="text-center border p-2 text-xs sm:text-sm md:text-base lg:text-lg xl:text-xl" style="border-color: #dfdfe2;">Result</span>
                    <span class="text-center border p-2 text-xs sm:text-sm md:text-base lg:text-lg xl:text-xl" style="border-color: #dfdfe2;">Reference Values</span>
                </div>

                @foreach($tests as $test)
                    <article class="grid grid-cols-3 border border-[#dfdfe2] rounded-md my-2 hover:border-blue-500 transition duration-200">
                        <span class="border border-[#dfdfe2] p-1 sm:p-2 md:p-3 text-center break-words text-xs sm:text-sm md:text-base lg:text-lg xl:text-xl font-medium hover:border-blue-500">{{ $test->LAB_ITEM_DESC }}</span>
                        <span class="border border-[#dfdfe2] p-1 sm:p-2 md:p-3 text-center break-words text-xs sm:text-sm md:text-base lg:text-lg xl:text-xl hover:border-blue-500">{{ $test->RESULT_L }} {{ $test->UNIT_DESC }}</span>
                        <span class="border border-[#dfdfe2] p-1 sm:p-2 md:p-3 text-center break-words text-xs sm:text-sm md:text-base lg:text-lg xl:text-xl hover:border-blue-500">{{ $test->NORMAL_RESULT }}</span>
                    </article>
                @endforeach
            @endforeach
        </section>
    </div>
    <footer>
                @if(!empty($hospital->footer))
                    <figure class="mb-4">
                        <img src="{{ asset('assets/img').'/'.$hospital->footer }}" alt="Lab Footer">
                    </figure>
                @endif
    </footer>

@endsection

