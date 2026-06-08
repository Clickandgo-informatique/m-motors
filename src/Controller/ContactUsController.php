<?php

namespace App\Controller;

use App\Form\ContactUsType;
use App\Service\SendEmailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ContactUsController extends AbstractController
{
    #[Route('/contact-us', name: 'contact_us')]
    public function index(
        Request $request,
        SendEmailService $mailService
    ): Response {
        $form = $this->createForm(ContactUsType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $data = $form->getData();

            $contactEmail = $data['email'];
            $bodyText = $data['body'];

            // Envoi de l'email
            $mailService->send(
                $contactEmail,
                "mail@m-motors.com",
                "Contact-us form",
                "contact-us-form",
                ['body' => $bodyText]
            );


            $this->addFlash('success', 'Votre message a été envoyé.');

            return $this->redirectToRoute('contact_us');
        }

        return $this->render('contact-us.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
