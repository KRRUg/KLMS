<?php


namespace App\Controller\Admin;


use App\Controller\BaseController;
use App\Entity\Navigation;
use App\Entity\Teamsite;
use App\Form\NavigationNodeType;
use App\Entity\NavigationNodeContent;
use App\Entity\NavigationNodeEmpty;
use App\Entity\NavigationNodeGeneric;
use App\Form\TeamsiteType;
use App\Service\ContentService;
use App\Service\NavigationService;
use App\Service\TeamsiteService;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/teamsite", name="teamsite")
 */
class TeamsiteController extends BaseController
{
    private LoggerInterface $logger;
    private TeamsiteService $teamsiteService;

    public function __construct(TeamsiteService $teamsiteService, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->teamsiteService = $teamsiteService;
    }

    /**
     * @Route("", name="", methods={"GET"})
     */
    public function index()
    {
        $sites = $this->teamsiteService->getAll();
        return $this->render('admin/teamsite/index.html.twig', [
            'teamsites' => $sites,
        ]);
    }

    /**
     * @Route("/edit/{id}", name="_edit", methods={"GET", "POST"})
     * @ParamConverter()
     */
    public function edit(Request $request, Teamsite $teamsite)
    {
        $array = $this->teamsiteService->renderSite($teamsite);

        $form = $this->createForm(TeamsiteType::class, $teamsite);
        $form->get('content')->setData(json_encode($array));

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $array = json_decode($form->get('content')->getData(), true);
            $success = $this->teamsiteService->parseSite($teamsite, $array);
            if ($success) {
                $this->addFlash('success', 'Navigation updated');
            } else {
                $this->addFlash('danger', 'Navigation Speichern fehlgeschlagen');
            }
            return $this->redirectToRoute('admin_teamsite');
        }
        return $this->render('admin/navigation/edit.html.twig', [
            'site' => $teamsite,
            'form' => $form->createView(),
        ]);
    }
}
