<?php

namespace App\Service;

use Symfony\Flex\Response;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;


class CookieReader
{
    private $stack;
    private $request;

    public function __construct(RequestStack $requestStack)
    {
        $this->stack = $requestStack;
        $this->request = $this->stack->getCurrentRequest();
    }


    public function getAllCookies(): \Symfony\Component\HttpFoundation\InputBag
    {
        return $this->request->cookies;
    }

    public function setCookie(string $name, string $value): Cookie
    {
        $cookie = new Cookie($name, $value, (new \DateTime("+1 year")));
        return $cookie;
    }

    public function getCookie(string $name)
    {
        return $this->request->cookies->get($name);
    }

    public function getSessionData($name)
    {
        return $this->request->getSession()->get($name);
    }

    public function setSessionData(string $name, $value)
    {
        $this->request->getSession()->set($name, $value);
    }
}