<?php

namespace Tv2regionerne\StatamicCache\Tags;

use Statamic\Tags\Partial as BasePartial;
use Tv2regionerne\StatamicCache\Facades\Store;

class Partial extends BasePartial
{
    public function render($partial)
    {
        if (! $this->shouldRender()) {
            return;
        }

        if ($html = $this->runHooks('before-render')) {
            return $html;
        }

        $variables = array_merge($this->context->all(), $this->params->all(), [
            '__frontmatter' => $this->params->all(),
            'slot' => $this->isPair ? trim($this->parse()) : null,
        ]);

        $key = $this->params->get('autocache_key', 'none');

        Store::addWatcher($key);

        $html = view($this->viewName($partial), $variables)
            ->withoutExtractions()
            ->render();

        Store::removeWatcher($key);

        Store::addKeyMappingData(
            $key,
            ($this->context->get('autocache_parents') ?? []),
            (($length = $this->params->get('for')) ? now()->add('+'.$length) : null),
            $this->params->explode('tags', []),
        );

        return $this->runHooks('render', $html);
    }
}
