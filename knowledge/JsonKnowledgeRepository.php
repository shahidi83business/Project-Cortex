<?php
include "KnowledgeRepository.php";
class JsonKnowledgeRepository implements KnowledgeRepository
{
    private string $directory;

    public function __construct(string $directory)
    {
        $this->directory = rtrim($directory, '/');

        if (!is_dir($this->directory)) {
            mkdir($this->directory, 0777, true);
        }
    }

    public function save(KnowledgePackage $package): void
    {
        $file = $this->directory . '/' . $package->artifactId . '.json';

        file_put_contents(
            $file,
            json_encode(
                $package,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            )
        );
    }

    public function load(string $artifactId): ?KnowledgePackage
    {
        $file = $this->directory . '/' . $artifactId . '.json';

        if (!file_exists($file)) {
            return null;
        }

        $data = json_decode(file_get_contents($file), true);

        return $this->hydrate($data);
    }

    public function all(): array
    {
        $packages = [];

        foreach (glob($this->directory . '/*.json') as $file) {

            $data = json_decode(file_get_contents($file), true);

            $packages[] = $this->hydrate($data);
        }

        return $packages;
    }

    public function delete(string $artifactId): void
    {
        $file = $this->directory . '/' . $artifactId . '.json';

        if (file_exists($file)) {
            unlink($file);
        }
    }

    public function exists(string $artifactId): bool
    {
        return file_exists(
            $this->directory . '/' . $artifactId . '.json'
        );
    }

    private function hydrate(array $data): KnowledgePackage
    {
        $package = new KnowledgePackage();

        $package->artifactId = $data['artifactId'];

        foreach ($data['objects'] as $item) {

            $object = new KnowledgeObject();

            $object->id = $item['id'];
            $object->artifactId = $item['artifactId'];
            $object->type = $item['type'];
            $object->section = $item['section'];
            $object->content = $item['content'];
            $object->tags = $item['tags'] ?? [];
            $object->relations = $item['relations'] ?? [];

            $package->objects[] = $object;
        }

        return $package;
    }
}