<?php

namespace Truffo\eZContentDecoratorBundle\EventListener;

use eZ\Publish\Core\MVC\Symfony\Event\PreContentViewEvent;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PreContentViewListener implements ContainerAwareInterface
{
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }


    /**
     * @param PreContentViewEvent $event
     */
    public function onPreContentView( PreContentViewEvent $event )
    {
        $contentView = $event->getContentView();

        /** @var \Truffo\eZContentDecoratorBundle\Decorator\ContentDecoratorFactory $contentDecoratorFactory */
        $contentDecoratorFactory = $this->container->get('ezcontentdecorator.services.factory');

        if ($contentView->hasParameter('location')) {
            $location = $contentView->getParameter('location');
            /** @var \Truffo\eZContentDecoratorBundle\Decorator\ContentDecorator $contentDecorator */
            $contentDecorator = $contentDecoratorFactory->getContentDecorator($location);
            $contentView->addParameters([
                $contentDecorator->getContentTypeIdentifier() => $contentDecorator,
                'decorator' => $contentDecorator
            ]);
        }

    }

    /**
     * Sets the Container.
     *
     * @param ContainerInterface|null $container A ContainerInterface instance or null
     *
     * @api
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
}