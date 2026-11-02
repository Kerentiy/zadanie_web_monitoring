<?php

declare(strict_types=1);

namespace App;

/** Base contract shared by all HTTP controllers. */
abstract class AbstractController
{
    abstract public function __invoke(Request $request): Response;

    protected function html(string $title, string $content, int $status = 200): Response
    {
        $safeTitle = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return new Response('<!doctype html><html lang="ru"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<title>' . $safeTitle . '</title><style>'
            . 'body{font:16px system-ui;max-width:760px;margin:2rem auto;padding:0 1rem;color:#222}'
            . 'a,button{color:#175cd3}input,button{padding:.55rem;border:1px solid #bbb;border-radius:4px}'
            . '.nav{display:flex;gap:1rem;margin:1rem 0}code{background:#f2f4f7;padding:.2rem .4rem}'
            . '.ok{color:#067647}</style></head><body>' . $content . '</body></html>', $status);
    }

    protected function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
