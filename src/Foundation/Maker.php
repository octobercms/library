<?php namespace October\Rain\Foundation;

use Closure;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use ReflectionClass;
use ReflectionParameter;

/**
 * Maker class restores App::make with parameters
 */
class Maker
{
    /**
     * @var array bindings for the container
     */
    protected $bindings = [];

    /**
     * @var Container container for the application
     */
    protected $container;

    /**
     * __construct
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * make from the container
     */
    public function make($abstract, $parameters = [])
    {
        return $this->build(
            $this->getBinding($abstract),
            $parameters
        );
    }

    /**
     * bind to the container
     */
    public function bind($abstract, Closure $concrete)
    {
        $this->bindings[$abstract] = $concrete;
    }

    /**
     * build a concrete implementation with parameters
     */
    protected function build($concrete, $parameters)
    {
        if ($concrete instanceof Closure) {
            return $concrete($this->container, $parameters);
        }

        $reflector = new ReflectionClass($concrete);

        if (!$reflector->isInstantiable()) {
            throw new BindingResolutionException("Target [$concrete] is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            return new $concrete;
        }

        $dependencies = $constructor->getParameters();

        $parameters = $this->keyParametersByArgument(
            $dependencies,
            $parameters
        );

        $instances = $this->getDependencies(
            $dependencies,
            $parameters
        );

        return $reflector->newInstanceArgs($instances);
    }

    /**
     * keyParametersByArgument if extra parameters are passed by numeric ID,
     * rekey them by argument name.
     * @return array
     */
    protected function keyParametersByArgument(array $dependencies, array $parameters)
    {
        foreach ($parameters as $key => $value) {
            if (is_numeric($key)) {
                unset($parameters[$key]);
                $parameters[$dependencies[$key]->name] = $value;
            }
        }

        return $parameters;
    }

    /**
     * getDependencies resolves all of the dependencies from the ReflectionParameters
     * @return array
     */
    protected function getDependencies(array $parameters, array $primitives = [])
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $dependency = $parameter->getType();

            if (array_key_exists($parameter->name, $primitives)) {
                $dependencies[] = $primitives[$parameter->name];
            }
            elseif (is_null($dependency)) {
                $dependencies[] = $this->resolvePrimitive($parameter);
            }
            else {
                $dependencies[] = $this->resolveClass($parameter);
            }
        }

        return $dependencies;
    }

    /**
     * getFromContainer
     * @return mixed
     * @throws BindingResolutionException
     */
    protected function resolveClass(ReflectionParameter $parameter)
    {
        try {
            return $this->getFromContainer($parameter->getType()->getName());
        }
        catch (BindingResolutionException $e) {
            if ($parameter->isOptional()) {
                return $parameter->getDefaultValue();
            }
            throw $e;
        }
    }

    /**
     * getBinding
     * @param $abstract
     * @return mixed
     */
    protected function getBinding($abstract)
    {
        return $this->isBound($abstract) ? $this->bindings[$abstract] : $abstract;
    }

    /**
     * isBound
     * @param $abstract
     * @return bool
     */
    protected function isBound($abstract)
    {
        return array_key_exists($abstract, $this->bindings);
    }

    /**
     * resolvePrimitive
     * @param ReflectionParameter $parameter
     * @return mixed|void
     */
    protected function resolvePrimitive(ReflectionParameter $parameter)
    {
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        return $this->unresolvablePrimitive($parameter);
    }

    /**
     * unresolvablePrimitive
     * @param ReflectionParameter $parameter
     * @throws BindingResolutionException
     */
    protected function unresolvablePrimitive(ReflectionParameter $parameter)
    {
        $message = "Unresolvable dependency resolving [$parameter] in class {$parameter->getDeclaringClass()->getName()}";
        throw new BindingResolutionException($message);
    }

    /**
     * getFromContainer
     * @param string $abstract
     * @return mixed
     */
    protected function getFromContainer($abstract)
    {
        return $this->container->make($abstract);
    }
}
