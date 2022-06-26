<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TaskDocumentation extends AbstractController
{
    #[Route('/docs')]
    public function page(): Response
    {
        return $this->render('docs.html.twig');
    }
}
