<?php

namespace App\Controller;

use App\Security\AccountNotConfirmedException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /**
     * @Route("/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            // Redirect to Frontpage if already logged in
            return $this->redirect('/');
        }
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $this->errorMsgToHtml($error),
        ]);
    }

    private function errorMsgToHtml($error): string
    {
        if (empty($error))
            return '';

        switch (true) {
            case $error instanceof BadCredentialsException:
            case $error instanceof UsernameNotFoundException:
                return "EMail-Addresse oder Password falsch.";
            case $error instanceof AccountNotConfirmedException:
                return "EMail-Addresse nicht bestätigt.";
            case $error instanceof InvalidCsrfTokenException:
                return "Es ist ein Fehler aufgetreten. Bitte Seite neu laden.";
            default:
                return "Es ist ein unbekannter Fehler aufgetreten.";
        }
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout()
    {
        throw new \Exception('This method can be blank - it will be intercepted by the logout key on your firewall');
    }
}
