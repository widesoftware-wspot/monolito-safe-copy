<?php
namespace Wideti\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Wideti\DomainBundle\Helpers\Controller\AdminControllerHelper;
use Wideti\DomainBundle\Service\ElasticSearch\ElasticSearch;
use Wideti\DomainBundle\Service\ElasticSearch\ElasticSearchAware;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\SecurityAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;
use Wideti\AdminBundle\Form\Type\Reports\LogFilterType;
use Wideti\DomainBundle\Helpers\Pagination;

class LogsController
{
    use EntityManagerAware;
    use TwigAware;
    use ElasticSearchAware;
    use SessionAware;
    use SecurityAware;

    /**
     * @var AuthorizationChecker
     */
    protected $authorization;
    /**
     * @var AdminControllerHelper
     */
    private $controllerHelper;

    public function __construct(AdminControllerHelper $controllerHelper)
    {
        $this->controllerHelper = $controllerHelper;
    }

    public function indexAction(Request $request, $page = 1)
    {
        $filter = $this->controllerHelper->createForm(LogFilterType::class);
        $filter->handleRequest($request);

        $from    = 0;
        $perPage = 30;

        if ($page > 1) {
            $from = $page * $perPage;
        }

        $query = [
            "size" => $perPage,
            "from" => $from,
            "query" => [
                "filtered" => [
                    "filter" => [
                        "and" => [
                            "filters" => [
                                [
                                    "term" => [
                                        "client.id" => $this->getLoggedClient()->getId()
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'sort' => [
                'date' => [
                    'order' => 'desc'
                ]
            ]
        ];

        if ($filter->isValid()) {
            $data = $filter->getData();

            if (isset($data['module'])) {
                array_push($query['query']['filtered']['filter']['and']['filters'], [
                    "term" => [
                        "module" => $data['module']
                    ]
                ]);
            }

            array_push($query['query']['filtered']['filter']['and']['filters'], [
                "range" => [
                    "date" => [
                        "lte" => $data['date_to']->format('Y-m-d 23:59:59'),
                        "gte" => $data['date_from']->format('Y-m-d 00:00:00'),
                    ]
                ]
            ]);
        } else {
            array_push($query['query']['filtered']['filter']['and']['filters'], [
                "range" => [
                    "date" => [
                        "gte" => "now-30d",
                        "lte" => "now"
                    ]
                ]
            ]);

        }

        if ($this->authorization->isGranted('ROLE_ADMIN') === false) {
            array_push($query['query']['filtered']['filter']['and']['filters'], [
                "term" => [
                    "user.id" => $this->getUser()->getId()
                ]
            ]);
        }

        $changes = $this->elasticSearchService->search('changelog', $query, ElasticSearch::LOG);
        $results = [];

        $pagination      = new Pagination($page, $changes['hits']['total'], $perPage);
        $paginationArray = $pagination->createPagination();

        foreach ($changes['hits']['hits'] as $result) {
            $result['_source']['id'] = $result['_id'];
            $results[]               = $result['_source'];
        }

        return $this->render('AdminBundle:Logs:index.html.twig', [
            'changes'    => $results,
            'pagination' => $paginationArray,
            'filter'     => $filter->createView(),
            'count'      => $changes['hits']['total']
        ]);
    }

    public function setAuthorizationChecker(AuthorizationChecker $authorizationChecker)
    {
        $this->authorization = $authorizationChecker;
    }
}
