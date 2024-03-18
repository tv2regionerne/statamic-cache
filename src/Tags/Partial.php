<?php

namespace Tv2regionerne\StatamicCache\Tags;

use Livewire\Livewire;
use Statamic\Facades\Site;
use Statamic\Facades\URL;
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

        $key = $this->context->get('autocache_key', $this->generateAutocacheKey());

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

    private function generateAutocacheKey()
    {
        $src = $this->params->get('src') ?? str_replace('partial:', '', $this->tag);

        // get depth of stack
        $parser = new \ReflectionObject($this->parser);
        $depth = $parser->getProperty('parseStack')->getValue($this->parser);

        // if we are looping
        if ($count = $this->context->int('count')) {
            $depth .= ':'.$count;
        }

        $scope = $this->params->get('scope', 'page');

        if ($scope === 'site') {
            $hash = Site::current()->handle();
        }

        if ($scope === 'page') {
            $hash = URL::makeAbsolute(class_exists(Livewire::class) ? Livewire::originalUrl() : URL::getCurrent());
        }

        if ($scope === 'user') {
            $hash = ($user = auth()->user()) ? $user->id : 'guest';
        }

        $key = 'autocache::'.md5($hash).':'.$depth.':'.str_replace('/', '.', $src);

        if ($prefix = $this->params->get('prefix') ? $prefix.'__' : '') {
            $key = $prefix.$key;
        }

        return $key;
    }
}
