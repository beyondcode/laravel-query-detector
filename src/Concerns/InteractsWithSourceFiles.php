<?php

namespace BeyondCode\QueryDetector\Concerns;

trait InteractsWithSourceFiles
{
    protected function findSource($stack)
    {
        $sources = [];

        foreach ($stack as $index => $trace) {
            $sources[] = $this->parseTrace($index, $trace);
        }

        return array_values(array_filter($sources));
    }

    public function parseTrace($index, array $trace)
    {
        $frame = (object) [
            'index' => $index,
            'name' => null,
            'line' => isset($trace['line']) ? $trace['line'] : '?',
        ];

        if (isset($trace['class']) &&
            isset($trace['file']) &&
            !$this->fileIsInExcludedPath($trace['file'])
        ) {
            $frame->name = $this->normalizeFilename($trace['file']);

            return $frame;
        }

        return false;
    }

    /**
     * Check if the given file is to be excluded from analysis
     *
     * @param string $file
     * @return bool
     */
    protected function fileIsInExcludedPath($file)
    {
        $excludedPaths = [
            '/vendor/laravel/framework/src/Illuminate/Database',
            '/vendor/laravel/framework/src/Illuminate/Events',
        ];

        $normalizedPath = str_replace('\\', '/', $file);

        foreach ($excludedPaths as $excludedPath) {
            if (strpos($normalizedPath, $excludedPath) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Shorten the path by removing the relative links and base dir
     *
     * @param string $path
     * @return string
     */
    protected function normalizeFilename($path): string
    {
        if (file_exists($path)) {
            $path = realpath($path);
        }

        return str_replace(base_path(), '', $path);
    }
}