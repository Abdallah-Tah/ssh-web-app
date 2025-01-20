use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

public function boot(): void
{
    RateLimiter::for('ssh-operations', function (Request $request) {
        return Limit::perMinute(5)->by($request->ip());
    });
}
