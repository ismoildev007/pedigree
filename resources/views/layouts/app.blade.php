<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Family Tree (Shajara)')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Tree Layout Styles */
        .tree-container {
            width: 100%;
            height: 70vh; /* Fixed height relative to viewport */
            text-align: center;
            overflow: hidden;
            padding: 40px;
            cursor: grab;
            position: relative;
            background-color: #fcfcfc;
            border-radius: 8px;
            border: 1px solid #eee;
        }
        .tree-container:active {
            cursor: grabbing;
        }
        .tree {
            display: inline-block;
            white-space: nowrap;
            transform-origin: 0 0;
            transition: transform 0.1s ease-out;
            user-select: none;
        }
        .couple-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin: 0 auto;
        }
        .spouse-connector {
            font-size: 1.2rem;
            color: #e83e8c;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.2); opacity: 0.7; }
            100% { transform: scale(1); opacity: 1; }
        }
        /* Responsive Tree for Mobile */
        @media (max-width: 768px) {
            .tree-container {
                height: 75vh;
                padding: 20px 10px;
            }
            .person-node {
                width: 130px;
                padding: 8px 4px;
                font-size: 0.75rem;
                min-width: 130px;
                margin: 0 5px;
            }
            .person-node i.fa-2x {
                font-size: 1rem;
            }
            .person-node img {
                width: 30px !important;
                height: 30px !important;
            }
            .person-node h6 {
                font-size: 0.8rem;
                white-space: normal;
                line-height: 1.2;
            }
            .person-node .btn-sm {
                padding: 0.2rem 0.3rem;
                font-size: 0.7rem;
            }
            .couple-wrapper {
                gap: 5px;
            }
            .spouse-connector {
                font-size: 0.9rem;
            }
            /* Zoom Controls Mobile */
            .zoom-controls {
                bottom: 10px;
                right: 10px;
            }
            .zoom-btn {
                width: 35px;
                height: 35px;
            }
            
            /* Increase vertical spacing on mobile to avoid overlapping lines */
            .tree ul { padding-top: 30px; }
            .tree li::before { height: 30px; top: -30px; }
            .tree li::after { top: -30px; }
            .tree ul::before { height: 30px; top: 0; }
        }
        /* Zoom Controls */
        .zoom-controls {
            position: absolute;
            bottom: 20px;
            right: 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            z-index: 1000;
        }
        .zoom-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: white;
            border: 1px solid #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: all 0.2s;
        }
        .zoom-btn:hover {
            background: #f8f9fa;
            transform: scale(1.1);
        }
        .tree ul {
            padding-top: 20px;
            position: relative;
            transition: all 0.5s;
            display: flex;
            justify-content: center;
        }
        .tree li {
            text-align: center;
            list-style-type: none;
            position: relative;
            padding: 20px 5px 0 5px;
            transition: all 0.5s;
        }
        .tree li::before, .tree li::after {
            content: '';
            position: absolute;
            top: 0;
            right: 50%;
            border-top: 1px solid #ccc;
            width: 50%;
            height: 20px;
        }
        .tree li::after {
            right: auto;
            left: 50%;
            border-left: 1px solid #ccc;
        }
        .tree li:only-child::after, .tree li:only-child::before {
            display: none;
        }
        .tree li:only-child {
            padding-top: 0;
        }
        .tree li:first-child::before, .tree li:last-child::after {
            border: 0 none;
        }
        .tree li:last-child::before {
            border-right: 1px solid #ccc;
            border-radius: 0 5px 0 0;
        }
        .tree li:first-child::after {
            border-radius: 5px 0 0 0;
        }
        .tree ul ul::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            border-left: 1px solid #ccc;
            width: 0;
            height: 20px;
        }
        .tree li div.person-node {
            border: 1px solid #ccc;
            padding: 10px;
            text-decoration: none;
            color: #666;
            font-family: arial, verdana, tahoma;
            font-size: 11px;
            display: inline-block;
            border-radius: 5px;
            background-color: #fff;
            transition: all 0.5s;
            min-width: 150px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: relative;
        }
        .tree li div.person-node:hover {
            background: #eef;
            color: #000;
            border: 1px solid #94a0b4;
        }
        .gender-male { border-top: 3px solid #007bff !important; }
        .gender-female { border-top: 3px solid #e83e8c !important; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand" href="{{ route('families.index') }}">
                <i class="fas fa-tree me-2"></i> Shajara
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navContent">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navContent">
                <ul class="navbar-nav ms-auto">
                    @auth
                        <li class="nav-item">
                            <span class="nav-link text-white me-3">
                                <i class="fas fa-user me-1"></i> {{ auth()->user()->name }}
                            </span>
                        </li>
                        <li class="nav-item">
                            <form action="{{ route('logout') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-link nav-link text-white" style="text-decoration: none;">
                                    <i class="fas fa-sign-out-alt me-1"></i> Logout
                                </button>
                            </form>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('register') }}">Register</a>
                        </li>
                    @endauth
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @yield('scripts')
</body>
</html>
