<?php namespace RainLab\Translate\Classes;

use RainLab\Translate\Classes\Translator;
use Closure;

class LocaleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $translator = Translator::instance();
        $translator->isConfigured();

        if (!$translator->loadLocaleFromRequest()) {
            $translator->loadLocaleFromSession();
        }

        return $next($request);
    }
}
