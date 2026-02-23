<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name') }} - Authorization</title>
    @vite('resources/css/app.css')
    <script>
        // Theme evaluation script - similar to theme.ts
        (function() {
            // Get theme setting from localStorage, default to 'system'
            const themeSetting = localStorage.getItem('theme') || 'system';

            // Get user's preferred color scheme
            const preferredColorScheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';

            // Determine current theme
            let currentTheme;
            if (themeSetting === 'system') {
                // If user has no preference, default to dark (matching theme.ts logic)
                currentTheme = preferredColorScheme === 'no-preference' ? 'dark' : preferredColorScheme;
            } else {
                currentTheme = themeSetting;
            }

            // Apply theme class to html element
            document.documentElement.classList.remove('light', 'dark');
            document.documentElement.classList.add(currentTheme);

            // Listen for changes in color scheme preference
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
                if (localStorage.getItem('theme') === 'system') {
                    const newTheme = e.matches ? 'dark' : 'light';
                    document.documentElement.classList.remove('light', 'dark');
                    document.documentElement.classList.add(newTheme);
                }
            });

            // Listen for localStorage changes (in case theme is changed in another tab)
            window.addEventListener('storage', function(e) {
                if (e.key === 'theme') {
                    const newThemeSetting = e.newValue || 'system';
                    let newTheme;

                    if (newThemeSetting === 'system') {
                        const preferredColorScheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                        newTheme = preferredColorScheme === 'no-preference' ? 'dark' : preferredColorScheme;
                    } else {
                        newTheme = newThemeSetting;
                    }

                    document.documentElement.classList.remove('light', 'dark');
                    document.documentElement.classList.add(newTheme);
                }
            });
        })();
    </script>
</head>
<body class="passport-authorize">
<div
    class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-default-background">
    <div>
        <a href="/" class="text-3xl font-bold text-text-primary">Tabi</a>
    </div>

    <div
        class="w-full sm:max-w-md mt-6 px-6 py-4 bg-card-background shadow-md border border-card-border overflow-hidden sm:rounded-lg">

        <!-- Introduction -->
        <p class="text-center pb-4 text-text-primary"><strong class="text-text-primary">{{ $client->name }}</strong> is requesting permission
            to access your
            account.</p>

        <!-- Scope List -->
        @if (count($scopes) > 0)
            <div class="pb-4">
                <p class="text-text-primary"><strong>This application will be able to:</strong></p>

                <ul class="list-disc pl-5 py-2 text-text-primary">
                    @foreach ($scopes as $scope)
                        <li>{{ $scope->description }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="flex flex-col sm:flex-row sm:space-x-5 space-x-0 space-y-3 sm:space-y-0">
            <!-- Authorize Button -->
            <form method="post" class="flex-1" action="{{ route('passport.authorizations.approve') }}">
                @csrf

                <input type="hidden" name="state" value="{{ $request->state }}">
                <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
                <input type="hidden" name="auth_token" value="{{ $authToken }}">
                <button type="submit"
                        class="w-full text-center items-center px-2 sm:px-3 py-2 bg-accent-300/10 border border-accent-300/20 rounded-md font-semibold text-xs sm:text-sm text-text-primary hover:bg-accent-300/20 active:bg-accent-300/20 focus:outline-none focus:ring-2 focus:ring-accent-300 focus:ring-offset-2 transition ease-in-out duration-150">
                    Authorize
                </button>
            </form>

            <!-- Cancel Button -->
            <form method="post" class="flex-1" action="{{ route('passport.authorizations.deny') }}">
                @csrf
                @method('DELETE')

                <input type="hidden" name="state" value="{{ $request->state }}">
                <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
                <input type="hidden" name="auth_token" value="{{ $authToken }}">
                <button
                    class="w-full text-center text-xs sm:text-sm px-2 sm:px-3 py-2 bg-button-secondary-background border border-button-secondary-border hover:bg-button-secondary-background-hover shadow-sm transition text-text-primary rounded-lg font-medium items-center space-x-1.5 focus-visible:border-input-border-active focus:outline-none focus:ring-0 disabled:opacity-25 ease-in-out">
                    Cancel
                </button>
            </form>
        </div>

    </div>

</div>
</body>
</html>
