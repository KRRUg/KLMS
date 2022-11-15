<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    /**
     * @Route("/", name="dashboard")
     */
    public function index(): \Symfony\Component\HttpFoundation\Response
    {
        return $this->render('admin/dashboard/index.html.twig');
    }
}
