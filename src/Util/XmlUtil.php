<?php

namespace App\Util;

use Exception;
use SimpleXMLElement;
use App\Manager\ErrorManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class XmlUtil
 *
 * Helper for XML parsing and formating
 *
 * @package App\Util
 */
class XmlUtil
{
    private ErrorManager $errorManager;

    public function __construct(ErrorManager $errorManager)
    {
        $this->errorManager = $errorManager;
    }

    /**
     * Check whether incoming request contains XML payload
     *
     * @param Request $request The HTTP request
     *
     * @return bool True if payload or headers indicate XML content
     */
    public function isXmlRequest(Request $request): bool
    {
        $contentType = strtolower((string) $request->headers->get('Content-Type', ''));
        $content = trim((string) $request->getContent());

        return str_contains($contentType, 'xml') || str_starts_with($content, '<');
    }

    /**
     * Parse raw XML payload into SimpleXMLElement
     *
     * @param string $rawPayload Raw request body
     *
     * @throws Exception When payload is empty or malformed
     *
     * @return SimpleXMLElement Parsed XML structure
     */
    public function parseXmlPayload(string $rawPayload): SimpleXMLElement
    {
        $payload = trim($rawPayload);
        if ($payload === '') {
            $this->errorManager->handleError(
                message: 'Payload body is empty',
                code: Response::HTTP_BAD_REQUEST
            );
        }

        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($payload, SimpleXMLElement::class);
        libxml_use_internal_errors($previous);

        if ($xml === false) {
            $this->errorManager->handleError(
                message: 'Malformed XML',
                code: Response::HTTP_BAD_REQUEST
            );
        }

        return $xml;
    }

    /**
     * Convert associative array to XML string
     *
     * @param array<mixed> $data Data to serialize
     * @param string $rootNode Root element name
     *
     * @return string XML-encoded content
     */
    public function formatToXml(array $data, string $rootNode = 'response'): string
    {
        $root = preg_replace('/[^a-z0-9_\-]/i', '_', $rootNode) ?: 'response';
        $xml = new SimpleXMLElement(sprintf('<%s/>', $root));
        $this->arrayToXml($data, $xml);

        // format xml data
        return (string) $xml->asXML();
    }

    /**
     * Convert array values to XML nodes (recursive)
     *
     * @param array<mixed> $data Data to convert
     * @param SimpleXMLElement $xml Current XML node
     *
     * @return void
     */
    private function arrayToXml(array $data, SimpleXMLElement $xml): void
    {
        foreach ($data as $key => $value) {
            $nodeName = is_numeric($key) ? 'item' : preg_replace('/[^a-z0-9_\-]/i', '_', (string) $key);
            if (is_array($value)) {
                $child = $xml->addChild((string) $nodeName);
                $this->arrayToXml($value, $child);
                continue;
            }

            $xml->addChild((string) $nodeName, htmlspecialchars((string) $value));
        }
    }
}
