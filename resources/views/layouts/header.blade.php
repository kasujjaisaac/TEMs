<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>{{ $page_title ?? 'Onyx Hub' }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}?v={{ filemtime(public_path('assets/css/style.css')) }}">
    <script>
        (function () {
            try {
                if (localStorage.getItem('onyx_sidebar_collapsed') === '1') {
                    document.documentElement.classList.add('sidebar-collapsed-preload');
                }
            } catch (error) {}
        })();
    </script>
</head>
<body>
<script>
    (function () {
        if (document.documentElement.classList.contains('sidebar-collapsed-preload')) {
            document.body.classList.add('sidebar-collapsed');
        }
    })();
</script>

@include('layouts.sidebar')

<div id="main">
