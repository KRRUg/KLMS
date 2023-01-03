<?php

namespace App\Security;

use App\Entity\User;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationServiceException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    private readonly UrlGeneratorInterface $urlGenerator;
    private readonly IdmRepository $repository;

    public function __construct(UrlGeneratorInterface $urlGenerator, IdmManager $idm)
    {
        $this->urlGenerator = $urlGenerator;
        $this->repository = $idm->getRepository(User::class);
    }

    public function authenticate(Request $request): Passport
    {
        // username is actually an email address
        // there is no LoginType to check if username is an email address or whether the parameter actually exits
        // invalid usernames fail at UserProvider::loadUserByIdentifier and invalid passwords at checkCredentials

        $username = $request->request->get('username', '');
        $password = $request->request->get('password', '');
        $csrf_token = $request->request->get('_csrf_token', '');

        if ($request->hasSession()) {
            $request->getSession()->set(Security::LAST_USERNAME, $username);
        }

        return new Passport(
            new UserBadge($username),
            new CustomCredentials($this->checkCredentials(...), $password),
            [
                new CsrfTokenBadge('authenticate', $csrf_token),
                new RememberMeBadge(),
            ]
        );
    }

    private function checkCredentials(string $credentials, UserInterface $user): bool
    {
        if (!$this->repository->authenticate($user->getUserIdentifier(), $credentials)) {
            return false;
        }
        if (!($user instanceof LoginUser)) {
            throw new AuthenticationServiceException();
        }
        if (!$user->getUser()->getEmailConfirmed()) {
            throw new AccountNotConfirmedException();
        }
        return true;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): Response
    {
        // Redirect back after Forced Login (Opening Page that you have no Access to)
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        // Redirect back when logging in via the Dropdown Login
        if ($targetPath = $request->request->get('_target_path')) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse('/');
    }

//    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
//    {
//        if ($exception instanceof AccountNotConfirmedException) {
//            // implement something here?
//            // e.g. see https://symfonycasts.com/screencast/symfony6-upgrade/authenticator-upgrade
//        }
//        return parent::onAuthenticationFailure($request, $exception);
//    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate('app_login');
    }
}
