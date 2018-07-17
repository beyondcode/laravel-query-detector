<?php
namespace BeyondCode\QueryDetector\Outputs;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class Console implements Output
{
    public function output(Collection $detectedQueries, Response $response)
    {
        if ($response->isRedirection()) {
            return;
        }
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
        $output .= "console.warn('Found the following N+1 queries in this request:\\n\\n";
        foreach ($detectedQueries as $detectedQuery) {
            $output .= "Model: ".addslashes($detectedQuery['model']). " => Relation: ".addslashes($detectedQuery['relation']);
            $output .= " - You should add \"with(\'".$detectedQuery['relation']."\')\" to eager-load this relation.";
            $output .= "\\n";
        }
        $output .= "')";
        $output .= '</script>';
        return $output;
    }
}
