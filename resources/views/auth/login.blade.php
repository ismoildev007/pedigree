<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

<div class="w-full max-w-md bg-white shadow-lg rounded-2xl p-8">
    <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Login</h2>

    <form action="{{ route('authenticate') }}" method="POST" class="space-y-5">
        @csrf

        <!-- PHONE INPUT -->
        <div>
            <input
                id="phone"
                type="text"
                name="phone"
                placeholder="Phone"
                required
                class="w-full px-4 py-3 rounded-xl border border-gray-300
                           focus:ring-2 focus:ring-blue-500 focus:outline-none"
            >
        </div>

        <!-- LOGIN BUTTON -->
        <button
            type="submit"
            class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-xl
                       font-semibold transition duration-200"
        >
            Login
        </button>
    </form>
</div>

</body>
</html>
