<?php

namespace App\Form;

use DOMDocument;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Event\PreSubmitEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class HtmlHandlingSubscriber implements EventSubscriberInterface
{
    private String $urlRegex;

    public function __construct(UrlGeneratorInterface $router)
    {
        $context = $router->getContext();
        $baseUrl = str_replace("/", "\\/", $context->getBaseUrl());
        $this->urlRegex = "/^(({$context->getScheme()}:\\/\\/)?{$context->getHost()}(:{$context->getHttpPort()}|:{$context->getHttpsPort()})?)?{$baseUrl}/";
    }

    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SUBMIT => 'onPreSubmit'
        ];
    }

    public function onPreSubmit(PreSubmitEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();
        assert($form->getConfig()->getType()->getInnerType() instanceof HtmlTextareaType);
        $doc = new DOMDocument();
        try{
            // add dummy div around content, to have one root node
            // otherwise $crawler->html() does crazy stuff
            $html = "<div>{$data}</div>";
            $doc->loadHTML(
                mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'),
                LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
            );
        } catch (\ErrorException $exception) {
            // TODO handle if html is not valid
            $doc->loadHTML('<p></p>');
        }
        $crawler = new Crawler($doc);
        if($form->getConfig()->getOption(HtmlTextareaType::RELATIVE_URLS)) $this->relativeUrls($crawler);
        if($form->getConfig()->getOption(HtmlTextareaType::CLEAR_SCRIPTS)) $this->clearScripts($crawler);
        $event->setData($crawler->html());
    }

    private function relativeUrls(Crawler $crawler)
    {
        $target = ['a' => 'href', 'img' => 'src'];

        foreach ($target as $item => $attr) {
            foreach ($crawler->filter($item) as $node) {
                $url = $node->getAttribute($attr);
                // remove base url
                $url = preg_replace($this->urlRegex, '', $url);
                $node->setAttribute($attr, $url);
            }
        }
    }

    private function clearScripts(Crawler $crawler)
    {
        foreach ($crawler->filter('script') as $node) {
            $node->parentNode->removeChild($node);
        }
    }
}