<div class="flex flex-wrap items-center gap-4 px-4 py-3 bg-gray-50 dark:bg-gray-800 rounded-lg mb-4 border border-gray-200 dark:border-gray-700">
    <!-- Urgent + Assigned to You -->
    <div class="flex items-center gap-4">  <!-- Increased from gap-2.5 to gap-4 -->
        <div class="w-5 h-5 rounded-md bg-purple dark:bg-purple"></div>
        <span class="text-sm font-medium text-gray-700 dark:text-white">
           {{__('Urgent ticket assigned to you')}}
        </span>
    </div>

    <!-- Urgent Ticket -->
    <div class="flex items-center gap-4">  <!-- Increased from gap-2.5 to gap-4 -->
        <div class="w-5 h-5 rounded-md dark:bg-danger bg-danger"></div>
        <span class="text-sm font-medium text-gray-700 dark:text-white">
            {{__('Urgent ticket')}}
        </span>
    </div>

    <!-- Assigned to You -->
    <div class="flex items-center gap-4">  <!-- Increased from gap-2.5 to gap-4 -->
        <div class="w-5 h-5 rounded-md bg-success dark:bg-success"></div>
        <span class="text-sm font-medium text-gray-700 dark:text-white">
            {{__('Ticket assigned to you')}}
        </span>
    </div>

    <!-- Completed Service -->
    <div class="flex items-center gap-4">  <!-- Increased from gap-2.5 to gap-4 -->
        <div class="w-5 h-5 rounded-md dark:bg-gray-500/20 bg-gray-200"></div>
        <span class="text-sm font-medium text-gray-700 dark:text-white">
           {{__('Requested Tickets')}}
        </span>
    </div>
</div>
