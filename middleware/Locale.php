<?php

namespace RainLab\Translate\Middleware;

use RainLab\Translate\Classes\Translator;
use Closure;

class Locale
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

        if( !$translator->isConfigured() ) {
            if( !$translator->loadLocaleFromRequest() ) {
                $translator->loadLocaleFromSession();
            }
        }       
        
        return $next($request);
    }
}