<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Exam Portal') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            :root {
                --ink: #111827;
                --muted: #64748b;
                --panel: #ffffff;
                --line: #dbe3ee;
                --primary: #0f766e;
                --primary-dark: #115e59;
                --accent: #b45309;
            }

            body {
                margin: 0;
                min-height: 100vh;
                background:
                    linear-gradient(135deg, rgba(15, 118, 110, 0.14), transparent 34%),
                    linear-gradient(315deg, rgba(180, 83, 9, 0.13), transparent 30%),
                    #f8fafc;
                font-family: Figtree, ui-sans-serif, system-ui, sans-serif;
                color: var(--ink);
            }

            .welcome-shell {
                min-height: 100vh;
                display: grid;
                grid-template-rows: auto 1fr;
            }

            .welcome-nav {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
                width: min(1120px, calc(100% - 32px));
                margin: 0 auto;
                padding: 1.25rem 0;
            }

            .brand {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                font-weight: 700;
            }

            .brand-mark {
                display: grid;
                place-items: center;
                width: 42px;
                height: 42px;
                border-radius: 12px;
                background: #123c3a;
                color: white;
            }

            .nav-actions {
                display: flex;
                align-items: center;
                gap: 0.75rem;
            }

            .btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-height: 42px;
                padding: 0 1rem;
                border-radius: 8px;
                border: 1px solid var(--line);
                font-size: 0.92rem;
                font-weight: 600;
                text-decoration: none;
            }

            .btn-primary {
                border-color: var(--primary);
                background: var(--primary);
                color: white;
                box-shadow: 0 14px 30px rgba(15, 118, 110, 0.2);
            }

            .btn-plain {
                background: rgba(255, 255, 255, 0.78);
                color: var(--ink);
            }

            .hero {
                display: grid;
                grid-template-columns: minmax(0, 0.96fr) minmax(360px, 1.04fr);
                align-items: center;
                gap: 3rem;
                width: min(1120px, calc(100% - 32px));
                margin: 0 auto;
                padding: 3rem 0 4rem;
            }

            .hero h1 {
                max-width: 760px;
                margin: 0;
                font-size: clamp(2.4rem, 6vw, 4.9rem);
                line-height: 0.98;
                letter-spacing: 0;
            }

            .hero p {
                max-width: 580px;
                margin: 1.4rem 0 0;
                color: var(--muted);
                font-size: 1.08rem;
                line-height: 1.7;
            }

            .hero-actions {
                display: flex;
                flex-wrap: wrap;
                gap: 0.8rem;
                margin-top: 1.8rem;
            }

            .console {
                border: 1px solid rgba(15, 23, 42, 0.1);
                border-radius: 8px;
                background: rgba(255, 255, 255, 0.92);
                box-shadow: 0 28px 70px rgba(15, 23, 42, 0.14);
                overflow: hidden;
            }

            .console-top {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 1rem 1.1rem;
                border-bottom: 1px solid var(--line);
                background: #f8fafc;
            }

            .console-title {
                font-size: 0.82rem;
                font-weight: 700;
                color: #475569;
                text-transform: uppercase;
            }

            .console-body {
                padding: 1.1rem;
            }

            .metric-row {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 0.8rem;
            }

            .metric {
                border: 1px solid var(--line);
                border-radius: 8px;
                padding: 0.9rem;
                background: white;
            }

            .metric span {
                display: block;
                color: var(--muted);
                font-size: 0.76rem;
            }

            .metric strong {
                display: block;
                margin-top: 0.35rem;
                font-size: 1.45rem;
            }

            .exam-card {
                margin-top: 1rem;
                border: 1px solid var(--line);
                border-radius: 8px;
                padding: 1rem;
                background: white;
            }

            .exam-card h2 {
                margin: 0;
                font-size: 1.05rem;
            }

            .progress {
                height: 9px;
                margin: 1rem 0 0.9rem;
                border-radius: 999px;
                background: #e2e8f0;
                overflow: hidden;
            }

            .progress span {
                display: block;
                width: 72%;
                height: 100%;
                background: linear-gradient(90deg, var(--primary), var(--accent));
            }

            .answer-list {
                display: grid;
                gap: 0.65rem;
                margin-top: 0.9rem;
            }

            .answer {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
                border: 1px solid var(--line);
                border-radius: 8px;
                padding: 0.75rem 0.85rem;
                color: #334155;
                font-size: 0.92rem;
            }

            .answer.active {
                border-color: rgba(15, 118, 110, 0.45);
                background: #ecfdf5;
                color: #0f513f;
            }

            @media (max-width: 860px) {
                .hero {
                    grid-template-columns: 1fr;
                    padding-top: 2rem;
                }

                .console {
                    max-width: 620px;
                }
            }

            @media (max-width: 540px) {
                .welcome-nav {
                    align-items: flex-start;
                    flex-direction: column;
                }

                .nav-actions,
                .hero-actions {
                    width: 100%;
                }

                .btn {
                    flex: 1;
                }

                .metric-row {
                    grid-template-columns: 1fr;
                }
            }
        </style>
    </head>
    <body>
        <div class="welcome-shell">
            <header class="welcome-nav">
                <div class="brand">
                    <div class="brand-mark">
                        <x-application-logo class="h-6 w-6 fill-current" />
                    </div>
                    <span>{{ config('app.name', 'Exam Portal') }}</span>
                </div>

                @if (Route::has('login'))
                    <nav class="nav-actions">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="btn btn-primary">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-plain">Log in</a>
                        @endauth
                    </nav>
                @endif
            </header>

            <main class="hero">
                <section>
                    <h1>Online Exam Portal</h1>
                    <p>
                        A focused workspace for creating exams, managing question banks, taking timed tests, and reviewing performance.
                    </p>

                    <div class="hero-actions">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="btn btn-primary">Open Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-primary">Start</a>
                        @endauth
                    </div>
                </section>

                <section class="console" aria-label="Portal preview">
                    <div class="console-top">
                        <div class="console-title">Exam Session</div>
                        <div class="console-title">18:42 left</div>
                    </div>

                    <div class="console-body">
                        <div class="metric-row">
                            <div class="metric">
                                <span>Questions</span>
                                <strong>20</strong>
                            </div>
                            <div class="metric">
                                <span>Pass Mark</span>
                                <strong>60%</strong>
                            </div>
                            <div class="metric">
                                <span>Score</span>
                                <strong>15</strong>
                            </div>
                        </div>

                        <div class="exam-card">
                            <h2>Question 8 of 20</h2>
                            <div class="progress"><span></span></div>
                            <p style="margin: 0; color: #475569;">Which option best matches the question?</p>

                            <div class="answer-list">
                                <div class="answer">Option A <span></span></div>
                                <div class="answer active">Option B <span>Selected</span></div>
                                <div class="answer">Option C <span></span></div>
                            </div>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </body>
</html>
