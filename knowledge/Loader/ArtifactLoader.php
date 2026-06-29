<?php

require_once __DIR__ . '/../Artifact/Artifact.php';

class ArtifactLoader
{
    public function load(string $path): Artifact
    {
        if (!file_exists($path)) {
            throw new Exception("Artifact not found: {$path}");
        }

        $markdown = file_get_contents($path);

        return $this->parse($markdown);
    }

    public function parse(string $markdown): Artifact
    {
        $metadata = [];
        $content = $markdown;

        // استخراج Front Matter
        if (preg_match('/^---\R(.*?)\R---\R/s', $markdown, $matches)) {

            $yaml = trim($matches[1]);

            foreach (preg_split('/\R/', $yaml) as $line) {

                if (!str_contains($line, ':')) {
                    continue;
                }

                [$key, $value] = explode(':', $line, 2);

                $metadata[trim($key)] = trim($value);
            }

            $content = substr($markdown, strlen($matches[0]));
        }

        return new Artifact(
            id: $metadata['id'] ?? uniqid(),
            type: $metadata['type'] ?? 'knowledge',
            title: $metadata['title'] ?? '',
            content: trim($content),
            metadata: $metadata
        );
    }
}