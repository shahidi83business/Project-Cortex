<?php

interface ConsumerInterface
{
    public function sync(Artifact $artifact): void;

    public function name(): string;
}