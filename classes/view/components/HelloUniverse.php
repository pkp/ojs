<?php

namespace APP\view\components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class HelloUniverse extends Component
{
    public string $title;
    public string $description;

    /**
     * Create a new component instance.
     */
    public function __construct(string $title, string $description)
    {
        $this->title = $title;
        $this->description = $description;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.hello-universe');
    }
}
