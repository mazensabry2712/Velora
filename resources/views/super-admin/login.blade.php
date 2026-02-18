<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Super Admin Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-600 min-h-screen flex items-center justify-center p-4">

    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl p-8 w-full max-w-md">
        <!-- Logo/Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-indigo-100 dark:bg-indigo-900 rounded-full mb-4">
                <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">Super Admin</h1>
            <p class="text-slate-600 dark:text-slate-400">الدخول لل��حة التحكم الرئيسية</p>
        </div>

        <!-- Error Messages -->
        @if ($errors->any())
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-400 px-4 py-3 rounded-lg mb-6">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Login Form -->
        <form method="POST" action="{{ route('super-admin.login.post') }}" class="space-y-6">
            @csrf

            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    البريد الإلكتروني
                </label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 transition"
                    placeholder="admin@example.com"
                >
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    كلمة المرور
                </label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 transition"
                    placeholder="••••••••"
                >
            </div>

            <!-- Remember Me -->
            <div class="flex items-center">
                <input
                    type="checkbox"
                    id="remember"
                    name="remember"
                    class="w-4 h-4 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500"
                >
                <label for="remember" class="mr-2 text-sm text-slate-700 dark:text-slate-300">
                    تذكرني
                </label>
            </div>

            <!-- Submit Button -->
            <button
                type="submit"
                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 rounded-lg transition duration-200 transform hover:scale-[1.02] shadow-lg hover:shadow-xl"
            >
                دخول
            </button>
        </form>

        <!-- Footer -->
        <div class="mt-8 text-center text-sm text-slate-500 dark:text-slate-400">
            <p>© 2026 Booking SaaS System</p>
        </div>
    </div>

</body>
</html>
