<?php

namespace BeyondCode\QueryDetector\Concerns;

trait Bootable
{
    /** @var bool */
    protected $booted = false;

    /**
     * Safely boot if wasn't booted before
     *
     * @return void
     */
    public function bootIfNotBooted() : void
    {
        if ($this->isBooted()) {
            return;
        }

        $this->boot();
        $this->booted();
    }

    /**
     * Runs after "boot"
     *
     * @return void
     */
    protected function booted() : void
    {
        $this->booted = true;
    }

    /**
     * Determine if already booted
     *
     * @return bool
     */
    public function isBooted()
    {
        return $this->booted;
    }

    protected function boot() : void
    {
    }
}