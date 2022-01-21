<?php

namespace Source\Model;

class Route
{
    private string  $name;
    private string  $path;
    private string  $route;
    private string  $class;
    private string  $method;
    private array   $methodsHttp;
    private array   $argsMethod;

    public function __construct(string $route, string $path)
    {
        $this->path = $path;
        $this->route = $route;
        $this->setClassAndMethod();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getMethodsHttp(): array
    {
        return $this->methodsHttp;
    }

    public function getArgsMethod(): array
    {
        return $this->argsMethod;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function addMethodHttp(string $method)
    {
        $this->methodsHttp[] = $method;
    }

    public function setClassAndMethod()
    {
        $classMethod = explode('::', $this->path);
        $this->class = $classMethod[0];
        $this->method = $classMethod[1];
    }

    public function match(string $url): array|bool
    {
        $pathRegex = $this->pathIntoRegex();
        $returned = preg_match($pathRegex, $url, $matches);

        if ($returned){
            return $matches;
        }

        return false;
    }

    public function pathIntoRegex(): string
    {
        $regex = preg_replace('/\{\w+\}/i', '(\w+)', $this->route);

        $regex = str_replace('/', '\/', $regex);
        $regex = '/^(' . $regex . ')$/i';

        return $regex;
    }

    public function loadArgsMethod(array $args)
    {
        preg_match_all('/\{\w+\}/i', $this->route, $matches);

        if (count($matches[0]) > 0) {

            $index = 0;
            foreach ($matches[0] as $value) {
                $name = preg_replace('/(\{)(\w+)(\})/', '${2}', $value);

                $this->argsMethod[$name] = $args[$index];
                $index++;
            }
        }
    }

    public function dispatch(string $url)
    {
        $returned = $this->match($url);
        if ($returned) {
            if (count($returned) > 2) {
                $this->loadArgsMethod(array_slice($returned, 2));
            }

            return $this->executeMethod();
        }

    }

    public function executeMethod()
    {
        $rMethod = new \ReflectionMethod($this->class, $this->method);

        if (isset($this->argsMethod)) {
            return $rMethod->invokeArgs(null, $this->argsMethod);
        }

        return $rMethod->invoke(null);
    }

}