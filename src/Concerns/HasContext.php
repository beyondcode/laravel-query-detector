<?php

namespace BeyondCode\QueryDetector\Concerns;

use Illuminate\Support\Str;

trait HasContext
{
    /** @var string */
    protected $context = 'querydetector';

    /**
     * Set specific context for the executed queries.
     *
     * @param string $context
     * @return self
     */
    public function setContext(string $context): self
    {
        $this->context = $context;

        return $this;
    }

    /**
     * Get the current context name
     *
     * @param string $context
     * @return self
     */
    public function getContext(): string
    {
        return $this->context;
    }

    /**
     * Generate a new context for the executed queries.
     *
     * @return self
     */
    public function newContext(): self
    {
        $this->setContext(Str::random());

        return $this;
    }
}