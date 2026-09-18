<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    <title>
        TCH Hospital ERP
    </title>


    <link
        rel="icon"
        href="{{ asset('images/TCH_favicon.png') }}"
    >


    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])


    <style>

        body {
            margin: 0;
            font-family:
                Inter,
                ui-sans-serif,
                system-ui,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                sans-serif;
        }


        .login-bg {

            background:
                radial-gradient(
                    circle at 5% 10%,
                    rgba(14,165,233,.10),
                    transparent 28%
                ),
                radial-gradient(
                    circle at 95% 90%,
                    rgba(13,148,136,.10),
                    transparent 30%
                ),
                linear-gradient(
                    135deg,
                    #f8fafc 0%,
                    #eef6fa 50%,
                    #f8fafc 100%
                );

        }


        .hero-overlay {

            background:
                linear-gradient(
                    180deg,
                    rgba(6,34,64,.12) 0%,
                    rgba(6,34,64,.22) 45%,
                    rgba(3,22,41,.88) 100%
                );

        }


        .login-shadow {

            box-shadow:
                0 30px 80px rgba(15, 23, 42, .14);

        }


        .glass-decoration {

            position: absolute;
            border-radius: 9999px;
            background: rgba(14,165,233,.06);
            filter: blur(1px);

        }

    </style>

</head>


<body class="login-bg text-slate-800">


<div class="relative flex min-h-screen items-center justify-center overflow-hidden px-4 py-8 sm:px-6 lg:px-8">


    {{-- DECORATIVE BACKGROUND --}}
    <div
        class="glass-decoration -left-24 top-12 h-80 w-80"
        aria-hidden="true"
    ></div>

    <div
        class="glass-decoration -right-24 bottom-0 h-96 w-96"
        aria-hidden="true"
    ></div>


    {{-- PAGE BRAND --}}
    <div class="absolute left-8 top-8 hidden xl:block">

        <div class="text-xs font-semibold uppercase tracking-[0.35em] text-slate-500">
            Tura Christian Hospital
        </div>

        <div class="mt-3 h-0.5 w-12 bg-teal-500"></div>

        <div class="mt-4 text-xs uppercase leading-6 tracking-[0.30em] text-slate-400">
            Faith<br>
            Care<br>
            Service
        </div>

    </div>


    {{-- MAIN LOGIN CONTAINER --}}
    <div class="login-shadow grid w-full max-w-6xl overflow-hidden rounded-[28px] border border-white/80 bg-white lg:grid-cols-[1.1fr_0.9fr]">


        {{-- ===================================================== --}}
        {{-- LEFT HERO --}}
        {{-- ===================================================== --}}
        <div class="relative hidden min-h-[720px] overflow-hidden lg:block">


            <img
                src="{{ asset('images/tch2.png') }}"
                alt="Tura Christian Hospital"
                class="absolute inset-0 h-full w-full object-cover"
            >


            <div class="hero-overlay absolute inset-0"></div>


            {{-- HERO CONTENT --}}
            <div class="relative z-10 flex h-full flex-col justify-between p-10 text-white">


                {{-- TOP --}}
                <div>

                    <div class="text-xs font-medium uppercase tracking-[0.3em] text-white/80">
                        People &nbsp; | &nbsp; Care &nbsp; | &nbsp; Community
                    </div>


                    <h1 class="mt-8 max-w-md text-5xl font-light leading-tight">
                        Healing Together
                    </h1>


                    <p class="mt-3 text-2xl font-light text-white/90">
                        for a Healthier Tomorrow
                    </p>


                    <div class="mt-6 h-1 w-16 rounded bg-teal-400"></div>

                </div>



                {{-- LOWER CONTENT --}}
                <div>


                    {{-- STAFF IMAGE --}}
                    <div class="mb-7 overflow-hidden rounded-2xl border border-white/20 bg-white/10 backdrop-blur-sm">

                        <div class="flex items-center gap-4 p-3">

                            <img
                                src="{{ asset('images/team.jpg') }}"
                                alt="Tura Christian Hospital team"
                                class="h-20 w-32 rounded-xl object-cover"
                            >

                            <div>

                                <div class="text-sm font-semibold">
                                    People at the Centre
                                </div>

                                <div class="mt-1 text-xs leading-5 text-white/75">
                                    Caring for every life and strengthening
                                    healthier communities.
                                </div>

                            </div>

                        </div>

                    </div>


                    {{-- VALUES --}}
                    <div class="grid grid-cols-3 gap-4 border-t border-white/20 pt-6">


                        <div>

                            <div class="text-lg">
                                ♡
                            </div>

                            <div class="mt-2 text-sm font-semibold">
                                People
                            </div>

                            <div class="text-xs text-white/65">
                                at the centre
                            </div>

                        </div>


                        <div>

                            <div class="text-lg">
                                ◉
                            </div>

                            <div class="mt-2 text-sm font-semibold">
                                Care
                            </div>

                            <div class="text-xs text-white/65">
                                for every life
                            </div>

                        </div>


                        <div>

                            <div class="text-lg">
                                ◇
                            </div>

                            <div class="mt-2 text-sm font-semibold">
                                Stronger
                            </div>

                            <div class="text-xs text-white/65">
                                communities
                            </div>

                        </div>


                    </div>

                </div>

            </div>

        </div>



        {{-- ===================================================== --}}
        {{-- LOGIN PANEL --}}
        {{-- ===================================================== --}}
        <div class="relative flex min-h-[680px] items-center bg-white px-7 py-10 sm:px-12 lg:px-14">


            {{-- FAINT WATERMARK --}}
            <img
                src="{{ asset('images/TCH_favicon.png') }}"
                alt=""
                aria-hidden="true"
                class="pointer-events-none absolute right-8 top-8 h-28 w-28 object-contain opacity-[0.045]"
            >


            <div class="mx-auto w-full max-w-md">


                {{-- LOGO --}}
                <div class="text-center">

                    <img
                        src="{{ asset('images/TCH_favicon.png') }}"
                        alt="Tura Christian Hospital"
                        class="mx-auto h-24 w-24 object-contain"
                    >


                    <h2 class="mt-4 text-2xl font-bold tracking-tight text-[#0b326f]">
                        TCH Hospital ERP
                    </h2>


                    <p class="mt-1 text-xs font-semibold uppercase tracking-[0.28em] text-slate-500">
                        Tura Christian Hospital
                    </p>


                    <div class="mx-auto mt-4 h-0.5 w-20 bg-teal-500"></div>

                </div>



                {{-- WELCOME --}}
                <div class="mt-9 text-center">

                    <h1 class="text-2xl font-bold text-slate-900">
                        Welcome Back
                    </h1>

                    <p class="mt-2 text-sm text-slate-500">
                        Sign in to access the Hospital ERP system
                    </p>

                    <p class="mt-1 text-xs font-medium tracking-wide text-slate-400">
                        Together for Better Care
                    </p>

                </div>



                {{-- SESSION STATUS --}}
                @if (session('status'))

                    <div class="mt-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">

                        {{ session('status') }}

                    </div>

                @endif



                {{-- VALIDATION ERRORS --}}
                @if ($errors->any())

                    <div class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3">

                        <ul class="list-inside list-disc text-sm text-red-700">

                            @foreach ($errors->all() as $error)

                                <li>
                                    {{ $error }}
                                </li>

                            @endforeach

                        </ul>

                    </div>

                @endif



                {{-- LOGIN FORM --}}
                <form
                    method="POST"
                    action="{{ route('login') }}"
                    class="mt-8 space-y-5"
                >

                    @csrf


                    {{-- EMAIL --}}
                    <div>

                        <label
                            for="email"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Email
                        </label>


                        <div class="relative">

                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">

                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                >

                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M3 8l9 6 9-6M5 6h14a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z"
                                    />

                                </svg>

                            </div>


                            <input
                                id="email"
                                name="email"
                                type="email"
                                value="{{ old('email') }}"
                                required
                                autofocus
                                autocomplete="username"
                                placeholder="name@tchospital.org"
                                class="block w-full rounded-xl border-slate-300 py-3 pl-12 pr-4 text-sm shadow-sm transition focus:border-blue-600 focus:ring-blue-600"
                            >

                        </div>

                    </div>



                    {{-- PASSWORD --}}
                    <div>

                        <label
                            for="password"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Password
                        </label>


                        <div class="relative">


                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">

                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                >

                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M12 11V7a4 4 0 118 0v4M6 11h12v9H6v-9z"
                                    />

                                </svg>

                            </div>


                            <input
                                id="password"
                                name="password"
                                type="password"
                                required
                                autocomplete="current-password"
                                placeholder="Enter your password"
                                class="block w-full rounded-xl border-slate-300 py-3 pl-12 pr-12 text-sm shadow-sm transition focus:border-blue-600 focus:ring-blue-600"
                            >


                            <button
                                type="button"
                                id="togglePassword"
                                class="absolute inset-y-0 right-0 flex items-center px-4 text-slate-400 hover:text-slate-700"
                                aria-label="Show password"
                            >

                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                >

                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6z"
                                    />

                                    <circle
                                        cx="12"
                                        cy="12"
                                        r="2.5"
                                    />

                                </svg>

                            </button>

                        </div>

                    </div>



                    {{-- REMEMBER / FORGOT --}}
                    <div class="flex items-center justify-between gap-4">


                        <label class="flex items-center gap-2 text-sm text-slate-600">

                            <input
                                type="checkbox"
                                name="remember"
                                class="rounded border-slate-300 text-blue-700 focus:ring-blue-600"
                            >

                            Remember me

                        </label>


                        @if (Route::has('password.request'))

                            <a
                                href="{{ route('password.request') }}"
                                class="text-sm font-medium text-blue-700 hover:text-blue-900 hover:underline"
                            >
                                Forgot your password?
                            </a>

                        @endif

                    </div>



                    {{-- LOGIN BUTTON --}}
                    <button
                        type="submit"
                        class="group flex w-full items-center justify-center gap-3 rounded-xl bg-gradient-to-r from-[#0b326f] to-[#07579b] px-5 py-3.5 text-sm font-semibold text-white shadow-lg shadow-blue-900/15 transition hover:-translate-y-0.5 hover:shadow-xl"
                    >

                        Log In

                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-4 w-4 transition group-hover:translate-x-1"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M5 12h14M13 6l6 6-6 6"
                            />

                        </svg>

                    </button>


                </form>



                {{-- SECURITY FOOTER --}}
                <div class="mt-8 flex items-center gap-4">

                    <div class="h-px flex-1 bg-slate-200"></div>

                    <div class="whitespace-nowrap text-xs text-slate-400">
                        Secure • Reliable • Better Care
                    </div>

                    <div class="h-px flex-1 bg-slate-200"></div>

                </div>


                <div class="mt-5 text-center text-xs text-slate-400">

                    Tura Christian Hospital

                    <span class="mx-1">
                        •
                    </span>

                    Estd. 1908

                </div>


            </div>

        </div>

    </div>


    {{-- BOTTOM --}}
    <div class="absolute bottom-5 hidden text-xs text-slate-400 lg:block">

        TCH Hospital ERP
        <span class="mx-2">|</span>
        Tura Christian Hospital

    </div>


</div>



<script>

    document.addEventListener('DOMContentLoaded', function () {

        const togglePassword =
            document.getElementById('togglePassword');

        const password =
            document.getElementById('password');


        if (togglePassword && password) {

            togglePassword.addEventListener('click', function () {

                password.type =
                    password.type === 'password'
                        ? 'text'
                        : 'password';

            });

        }

    });

</script>


</body>

</html>