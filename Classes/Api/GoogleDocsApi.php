<?php
declare(strict_types=1);

namespace GeorgRinger\GoogleDocsContent\Api;

use DOMDocument;
use DOMXPath;
use Google_Service_Drive;

class GoogleDocsApi extends Client
{


    public function getDoc(string $fileId)
    {
        $client = $this->getClient();
        $service = new Google_Service_Drive($client);

        /** @var \GuzzleHttp\Psr7\Response $response */
        $response = $service->files->export($fileId, 'text/html', [
            'alt' => 'media']);
        $content = $response->getBody()->getContents();
        if ($content) {
            $content = $this->cleanContent($content);
        }
        return $content;
    }

    protected function cleanContent(string $html)
    {
        $domd = new DOMDocument();
        libxml_use_internal_errors(true);
        $domd->loadHTML($html);
        libxml_use_internal_errors(false);

        $domx = new DOMXPath($domd);
        $items = $domx->query("//*[@style]");
        foreach ($items as $item) {
            $item->removeAttribute("style");
        }

        $clean = $domd->saveHTML();
        return $clean;
    }


    /**
     * Expands the home directory alias '~' to the full path.
     * @param string $path the path to expand.
     * @return string the expanded path.
     */
    function expandHomeDirectory($path)
    {
        $homeDirectory = getenv('HOME');
        if (empty($homeDirectory)) {
            $homeDirectory = getenv('HOMEDRIVE') . getenv('HOMEPATH');
        }
        return str_replace('~', realpath($homeDirectory), $path);
    }

}
