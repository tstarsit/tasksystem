<div id="sidebar" class="fixed left-0 top-0 w-64 h-screen bg-gray-800 text-white transform -translate-x-64 transition-transform duration-300">
    <div class="p-4 flex justify-between items-center border-b border-gray-700">
        <strong>Menu</strong>
        <button id="closeSidebar" class="text-white">
            ✖
        </button>
    </div>
    <ul class="p-4 space-y-2">
        @foreach($allTests as $test)
        <li><a href="{{$test->url}}" class="block p-2 hover:bg-gray-700 rounded">{{\Carbon\Carbon::parse($test->result_date)->format('Y-m-d')}}</a></li>
        @endforeach

    </ul>
</div>

<!-- Main Content -->

    <button id="openSidebar" class="m-4 p-2 h-12 w-[3rem] bg-gray-800 text-white rounded">☰</button>

@push('js')
    <script>
        const sidebar = document.getElementById("sidebar");
        const openBtn = document.getElementById("openSidebar");
        const closeBtn = document.getElementById("closeSidebar");

        openBtn.addEventListener("click", () => {
            sidebar.classList.remove("-translate-x-64");
        });

        closeBtn.addEventListener("click", () => {
            sidebar.classList.add("-translate-x-64");
        });
    </script>
@endpush
