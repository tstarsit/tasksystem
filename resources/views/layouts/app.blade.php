<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Responsive sidebar template with sliding effect and dropdown menu based on bootstrap 3">
    @vite('resources/css/app.css')
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class=" h-screen">
<!-- Sidebar -->
@include('layouts.partials.sidebar')

<!-- Overlay for mobile -->
<div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-20 hidden"></div>

<!-- Main Content -->
<main id="mainContent" class="flex-1 overflow-y-auto">
    @yield('content')
</main>

<!-- jQuery for Sidebar Toggle -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
@stack('js')
</body>
</html>
