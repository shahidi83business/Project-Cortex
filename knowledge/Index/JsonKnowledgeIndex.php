<?php

class JsonKnowledgeIndex implements KnowledgeIndex
{
    private array $types = [];

    private array $tags = [];

    private array $relations = [];

    public function index(KnowledgePackage $package): void
    {
        foreach ($package->objects as $object) {

            $this->types[$object->type][] =
                $package->artifactId;

            foreach ($object->tags as $tag) {

                $this->tags[$tag][] =
                    $package->artifactId;
            }

            foreach ($object->relations as $relation) {

                $this->relations[$relation][] =
                    $package->artifactId;
            }
        }

        $this->unique();
    }

    public function remove(string $artifactId): void
    {
        foreach ($this->types as &$items) {
            $items = array_values(
                array_diff($items, [$artifactId])
            );
        }

        foreach ($this->tags as &$items) {
            $items = array_values(
                array_diff($items, [$artifactId])
            );
        }

        foreach ($this->relations as &$items) {
            $items = array_values(
                array_diff($items, [$artifactId])
            );
        }
    }

    public function findByType(string $type): array
    {
        return $this->types[$type] ?? [];
    }

    public function findByTag(string $tag): array
    {
        return $this->tags[$tag] ?? [];
    }

    public function findByRelation(string $relation): array
    {
        return $this->relations[$relation] ?? [];
    }

    private function unique(): void
    {
        foreach ($this->types as &$items) {
            $items = array_values(array_unique($items));
        }

        foreach ($this->tags as &$items) {
            $items = array_values(array_unique($items));
        }

        foreach ($this->relations as &$items) {
            $items = array_values(array_unique($items));
        }
    }
}