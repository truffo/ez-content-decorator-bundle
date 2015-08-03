<?php

namespace Truffo\eZContentDecoratorBundle\Decorator;

use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ContentDecorator implements ContainerAwareInterface
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * @var \eZ\Publish\API\Repository\Repository
     */
    protected $repository;

    /**
     * @var \eZ\Publish\API\Repository\Values\Content\Location
     */
    protected $location = null;

    /**
     * @var \eZ\Publish\API\Repository\Values\Content\Content
     */
    protected $content = null;

    /**
     * @var string
     */
    protected $contentTypeIdentifier = null;


    /**
     * @param ContainerInterface $container
     * @param Location $location
     * @param $contentTypeIdentifier
     */
    public function __construct(ContainerInterface $container, Location $location, $contentTypeIdentifier)
    {

        $this->container = $container;
        $this->repository = $this->container->get('ezpublish.api.repository');

        $this->location = $location;
        $this->contentTypeIdentifier = $contentTypeIdentifier;
    }

    /**
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
        $this->repository = $this->container->get('ezpublish.api.repository');
    }

    /**
     * @return \eZ\Publish\API\Repository\Values\Content\Content
     */
    public function getContent()
    {
        if ($this->content == null) {
            $this->content = $this->repository->getContentService()
                ->loadContent($this->location->contentId);
        }
        return $this->content;
    }

    /**
     * @return Location
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @return string
     */
    public function getContentTypeIdentifier()
    {
        return $this->contentTypeIdentifier;
    }

}
