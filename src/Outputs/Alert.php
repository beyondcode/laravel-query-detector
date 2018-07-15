<?php

namespace BeyondCode\QueryDetector\Outputs;

use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class Alert implements Output
{
    public function output(Collection $detectedQueries, Response $response)
    {
        $content = $response->getContent();

        $outputContent = $this->getOutputContent($detectedQueries);

        $pos = strripos($content, '</body>');

        if (false !== $pos) {
            $content = substr($content, 0, $pos) . $outputContent . substr($content, $pos);
        } else {
            $content = $content . $outputContent;
        }

        // Update the new content and reset the content length
        $response->setContent($content);

        $response->headers->remove('Content-Length');
    }

    protected function getOutputContent(Collection $detectedQueries)
    {
        $output = '<script type="text/javascript">';
        $output .= "alert('Found the following N+1 queries in this request:\\n\\n";
        foreach ($detectedQueries as $detectedQuery) {
            $output .= "Model: ".addslashes($detectedQuery['model']). " => Relation: ".addslashes($detectedQuery['relation'])."\\n";
        }
        $output .= "')";
        $output .= '</script>';

        return $output;
    }
}