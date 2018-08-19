<?php

namespace BeyondCode\QueryDetector\Outputs;

use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class Alert implements Output
{
    /**
     * Boot the Output.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Generate the output.
     *
     * @param  \Illuminate\Support\Collection  $detectedQueries
     * @param  \Symfony\Component\HttpFoundation\Response|null  $response
     * @return void
     */
    public function output(Collection $detectedQueries, Response $response)
    {
        if (stripos($response->headers->get('Content-Type'), 'text/html') !== 0 || $response->isRedirection()) {
            return;
        }

        $content = $response->getContent();

        $outputContent = $this->getOutputContent($detectedQueries);

        $pos = strripos($content, '</body>');

        if ($pos !== false) {
            $content = substr($content, 0, $pos) . $outputContent . substr($content, $pos);
        } else {
            $content = $content . $outputContent;
        }

        // Update the new content and reset the content length
        $response->setContent($content);

        $response->headers->remove('Content-Length');
    }

    /**
     * Get the output content.
     *
     * @param  \Illuminate\Support\Collection  $detectedQueries
     * @return string
     */
    protected function getOutputContent(Collection $detectedQueries)
    {
        $output = '<script type="text/javascript">';
        $output .= "alert('Found the following N+1 queries in this request:\\n\\n";
        foreach ($detectedQueries as $detectedQuery) {
            $output .= "Model: ".addslashes($detectedQuery['model'])." => Relation: ".addslashes($detectedQuery['relation']);
            $output .= " - You should add \"with(\'".addslashes($detectedQuery['relation'])."\')\" to eager-load this relation.";
            $output .= "\\n";
        }
        $output .= "')";
        $output .= '</script>';

        return $output;
    }
}
