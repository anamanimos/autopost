<?php

namespace App\View;

use Illuminate\View\Compilers\BladeCompiler;

class SafeBladeCompiler extends BladeCompiler
{
    /**
     * Compile the view at the given path safely without throwing Utime/touch permission errors.
     *
     * @param  string|null  $path
     * @return void
     */
    public function compile($path = null)
    {
        if ($path) {
            $this->setPath($path);
        }

        if (! is_null($this->cachePath)) {
            $contents = $this->compileString($this->files->get($this->getPath()));

            if (! empty($this->getPath())) {
                $contents = $this->appendFilePath($contents);
            }

            $this->ensureCompiledDirectoryExists(
                $compiledPath = $this->getCompiledPath($this->getPath())
            );

            if (! $this->files->exists($compiledPath)) {
                $this->files->replace($compiledPath, $contents);

                return;
            }

            $compiledHash = $this->files->hash($compiledPath, 'xxh128');

            if ($compiledHash !== hash('xxh128', $contents)) {
                $this->files->replace($compiledPath, $contents);

                return;
            }

            $lastModified = $this->files->lastModified($this->getPath());

            if ($lastModified >= $this->files->lastModified($compiledPath)) {
                // PHP touch() triggers E_WARNING ("touch(): Utime failed: Operation not permitted")
                // when the file is owned by another user (e.g. root/ubuntu instead of www-data).
                // Use @touch to suppress warning, and replace file if touch fails.
                if (! @touch($compiledPath, $lastModified + 1)) {
                    try {
                        $this->files->replace($compiledPath, $contents);
                    } catch (\Throwable $e) {
                        // The compiled content is already identical and valid; ignore any timestamp update failure.
                    }
                }
            }
        }
    }
}
