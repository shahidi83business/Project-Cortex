<?php

class Artifact
{
    public string $id;
    public string $type;
    public string $title;

    /**
     * @var array<string,string>
     */
    public array $sections = [];

    /**
     * @var array<string,mixed>
     */
    public array $metadata = [];
}