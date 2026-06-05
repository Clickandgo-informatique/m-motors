<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

class AboutController extends AbstractController
{
#[Route(path:'/about-us/',name:'about_us')]
    public function aboutUsPage()
    {
        return $this->render('about-us.html.twig');
    }
}
