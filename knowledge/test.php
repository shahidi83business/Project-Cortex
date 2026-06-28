<?php
include "KnowledgeCompiler.php";
include "JsonKnowledgeRepository.php";

$artifact = new Artifact();

$artifact->id = 'DEC-001';

$artifact->type = 'decision';

$artifact->title = 'Use PostgreSQL';

$artifact->metadata = [
    'tags' => [
        'database',
        'backend'
    ]
];

$artifact->sections = [

    'Context' =>
        'We need a relational database.',

    'Decision' =>
        'Use PostgreSQL.',

    'Rationale' =>
        'Better concurrency.',

    'Consequences' =>
        'Need backup.'
];

$compiler = new KnowledgeCompiler();

$package = $compiler->compile($artifact);

$repository = new JsonKnowledgeRepository(
    __DIR__ . '/knowledge'
);

$repository->save($package);