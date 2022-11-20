<?php

namespace App\Form;

use DOMDocument;
use ErrorException;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Event\PreSubmitEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Wa72\Url\Url;

class HtmlHandlingSubscriber implements EventSubscriberInterface
{
    private readonly Url $serverUrl;

    public function __construct(UrlGeneratorInterface $router)
    {
        $context = $router->getContext();
        $port = $context->getScheme() == 'https' ? $context->getHttpsPort() : $context->getHttpPort();
        $this->serverUrl = new Url('');
        $this->serverUrl->setScheme($context->getScheme());
        $this->serverUrl->setHost($context->getHost());
        $this->serverUrl->setPort($port);
        $this->serverUrl->setPath($context->getBaseUrl());
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SUBMIT => 'onPreSubmit',
        ];
    }

    public function onPreSubmit(PreSubmitEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();
        assert($form->getConfig()->getType()->getInnerType() instanceof HtmlTextareaType);
        $doc = new DOMDocument();
        try {
            // add dummy div around content, to have one root node
            // otherwise $crawler->html() does crazy stuff
            $html = "<div>{$data}</div>";
            $doc->loadHTML(
                mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'),
                LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
            );
        } catch (ErrorException) {
            // TODO handle if html is not valid
            $doc->loadHTML('<p></p>');
        }
        $crawler = new Crawler($doc);
        $this->relativeUrls($crawler, $form->getConfig()->getOption(HtmlTextareaType::FIX_URLS));
        $this->clearScripts($crawler, $form->getConfig()->getOption(HtmlTextareaType::CLEAR_SCRIPTS));
        $this->emptyHeadlines($crawler, $form->getConfig()->getOption(HtmlTextareaType::FIX_HEADLINES));
        $event->setData($crawler->html());
    }

    private function relativeUrls(Crawler $crawler, $mode)
    {
        if ($mode === false) {
            return;
        }

        $target = ['a' => 'href', 'img' => 'src'];

        foreach ($target as $item => $attr) {
            foreach ($crawler->filter($item) as $node) {
                $url = $node->getAttribute($attr);
                $url = new Url($url);

                if (!$url->is_url()) {
                    continue;
                }

                $newUrl = $url->write();
                if ($mode === 'relative') {
                    // Url::host is not set for relative urls
                    if ($url->getHost() == $this->serverUrl->getHost()) {
                        $newUrl = $url->write(Url::WRITE_FLAG_OMIT_SCHEME | Url::WRITE_FLAG_OMIT_HOST);
                    }
                } elseif ($mode === 'absolute') {
                    if (!$url->is_absolute()) {
                        $newUrl = $url->makeAbsolute($this->serverUrl)->write();
                    }
                }
                $node->setAttribute($attr, $newUrl);
            }
        }
    }

    private function clearScripts(Crawler $crawler, bool $enabled)
    {
        if (!$enabled) {
            return;
        }

        foreach ($crawler->filter('script') as $node) {
            $node->parentNode->removeChild($node);
        }
    }

    private function emptyHeadlines(Crawler $crawler, bool $enabled)
    {
        if (!$enabled) {
            return;
        }

        $tags = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'];

        foreach ($tags as $tag) {
            foreach ($crawler->filter($tag) as $node) {
                $text = $node->textContent;
                // replace &nbsp;
                $text = str_replace("\xC2\xA0", '', $text);
                $text = trim($text);
                if (empty($text)) {
                    $node->parentNode->removeChild($node);
                }
            }
        }
    }
}
