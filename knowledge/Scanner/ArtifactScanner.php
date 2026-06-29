<?php
class ArtifactScanner
{
    public function scan(string $directory): array
    {
        $files = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory)
        );

        foreach ($iterator as $file) {

            if ($file->isDir()) {
                continue;
            }

            if ($file->getExtension() !== 'md') {
                continue;
            }

            $files[] = $file->getPathname();
        }

        return $files;
    }
}