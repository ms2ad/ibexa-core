<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Core\MVC\Symfony\Security\EventListener;

use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\UserService;
use Ibexa\Contracts\Core\Repository\Values\User\User as APIUser;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\MVC\Symfony\Event\InteractiveLoginEvent;
use Ibexa\Core\MVC\Symfony\MVCEvents;
use Ibexa\Core\MVC\Symfony\Security\Authorization\Attribute;
use Ibexa\Core\MVC\Symfony\Security\Exception\UnauthorizedSiteAccessException;
use Ibexa\Core\MVC\Symfony\Security\InteractiveLoginToken;
use Ibexa\Core\MVC\Symfony\Security\UserInterface as IbexaUser;
use Ibexa\Core\MVC\Symfony\Security\UserWrapped;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent as BaseInteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

/**
 * This security listener listens to security.interactive_login event to:
 *  - Give a chance to retrieve an Ibexa user when using multiple user providers
 *  - Check if user can actually login to the current SiteAccess.
 *
 * Also listens to kernel.request to:
 *  - Check if current user (authenticated or not) can access to current SiteAccess
 */
class SecurityListener implements EventSubscriberInterface
{
    /** @var \Ibexa\Contracts\Core\Repository\PermissionResolver */
    protected $permissionResolver;

    /** @var \Ibexa\Contracts\Core\Repository\UserService */
    protected $userService;

    /** @var \Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface */
    protected $configResolver;

    /** @var \Symfony\Component\EventDispatcher\EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface */
    protected $tokenStorage;

    /** @var \Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /**
     * The fragment path (for ESI/Hinclude...).
     *
     * @var string
     */
    protected $fragmentPath;

    public function __construct(
        PermissionResolver $permissionResolver,
        UserService $userService,
        ConfigResolverInterface $configResolver,
        EventDispatcherInterface $eventDispatcher,
        TokenStorageInterface $tokenStorage,
        AuthorizationCheckerInterface $authorizationChecker,
        $fragmentPath = '/_fragment'
    ) {
        $this->permissionResolver = $permissionResolver;
        $this->userService = $userService;
        $this->configResolver = $configResolver;
        $this->eventDispatcher = $eventDispatcher;
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
        $this->fragmentPath = $fragmentPath;
    }

    public static function getSubscribedEvents()
    {
        return [
            SecurityEvents::INTERACTIVE_LOGIN => [
                ['onInteractiveLogin', 10],
                ['checkSiteAccessPermission', 9],
            ],
            // Priority 7, so that it occurs just after firewall (priority 8)
            KernelEvents::REQUEST => ['onKernelRequest', 7],
        ];
    }

    /**
     * Tries to retrieve a valid Ibexa user if authenticated user doesn't come from the repository (foreign user provider).
     * Will dispatch an event allowing listeners to return a valid Ibexa user for current authenticated user.
     * Will by default let the repository load the anonymous user.
     *
     * @param \Symfony\Component\Security\Http\Event\InteractiveLoginEvent $event
     */
    public function onInteractiveLogin(BaseInteractiveLoginEvent $event)
    {
        $token = $event->getAuthenticationToken();
        $originalUser = $token->getUser();
        if ($originalUser instanceof IbexaUser || !$originalUser instanceof UserInterface) {
            return;
        }

        /*
         * 1. Send the event.
         * 2. If no Ibexa user is returned, load Anonymous user.
         * 3. Inject Ibexa user in repository.
         * 4. Create the UserWrapped user object (implementing Ibexa UserInterface) with loaded Ibexa user.
         * 5. Create new token with UserWrapped user
         * 6. Inject the new token in security context
         */
        $subLoginEvent = new InteractiveLoginEvent($event->getRequest(), $token);
        $this->eventDispatcher->dispatch($subLoginEvent, MVCEvents::INTERACTIVE_LOGIN);

        if ($subLoginEvent->hasAPIUser()) {
            $apiUser = $subLoginEvent->getAPIUser();
        } else {
            $apiUser = $this->userService->loadUser(
                $this->configResolver->getParameter('anonymous_user_id')
            );
        }

        $this->permissionResolver->setCurrentUserReference($apiUser);

        $providerKey = method_exists($token, 'getProviderKey') ? $token->getProviderKey() : __CLASS__;
        $interactiveToken = new InteractiveLoginToken(
            $this->getUser($originalUser, $apiUser),
            get_class($token),
            $token->getCredentials(),
            $providerKey,
            $token->getRoleNames()
        );
        $interactiveToken->setOriginalToken($token);
        $interactiveToken->setAttributes($token->getAttributes());
        $this->tokenStorage->setToken($interactiveToken);
    }

    /**
     * Returns new user object based on original user and provided API user.
     * One may want to override this method to use their own user class.
     *
     * @param \Symfony\Component\Security\Core\User\UserInterface $originalUser
     * @param \Ibexa\Contracts\Core\Repository\Values\User\User $apiUser
     *
     * @return \Ibexa\Core\MVC\Symfony\Security\UserInterface
     */
    protected function getUser(UserInterface $originalUser, APIUser $apiUser)
    {
        return new UserWrapped($originalUser, $apiUser);
    }

    /**
     * Throws an UnauthorizedSiteAccessException if current user doesn't have permission to current SiteAccess.
     *
     * @param \Symfony\Component\Security\Http\Event\InteractiveLoginEvent $event
     *
     * @throws \Ibexa\Core\MVC\Symfony\Security\Exception\UnauthorizedSiteAccessException
     */
    public function checkSiteAccessPermission(BaseInteractiveLoginEvent $event)
    {
        $token = $event->getAuthenticationToken();
        $originalUser = $token->getUser();
        $request = $event->getRequest();
        $siteAccess = $request->attributes->get('siteaccess');
        if (!($originalUser instanceof IbexaUser && $siteAccess instanceof SiteAccess)) {
            return;
        }

        if (!$this->hasAccess($siteAccess)) {
            throw new UnauthorizedSiteAccessException($siteAccess, $originalUser->getUsername());
        }
    }

    /**
     * Throws an UnauthorizedSiteAccessException if current user doesn't have access to current SiteAccess.
     *
     * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
     *
     * @throws \Ibexa\Core\MVC\Symfony\Security\Exception\UnauthorizedSiteAccessException
     */
    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();
        // Ignore sub-requests, including fragments.
        if (!$this->isMasterRequest($request, $event->getRequestType())) {
            return;
        }

        $siteAccess = $request->attributes->get('siteaccess');
        if (!$siteAccess instanceof SiteAccess) {
            return;
        }

        $token = $this->tokenStorage->getToken();
        if ($token === null) {
            return;
        }

        if (
            // Leave access to login route, so that user can attempt re-authentication.
            $request->attributes->get('_route') !== 'login'
            && !$this->hasAccess($siteAccess)
        ) {
            throw new UnauthorizedSiteAccessException($siteAccess, $token->getUsername());
        }
    }

    /**
     * Returns true if given request is considered as a master request.
     * Fragments are considered as sub-requests (i.e. ESI, Hinclude...).
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param $requestType
     *
     * @return bool
     */
    private function isMasterRequest(Request $request, $requestType)
    {
        if (
            $requestType !== HttpKernelInterface::MASTER_REQUEST
            || substr($request->getPathInfo(), -strlen($this->fragmentPath)) === $this->fragmentPath
        ) {
            return false;
        }

        return true;
    }

    /**
     * Returns true if current user has access to given SiteAccess.
     *
     * @param \Ibexa\Core\MVC\Symfony\SiteAccess $siteAccess
     *
     * @return bool
     */
    protected function hasAccess(SiteAccess $siteAccess)
    {
        return $this->authorizationChecker->isGranted(
            new Attribute('user', 'login', ['valueObject' => $siteAccess])
        );
    }
}

class_alias(SecurityListener::class, 'eZ\Publish\Core\MVC\Symfony\Security\EventListener\SecurityListener');
