<?php

namespace BeyondCode\QueryDetector\Outputs;

use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class Alert implements Output
{
    public function boot()
    {
        //
    }

    public function output(Collection $detectedQueries, Response $response)
    {

        $contentType = $response->headers->get('Content-Type');
        
        $validateContentTypeHTML = true;

        if (env('QUERY_DETECTOR_AJAX', false)) {
            if (request()->ajax()) {
                if (stripos($contentType, 'application/json') === false || $response->isRedirection()) {
                    return;
                }

                $validateContentTypeHTML = false;
            }
        }

        if ($validateContentTypeHTML) {
            if (stripos($contentType, 'text/html') !== 0 || $response->isRedirection()) {
                return;
            }
        }

        $content = $response->getContent();

        $outputContent = $this->getOutputContent($detectedQueries);

        $pos = strripos($content, '</body>');

        if (false !== $pos) {
            $content = substr($content, 0, $pos) . $outputContent . substr($content, $pos);
        } else {
            if (!request()->ajax()) {
                $content = $content . $outputContent;
            } else {
                $jsonResponseContent = json_decode($content);

                $jsonResponseContent->laravelQueryDetector = $outputContent;

                $content = json_encode($jsonResponseContent);
            }
        }

        // Update the new content and reset the content length
        $response->setContent($content);

        $response->headers->remove('Content-Length');
    }

    protected function getOutputContent(Collection $detectedQueries)
    {
        if (!request()->ajax()) {
            $output = '<script type="text/javascript">';
            $output .= "alert('Found the following N+1 queries in this request:\\n\\n";

            foreach ($detectedQueries as $detectedQuery) {
                $output .= "Model: " . addslashes($detectedQuery['model']) . " => Relation: " . addslashes($detectedQuery['relation']);
                $output .= " - You should add \"with(\'" . addslashes($detectedQuery['relation']) . "\')\" to eager-load this relation.";
                $output .= "\\n";
            }
            $output .= "')";
            $output .= '</script>';
        } else {
            $output = "Found the following N+1 queries in this request:\n\n";

            foreach ($detectedQueries as $detectedQuery) {
                $output .= "Model: " . $detectedQuery['model'] . " => Relation: " . $detectedQuery['relation'];
                $output .= " - You should add \"with('" . $detectedQuery['relation'] . "')\" to eager-load this relation.";
                $output .= "\n";
            }
        }

        return $output;
    }
}