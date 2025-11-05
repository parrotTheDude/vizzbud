<?php

namespace App\View\Components\Vizzbud;

use Illuminate\View\Component;

class Captcha extends Component
{
    public string $sitekey;

    /**
     * Create a new component instance.
     */
    public function __construct()
    {
        $this->sitekey = config('services.friendlycaptcha.sitekey');
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render()
    {
        return view('components.vizzbud.captcha');
    }
}