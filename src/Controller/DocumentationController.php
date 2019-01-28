<?php

namespace App\Controller;

use App\Service\Companion\CompanionTokenManager;
use App\Service\Content\ContentList;
use App\Service\Content\GameServers;
use App\Service\Docs\Icons;
use App\Service\Search\SearchContent;
use App\Service\Search\SearchRequest;
use App\Service\ThirdParty\GitHub;
use App\Service\ThirdParty\DigitalOcean;
use App\Service\ThirdParty\Vultr;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @package App\Controller
 */
class DocumentationController extends Controller
{
    /** @var EntityManagerInterface */
    private $em;
    /** @var CompanionTokenManager */
    private $companion;
    
    public function __construct(EntityManagerInterface $em, CompanionTokenManager $companion)
    {
        $this->em = $em;
        $this->companion = $companion;
    }
    
    /**
     * @Route("/docs", name="docs")
     * @Route("/docs/{page}", name="docs_page")
     */
    public function docs(Request $request, $page = null)
    {
        $file = strtolower($page ? $page : 'Welcome');
        $file = str_ireplace([' ','-'], '_', $file);

        $response = [
            'file' => $file,
            'page_name' => $page,
            'search_indexes' => SearchContent::LIST,
            'search_indexes_default' => SearchContent::LIST,
            'search_algo_default' => SearchRequest::STRING_ALGORITHM_DEFAULT,
            'content_max_default' => ContentList::DEFAULT_ITEMS,
            'content_max' => ContentList::MAX_ITEMS,
            'server_list' => GameServers::LIST,
            'server_tokens' => $this->companion->getCompanionLoginStatus(),
            'server_unsupported' => CompanionTokenManager::SERVERS_OFFLINE,
        ];
        
        // change logs
        if ($file == 'costs') {
            $response['vultr'] = Vultr::costs();
            $response['digitalocean'] = DigitalOcean::costs();
        }

        // change logs0
        if ($file == 'git_logs') {
            $response['commits'] = GitHub::getGithubCommitHistory();
        }

        // icon
        if ($file == 'icons') {
            $response['set']    = $request->get('set');
            $response['images'] = (new Icons())->get($request->get('set'));
        }

        return $this->render('docs/pages/'. $file .'.html.twig', $response);
    }

    /**
     * @Route("/docs/download", name="docs_download")
     */
    public function download(Request $request)
    {
        if ($request->get('set')) {
            return $this->file(
                new File(
                    (new Icons())->downloadIconSet($request->get('set'))
                )
            );
        }

        throw new NotFoundHttpException();
    }
}
