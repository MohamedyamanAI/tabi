<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\HomeController;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Jetstream\Jetstream;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', [HomeController::class, 'index']);

Route::get('/time-tracking-software-for-agencies', function () {
    return Inertia::render('LandingAgencies');
})->name('landing.agencies');

Route::get('/time-tracking-software-for-startups', function () {
    return Inertia::render('LandingStartups');
})->name('landing.startups');

Route::get('/time-tracking-software-for-remote-teams', function () {
    return Inertia::render('LandingRemoteTeams');
})->name('landing.remote-teams');

Route::get('/sitemap.xml', function () {
    $urls = [
        ['loc' => url('/'), 'changefreq' => 'weekly', 'priority' => '1.0'],
        ['loc' => url('/login'), 'changefreq' => 'monthly', 'priority' => '0.5'],
        ['loc' => url('/register'), 'changefreq' => 'monthly', 'priority' => '0.8'],
        ['loc' => url('/time-tracking-software-for-agencies'), 'changefreq' => 'monthly', 'priority' => '0.8'],
        ['loc' => url('/time-tracking-software-for-startups'), 'changefreq' => 'monthly', 'priority' => '0.8'],
        ['loc' => url('/time-tracking-software-for-remote-teams'), 'changefreq' => 'monthly', 'priority' => '0.8'],
        ['loc' => url('/privacy-policy'), 'changefreq' => 'monthly', 'priority' => '0.3'],
        ['loc' => url('/terms-of-service'), 'changefreq' => 'monthly', 'priority' => '0.3'],
    ];

    $xml = '<?xml version="1.0" encoding="UTF-8"?>';
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    foreach ($urls as $url) {
        $xml .= '<url>';
        $xml .= '<loc>'.$url['loc'].'</loc>';
        $xml .= '<changefreq>'.$url['changefreq'].'</changefreq>';
        $xml .= '<priority>'.$url['priority'].'</priority>';
        $xml .= '</url>';
    }
    $xml .= '</urlset>';

    return new Response($xml, 200, ['Content-Type' => 'application/xml']);
})->name('sitemap');

Route::get('/robots.txt', function () {
    $content = "User-agent: *\n";
    $content .= "Disallow: /dashboard\n";
    $content .= "Disallow: /time\n";
    $content .= "Disallow: /calendar\n";
    $content .= "Disallow: /timesheet\n";
    $content .= "Disallow: /reporting\n";
    $content .= "Disallow: /projects\n";
    $content .= "Disallow: /clients\n";
    $content .= "Disallow: /members\n";
    $content .= "Disallow: /tags\n";
    $content .= "Disallow: /screenshots\n";
    $content .= "Disallow: /app-activity\n";
    $content .= "Disallow: /import\n\n";
    $content .= 'Sitemap: '.url('/sitemap.xml')."\n";

    return new Response($content, 200, ['Content-Type' => 'text/plain']);
})->name('robots');

Route::get('/shared-report', function () {
    return Inertia::render('SharedReport');
})->name('shared-report');

Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');

Route::middleware([
    'auth:web',
    config('jetstream.auth_session'),
    'verified',
])->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');

    Route::get('/time', function () {
        return Inertia::render('Time');
    })->name('time');

    Route::get('/calendar', function () {
        return Inertia::render('Calendar');
    })->name('calendar');

    Route::get('/timesheet', function () {
        return Inertia::render('Timesheet');
    })->name('timesheet');

    Route::get('/reporting', function () {
        return Inertia::render('Reporting');
    })->name('reporting');

    Route::get('/reporting/detailed', function () {
        return Inertia::render('ReportingDetailed');
    })->name('reporting.detailed');

    Route::get('/reporting/shared', function () {
        return Inertia::render('ReportingShared');
    })->name('reporting.shared');

    Route::get('/projects', function () {
        return Inertia::render('Projects');
    })->name('projects');

    Route::get('/projects/{project}', function () {
        return Inertia::render('ProjectShow');
    })->name('projects.show');

    Route::get('/clients', function () {
        return Inertia::render('Clients');
    })->name('clients');

    Route::get('/members', function () {
        return Inertia::render('Members', [
            'availableRoles' => array_values(Jetstream::$roles),
        ]);
    })->name('members');

    Route::get('/tags', function () {
        return Inertia::render('Tags');
    })->name('tags');

    Route::get('/screenshots', function () {
        return Inertia::render('Screenshots');
    })->name('screenshots');

    Route::get('/app-activity', function () {
        return Inertia::render('AppActivity');
    })->name('app-activity');

    Route::get('/import', function () {
        return Inertia::render('Import');
    })->name('import');

});
