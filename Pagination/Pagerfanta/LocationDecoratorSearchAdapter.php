<?php
namespace Truffo\eZContentDecoratorBundle\Pagination\Pagerfanta;

use eZ\Publish\Core\Pagination\Pagerfanta\LocationSearchHitAdapter;
use \Truffo\eZContentDecoratorBundle\Decorator\ContentDecoratorFactory;
use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use eZ\Publish\API\Repository\SearchService;
use Pagerfanta\Adapter\AdapterInterface;
use Pagerfanta\Pagerfanta;

/**
 * Pagerfanta adapter for eZ Publish content search.
 * Will return results as Location objects.
 */
class LocationDecoratorSearchAdapter extends LocationSearchHitAdapter
{
    /**
     * @var \eZ\Publish\API\Repository\Values\Content\LocationQuery
     */
    private $query;

    /**
     * @var \eZ\Publish\Core\Repository\SearchService
     */
    private $searchService;


    /**
     * @var \Truffo\eZContentDecoratorBundle\ContentDecoratorFactory $contentDecoratorFactory
     */
    private $contentDecoratorFactory;

    /**
     * @var int
     */
    private $nbResults;

    public function __construct( LocationQuery $query, SearchService $searchService, ContentDecoratorFactory $contentDecoratorFactory )
    {
        $this->query = $query;
        $this->searchService = $searchService;
        $this->contentDecoratorFactory = $contentDecoratorFactory;
    }

    /**
     * Returns the number of results.
     *
     * @return integer The number of results.
     */
    public function getNbResults()
    {
        if ( isset( $this->nbResults ) )
        {
            return $this->nbResults;
        }

        $countQuery = clone $this->query;
        $countQuery->limit = 0;
        return $this->nbResults = $this->searchService->findLocations( $countQuery )->totalCount;
    }

    /**
     * Returns a slice of the results as Location objects.
     *
     * @param integer $offset The offset.
     * @param integer $length The length.
     *
     * @return \Truffo\eZContentDecoratorBundle\Decorator\ContentDecorator[]
     */
    public function getSlice( $offset, $length )
    {

        $list = array();
        foreach ( parent::getSlice( $offset, $length ) as $hit )
        {
            $list[] = $this->contentDecoratorFactory->getContentDecorator($hit->valueObject);
        }

        return $list;
    }

    public static function buildPager(LocationQuery $query, SearchService $searchService, ContentDecoratorFactory $contentDecoratorFactory, $maxPerPage, $currentPage = 1)
    {
        $pager = new Pagerfanta(
            new static($query, $searchService, $contentDecoratorFactory)
        );
        $pager->setMaxPerPage($maxPerPage);
        $pager->setCurrentPage($currentPage);
        return $pager;
    }
}
