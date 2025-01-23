<?php

declare(strict_types=1);

namespace Strata\Frontend\Cms;

use Strata\Frontend\Content\BaseContent;
use Strata\Frontend\Content\Page;
use Strata\Frontend\Content\PageCollection;
use Strata\Frontend\ContentModel\ContentModel;
use Strata\Frontend\ContentModel\ContentType;
use Strata\Frontend\Traits\CacheTrait;
use Strata\Frontend\Exception\ApiException;
use Strata\Frontend\Exception\ContentTypeNotSetException;
use Psr\SimpleCache\CacheInterface;

/**
 * Base class for CMS functionality
 *
 * Purpose is to read content from API and map to content objects
 *
 * @package Strata\Frontend\Cms
 */
abstract class ContentRepository
{
    use CacheTrait;

    /**
     * Content model
     *
     * @var ContentModel
     */
    protected $contentModel;

    /**
     * Current content type
     *
     * @var ContentType
     */
    protected $contentType;

    protected $cacheKey;

    /**
     * Set the content model
     *
     * @param ContentModel $contentModel
     * @return ContentRepository Fluent interface
     */
    public function setContentModel(ContentModel $contentModel): ContentRepository
    {
        $this->contentModel = $contentModel;

        return $this;
    }

    /**
     * Return the content model
     *
     * @return ContentModel
     */
    public function getContentModel(): ContentModel
    {
        return $this->contentModel;
    }

    /**
     * Set the requested content type
     *
     * @param string $type
     * @return ContentRepository
     */
    public function setContentType(string $type): ContentRepository
    {
        if ($this->contentTypeExists($type)) {
            $this->contentType = $this->getContentModel()->getContentType($type);
        }

        return $this;
    }

    /**
     * Return the current content type
     *
     * @return ContentType
     * @throws ContentTypeNotSetException
     */
    public function getContentType(): ContentType
    {
        if (!$this->hasContentType()) {
            throw new ContentTypeNotSetException('Content type is not set!');
        }
        return $this->contentType;
    }

    /**
     * Set cache key for current content request
     *
     * @param string $key
     */
    public function setCacheKey(string $key)
    {
        $this->cacheKey = $this->filterCacheKey($key);
    }

    /**
     * Get cache key for current request
     *
     * If not set, build it from passed params
     *
     * @param mixed ...$params
     * @return string
     * @throws ApiException
     */
    public function getCacheKey(...$params)
    {
        if (empty($this->cacheKey)) {
            return $this->buildCacheKey($params);
        }
        return $this->cacheKey ;
    }


    /**
     * Does the content type exist?
     *
     * @param string $type
     * @return bool
     */
    public function contentTypeExists(string $type): bool
    {
        return $this->contentModel->hasContentType($type);
    }

    /**
     * Do we have a valid content type and content model set?
     *
     * @return bool
     */
    public function hasContentType(): bool
    {
        if ($this->contentModel instanceof ContentModel && $this->contentType instanceof ContentType) {
            return true;
        }
        return false;
    }

    /**
     * Create a new page object
     *
     * This represents a content object (e.g. news article, page, case study, etc)
     *
     * @param array $data
     * @return Page
     */
    public function createPage(array $data): Page
    {
        $page = new Page();
        $page->setContentType($this->getContentType());
        $this->setContentFields($page, $data);

        return $page;
    }

    /**
     * Filter cache key to ensure it is safe to use
     *
     * @param $string
     * @return string
     */
    public function filterCacheKey($string): string
    {
        $string = (string) $string;
        $string = preg_replace('![{}()\@:]!', '', $string);
        $string = preg_replace('![\s/]!', '-', (string) $string);
        return $string;
    }

    /**
     * Build a cache key
     *
     * @param mixed ...$params An array of strings or single-level arrays used to build a cache key
     * @return string
     * @throws ApiException
     */
    public function buildCacheKey(...$params): string
    {
        $elements = [];
        foreach ($params as $param) {
            switch (gettype($param)) {
                case 'string':
                    if (!empty($param)) {
                        $elements[] = $this->filterCacheKey($param);
                    }
                    break;
                case 'integer':
                case 'double':
                    $elements[] = $this->filterCacheKey($param);
                    break;
                case 'boolean':
                    $elements[] = ($param) ? 'true' : 'false';
                    break;
                case 'NULL':
                    $elements[] = 'NULL';
                    break;
                case 'array':
                    foreach ($param as $key => $value) {
                        if (is_array($value)) {
                            throw new ApiException('Cannot build cache key from a multidimensional array');
                        }
                        $elements[] = $this->filterCacheKey($key) . '=' . $this->filterCacheKey($value);
                    }
                    break;
                default:
                    throw new ApiException(sprintf('Cannot build cache key from passed param, type: %s', gettype($param)));
            }
        }
        if (empty($elements)) {
            $elements[] = 'cache';
        }
        return implode('.', $elements);
    }

    /**
     * Sets content from data array into the content object
     *
     * @param BaseContent $page Content object to add fields to
     * @param array $data Array of data to set
     * @return null
     */
    abstract public function setContentFields(BaseContent $page, array $data);
}
