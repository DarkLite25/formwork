<?php

namespace Formwork;

use Formwork\Data\Collection;
use Formwork\Utils\Arr;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Str;
use InvalidArgumentException;

class PageCollection extends Collection
{
    /**
     * Default property used to sort pages
     */
    protected const DEFAULT_SORT_PROPERTY = 'relativePath';

    /**
     * Pagination related to the collection
     */
    protected Pagination $pagination;

    /**
     * Return the Pagination object related to the collection
     */
    public function pagination(): Pagination
    {
        return $this->pagination;
    }

    /**
     * Reverse the order of collection items
     */
    public function reverse(): self
    {
        $pageCollection = clone $this;
        $pageCollection->data = array_reverse($pageCollection->data);
        return $pageCollection;
    }

    /**
     * Extract a slice from the collection containing a given number of items
     * and starting from a given offset
     *
     * @param int $length
     */
    public function slice(int $offset, int $length = null): self
    {
        $pageCollection = clone $this;
        $pageCollection->data = array_slice($pageCollection->data, $offset, $length);
        return $pageCollection;
    }

    /**
     * Remove a given element from the collection
     */
    public function remove(Page $element): self
    {
        $pageCollection = clone $this;
        foreach ($pageCollection->data as $key => $item) {
            if ($item->path() === $element->path()) {
                unset($pageCollection->data[$key]);
            }
        }
        return $pageCollection;
    }

    /**
     * Paginate the collection
     *
     * @param int $length Number of items in the pagination
     */
    public function paginate(int $length): self
    {
        $pagination = new Pagination($this->count(), $length);
        $pageCollection = $this->slice($pagination->offset(), $pagination->length());
        $pageCollection->pagination = $pagination;
        return $pageCollection;
    }

    /**
     * Filter collection items
     *
     * @param string   $property Property to find in filtered items
     * @param          $value    Value to check in filtered items (default: true)
     * @param callable $process  Callable to process items before filtering
     */
    public function filter(string $property, $value = true, callable $process = null): self
    {
        $pageCollection = clone $this;

        $pageCollection->data = array_filter($pageCollection->data, static function (Page $item) use ($property, $value, $process): bool {
            if ($item->has($property)) {
                $propertyValue = $item->get($property);

                if (is_callable($process)) {
                    $propertyValue = is_array($propertyValue) ? array_map($process, $propertyValue) : $process($propertyValue);
                    $value = $process($value);
                }

                if (is_array($propertyValue)) {
                    return in_array($value, $propertyValue);
                }
                return $propertyValue == $value;
            }

            return false;
        });

        return $pageCollection;
    }

    /**
     * Sort collection items
     *
     * @param int $direction Sorting direction (SORT_ASC or 1 for ascending order, SORT_DESC or -1 for descending)
     */
    public function sort(string $property = self::DEFAULT_SORT_PROPERTY, int $direction = SORT_ASC): self
    {
        $pageCollection = clone $this;

        if ($pageCollection->count() <= 1) {
            return $pageCollection;
        }

        if ($direction === SORT_ASC || $direction === 1) {
            $direction = 1;
        } elseif ($direction === SORT_DESC || $direction === -1) {
            $direction = -1;
        } else {
            throw new InvalidArgumentException('Invalid sorting direction. Use SORT_ASC or 1 for ascending order, SORT_DESC or -1 for descending');
        }

        usort($pageCollection->data, static fn (Page $item1, Page $item2): int => $direction * strnatcasecmp($item1->get($property), $item2->get($property)));

        return $pageCollection;
    }

    /**
     * Shuffle collection items
     */
    public function shuffle(): self
    {
        $pageCollection = clone $this;
        $pageCollection->data = Arr::shuffle($pageCollection->data);
        return $pageCollection;
    }

    /**
     * Search pages in the collection
     *
     * @param string $query Query to search for
     * @param int    $min   Minimum query length (default: 4)
     */
    public function search(string $query, int $min = 4): self
    {
        $query = trim(preg_replace('/\s+/u', ' ', $query));
        if (strlen($query) < $min) {
            return new static();
        }

        $keywords = explode(' ', $query);
        $keywords = array_diff($keywords, (array) Formwork::instance()->config()->get('search.stopwords'));
        $keywords = array_filter($keywords, static fn (string $item): bool => strlen($item) > $min);

        $queryRegex = '/\b' . preg_quote($query, '/') . '\b/iu';
        $keywordsRegex = '/(?:\b' . implode('\b|\b', $keywords) . '\b)/iu';

        $scores = [
            'title'    => 8,
            'summary'  => 4,
            'content'  => 3,
            'author'   => 2,
            'uri'      => 1
        ];

        $pageCollection = clone $this;

        foreach ($pageCollection->data as $page) {
            $score = 0;
            foreach (array_keys($scores) as $key) {
                $value = Str::removeHTML((string) $page->get($key));

                $queryMatches = preg_match_all($queryRegex, $value);
                $keywordsMatches = empty($keywords) ? 0 : preg_match_all($keywordsRegex, $value);

                $score += ($queryMatches * 2 + min($keywordsMatches, 3)) * $scores[$key];
            }

            if ($score > 0) {
                $page->set('score', $score);
            }
        }

        return $pageCollection->filter('score')->sort('score', SORT_DESC);
    }

    /**
     * Create a collection getting pages from a given path
     *
     * @param bool $recursive Whether to recursively search for pages
     */
    public static function fromPath(string $path, bool $recursive = false): self
    {
        $pages = [];

        foreach (FileSystem::listDirectories($path) as $dir) {
            $pagePath = FileSystem::joinPaths($path, $dir, DS);

            if ($dir[0] !== '_' && FileSystem::isDirectory($pagePath)) {
                $page = Formwork::instance()->site()->retrievePage($pagePath);

                if (!$page->isEmpty()) {
                    $pages[] = $page;
                }

                if ($recursive) {
                    $pages = array_merge($pages, static::fromPath($pagePath, true)->toArray());
                }
            }
        }

        $pages = new static($pages);
        return $pages->sort();
    }

    public function __debugInfo(): array
    {
        return [
            'items' => $this->data
        ];
    }
}
