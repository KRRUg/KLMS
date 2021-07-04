<?php

namespace App\Controller;

use App\Security\AccountNotConfirmedException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Controller for login and logout. Handling of the request is done by the firewall.
 */
class SecurityController extends AbstractController
{
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

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

        if ($error) {
            $this->addFlash('error-raw', $this->errorMsgToHtml($error));
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
        ]);
    }

    private function errorMsgToHtml($error): string
    {
        if (empty($error))
            return '';

        switch (true) {
            case $error instanceof BadCredentialsException:
            case $error instanceof UsernameNotFoundException:
                return "E-Mail-Addresse oder Passwort falsch";
            case $error instanceof AccountNotConfirmedException:
                $user = $error->getUser();
                $url = $this->urlGenerator->generate('app_register_resend', ['email' => $user->getUsername()]);
                return "E-Mail-Addresse nicht bestätigt. <a href=\"{$url}\">Bestätigung anfordern.</a>";
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
