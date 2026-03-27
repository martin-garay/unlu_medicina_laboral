<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('internal_chat.ui.page_title') }}</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f3f0e7;
            --panel: #fffdf8;
            --panel-strong: #f4ede0;
            --border: #d2c5ae;
            --text: #1f2a24;
            --muted: #6a7169;
            --accent: #1d5c4b;
            --accent-soft: #e0efe9;
            --bot: #e7f0ea;
            --user: #1d5c4b;
            --user-text: #f7faf8;
            --danger: #8a3d2b;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Georgia, "Times New Roman", serif;
            background:
                radial-gradient(circle at top left, rgba(221, 197, 146, 0.32), transparent 32%),
                linear-gradient(180deg, #f6f2e8 0%, var(--bg) 100%);
            color: var(--text);
        }

        .page {
            max-width: 980px;
            margin: 0 auto;
            padding: 32px 20px 48px;
        }

        .header {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 24px;
        }

        .title-block h1 {
            margin: 0 0 8px;
            font-size: clamp(2rem, 4vw, 3rem);
            line-height: 1;
        }

        .title-block p,
        .meta,
        .empty,
        .input-help {
            margin: 0;
            color: var(--muted);
        }

        .meta-card,
        .chat-card {
            background: rgba(255, 253, 248, 0.9);
            border: 1px solid var(--border);
            border-radius: 22px;
            box-shadow: 0 12px 28px rgba(73, 59, 31, 0.08);
        }

        .meta-card {
            padding: 16px 18px;
            min-width: 280px;
        }

        .chat-card {
            overflow: hidden;
        }

        .chat-stream {
            display: grid;
            gap: 14px;
            padding: 24px;
            background:
                linear-gradient(180deg, rgba(255, 255, 255, 0.5), rgba(244, 237, 224, 0.85)),
                repeating-linear-gradient(
                    180deg,
                    rgba(255, 255, 255, 0.45) 0,
                    rgba(255, 255, 255, 0.45) 38px,
                    rgba(226, 217, 199, 0.3) 38px,
                    rgba(226, 217, 199, 0.3) 39px
                );
            min-height: 420px;
        }

        .bubble {
            max-width: min(720px, 100%);
            padding: 14px 16px;
            border-radius: 18px;
            border: 1px solid rgba(0, 0, 0, 0.06);
        }

        .bubble.user {
            margin-left: auto;
            background: var(--user);
            color: var(--user-text);
        }

        .bubble.bot {
            background: var(--bot);
        }

        .bubble-label {
            display: block;
            margin-bottom: 6px;
            font-size: 0.75rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: inherit;
            opacity: 0.75;
        }

        .bubble pre {
            margin: 0;
            white-space: pre-wrap;
            word-break: break-word;
            font: inherit;
        }

        .menu {
            display: grid;
            gap: 10px;
            margin-top: 12px;
        }

        .menu-option {
            padding: 10px 12px;
            border-radius: 12px;
            border: 1px solid var(--border);
            background: rgba(255, 255, 255, 0.55);
        }

        .composer {
            display: grid;
            gap: 16px;
            padding: 20px 24px 24px;
            background: var(--panel);
            border-top: 1px solid var(--border);
        }

        .composer textarea {
            width: 100%;
            min-height: 96px;
            resize: vertical;
            padding: 14px;
            border-radius: 16px;
            border: 1px solid var(--border);
            background: #fff;
            color: var(--text);
            font: inherit;
        }

        .actions,
        .quick-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        button {
            cursor: pointer;
            border: 0;
            border-radius: 999px;
            padding: 11px 16px;
            font: inherit;
        }

        .primary {
            background: var(--accent);
            color: #fff;
        }

        .secondary {
            background: var(--accent-soft);
            color: var(--accent);
        }

        .ghost {
            background: transparent;
            color: var(--danger);
            border: 1px solid rgba(138, 61, 43, 0.3);
        }

        .error-list {
            margin: 0;
            padding: 0;
            list-style: none;
            display: grid;
            gap: 8px;
            color: var(--danger);
        }

        @media (max-width: 720px) {
            .page {
                padding-inline: 14px;
            }

            .chat-stream,
            .composer {
                padding-inline: 16px;
            }

            .meta-card {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <main class="page">
        <section class="header">
            <div class="title-block">
                <p>{{ __('internal_chat.ui.eyebrow') }}</p>
                <h1>{{ __('internal_chat.ui.title') }}</h1>
                <p>{{ __('internal_chat.ui.subtitle') }}</p>
            </div>

            <aside class="meta-card">
                <p class="meta">{{ __('internal_chat.ui.participant_label') }}</p>
                <strong>{{ $participantId }}</strong>
                <p class="meta">{{ __('internal_chat.ui.scope_note') }}</p>
            </aside>
        </section>

        <section class="chat-card">
            <div class="chat-stream">
                @if ($transcript === [])
                    <p class="empty">{{ __('internal_chat.ui.empty_state') }}</p>
                @endif

                @foreach ($transcript as $entry)
                    <article class="bubble {{ $entry['actor'] === 'user' ? 'user' : 'bot' }}">
                        <span class="bubble-label">
                            {{ $entry['actor'] === 'user' ? __('internal_chat.ui.user_label') : __('internal_chat.ui.bot_label') }}
                        </span>

                        @if (($entry['text'] ?? null) !== null)
                            <pre>{{ $entry['text'] }}</pre>
                        @endif

                        @if (($entry['type'] ?? null) === 'menu' && isset($entry['menu']['buttons']))
                            <div class="menu">
                                @foreach ($entry['menu']['buttons'] as $button)
                                    <div class="menu-option">{{ $button['title'] ?? $button['id'] }}</div>
                                @endforeach
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>

            <div class="composer">
                @if ($errors->any())
                    <ul class="error-list">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif

                @if ($activeMenu !== null && isset($activeMenu['buttons']))
                    <div class="quick-actions">
                        @foreach ($activeMenu['buttons'] as $button)
                            <form method="post" action="{{ route('internal-chat.console.send') }}">
                                @csrf
                                <input type="hidden" name="button_id" value="{{ $button['id'] }}">
                                <input type="hidden" name="button_title" value="{{ $button['title'] ?? $button['id'] }}">
                                <button class="secondary" type="submit">
                                    {{ $button['title'] ?? $button['id'] }}
                                </button>
                            </form>
                        @endforeach
                    </div>
                @endif

                <form method="post" action="{{ route('internal-chat.console.send') }}">
                    @csrf
                    <label for="text">{{ __('internal_chat.ui.input_label') }}</label>
                    <textarea id="text" name="text" placeholder="{{ __('internal_chat.ui.input_placeholder') }}">{{ old('text') }}</textarea>
                    <p class="input-help">{{ __('internal_chat.ui.input_help') }}</p>

                    <div class="actions">
                        <button class="primary" type="submit">{{ __('internal_chat.ui.send_action') }}</button>
                    </div>
                </form>

                <form method="post" action="{{ route('internal-chat.console.reset') }}">
                    @csrf
                    <button class="ghost" type="submit">{{ __('internal_chat.ui.reset_action') }}</button>
                </form>
            </div>
        </section>
    </main>
</body>
</html>
