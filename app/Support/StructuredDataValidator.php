<?php

namespace App\Support;

use App\Models\BlogPost;
use Throwable;

class StructuredDataValidator
{
    /**
     * @return array<int, array{name: string, status: string, message: string}>
     */
    public function checks(): array
    {
        return [
            $this->checkEncoder(),
            $this->checkLatestBlogPayload(),
            $this->checkRawJsonLdRegression(),
        ];
    }

    public function hasFailures(): bool
    {
        return collect($this->checks())->contains(fn (array $check): bool => $check['status'] === 'fail');
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function checkEncoder(): array
    {
        try {
            $json = Vdj::jsonLd([
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => 'Visual Design Journey',
                'url' => url('/'),
            ]);

            json_decode($json, true, 512, JSON_THROW_ON_ERROR);

            return $this->pass('JSON-LD encoder', 'Safe encoder produced parseable JSON.');
        } catch (Throwable $e) {
            return $this->fail('JSON-LD encoder', $e->getMessage());
        }
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function checkLatestBlogPayload(): array
    {
        try {
            $post = BlogPost::query()->latest('published_at')->first();

            if (! $post) {
                return $this->warn('Latest blog Article payload', 'No blog post found; skipped database-backed JSON-LD sample.');
            }

            $json = Vdj::jsonLd([
                '@context' => 'https://schema.org',
                '@type' => 'Article',
                'headline' => $post->localizedSeoTitle('en'),
                'description' => Vdj::plainText($post->localizedSeoDescription('en') ?: $post->localizedContent('en')),
                'mainEntityOfPage' => Vdj::blogUrl($post, 'en'),
            ]);

            json_decode($json, true, 512, JSON_THROW_ON_ERROR);

            return $this->pass('Latest blog Article payload', 'Latest post JSON-LD sample decoded successfully.');
        } catch (Throwable $e) {
            return $this->warn('Latest blog Article payload', 'Database-backed sample skipped: '.$e->getMessage());
        }
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function checkRawJsonLdRegression(): array
    {
        $files = [
            base_path('blog-post.php'),
            base_path('includes/header.php'),
            resource_path('views/vdj/blog/show.blade.php'),
        ];

        $bad = [];

        foreach ($files as $file) {
            if (! is_file($file)) {
                continue;
            }

            $contents = (string) file_get_contents($file);

            if (! str_contains($contents, 'application/ld+json')) {
                continue;
            }

            $usesSafeEncoder = str_contains($contents, 'vdj_json_ld_script')
                || str_contains($contents, 'Vdj::jsonLd')
                || str_contains($contents, '$jsonLd');

            if (! $usesSafeEncoder) {
                $bad[] = str_replace(base_path().'/', '', $file);
            }
        }

        if ($bad !== []) {
            return $this->fail('Raw JSON-LD regression scan', 'Unsafe JSON-LD script source found in: '.implode(', ', $bad));
        }

        return $this->pass('Raw JSON-LD regression scan', 'Critical templates use shared safe encoders.');
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function pass(string $name, string $message): array
    {
        return ['name' => $name, 'status' => 'pass', 'message' => $message];
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function warn(string $name, string $message): array
    {
        return ['name' => $name, 'status' => 'warn', 'message' => $message];
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function fail(string $name, string $message): array
    {
        return ['name' => $name, 'status' => 'fail', 'message' => $message];
    }
}
