<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\API;

class HypermediaResource
{
    /**
     * @var array
     */
    private $data;

    /**
     * @var array
     */
    private $children;

    /**
     * @var array
     */
    private $links;

    /**
     * @var array
     */
    private $embedded;

    /**
     * @param array $data
     * @param array $links
     * @param array $children
     */
    public function __construct(array $data, array $links = [], array $children = [])
    {
        $this->links = $this->embedded = [];

        $this->data = $data;
        $this->children = $children;

        foreach ($links as $rel => $link) {
            $this->withLink($rel, $link);
        }
    }

    /**
     * @param NormalizerInterface $normalizer
     * @param string $selfLink
     *
     * @return JsonSerializable|array
     */
    public function resolved(NormalizerInterface $normalizer, ?string $selfLink = '')
    {
        if ($selfLink && !isset($this->links['self'])) {
            $this->withLink('self', new Hyperlink($selfLink));
        }

        $links = $this->resolveLinkRelations($normalizer);
        $embeds = $this->resolveEmbeddedRelations($normalizer);
        $resolved = $this->properties();

        if ($links) {
            $resolved['_links'] = $links;
        }

        if ($embeds) {
            $resolved['_embedded'] = $embeds;
        }

        return $resolved;
    }

    /**
     * @return array
     */
    public function properties()
    {
        return $this->data;
    }

    /**
     * @return array
     */
    public function children()
    {
        return $this->children;
    }

    /**
     * @return array
     */
    public function embeddedRelations()
    {
        return $this->embedded;
    }

    /**
     * @return array
     */
    public function linkRelations()
    {
        return $this->links;
    }

    /**
     * @param array $embeds
     *
     * @return self
     */
    public function withEmbedded(array $embeds)
    {
        $this->embedded = $embeds;
        return $this;
    }

    /**
     * @param string $rel
     * @param Hyperlink $link
     *
     * @return self
     */
    public function withLink($rel, Hyperlink $link)
    {
        $this->links[$rel] = $link;
        return $this;
    }

    /**
     * @param NormalizerInterface $normalizer
     *
     * @return array
     */
    private function resolveLinkRelations(NormalizerInterface $normalizer)
    {
        $links = $this->linkRelations();

        foreach ($this->children() as $rel => $child) {
            if (!in_array($rel, $this->embedded, true) && $child !== null) {
                $links[$rel] = $this->parseLinkedChildren($child, $normalizer);
            }
        }

        foreach ($links as &$link) {
            $link = $normalizer->normalize($link);
        }

        return $links;
    }

    /**
     * @param NormalizerInterface $normalizer
     *
     * @return array
     */
    private function resolveEmbeddedRelations(NormalizerInterface $normalizer)
    {
        $embeds = [];

        foreach ($this->children() as $rel => $child) {
            if (in_array($rel, $this->embedded, true) && $child !== null) {
                $embeds[$rel] = $this->parseEmbeddedChildren($child, $normalizer);
            }
        }

        return $embeds;
    }

    /**
     * @param mixed|array $child
     * @param NormalizerInterface $normalizer
     *
     * @return array
     */
    private function parseLinkedChildren($child, NormalizerInterface $normalizer)
    {
        if (is_array($child)) {
            return array_map(function ($nested) use ($normalizer) {
                return $normalizer->link($nested);
            }, $child);
        }

        return $normalizer->link($child);
    }

    /**
     * @param mixed|array $child
     * @param NormalizerInterface $normalizer
     *
     * @return array
     */
    private function parseEmbeddedChildren($child, NormalizerInterface $normalizer)
    {
        if (is_array($child)) {
            return array_map(function ($nested) use ($normalizer) {
                $resource = $normalizer->normalize($nested);
                return ($resource instanceof HypermediaResource) ? $resource->resolved($normalizer) : $resource;
            }, $child);
        }

        $resource = $normalizer->normalize($child);
        return ($resource instanceof HypermediaResource) ? $resource->resolved($normalizer) : $resource;
    }
}
