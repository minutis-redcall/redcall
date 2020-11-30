<?php

namespace App\Base;

use Bundles\PaginationBundle\Manager\PaginationManager;
use Doctrine\ORM\QueryBuilder;
use LogicException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Test\FormBuilderInterface;

class BaseController extends Controller
{
    public function orderBy(QueryBuilder $qb,
        $class,
        $prefixedDefaultColumn,
        $defaultDirection = 'ASC',
        $prefix = '')
    {
        $request = $this->get('request_stack')->getMasterRequest();

        if (strpos($prefixedDefaultColumn, '.') === false) {
            throw new LogicException("Invalid format of the given doctrine default column: {$prefixedDefaultColumn}.");
        }

        $qbPrefix      = substr($prefixedDefaultColumn, 0, strpos($prefixedDefaultColumn, '.'));
        $defaultColumn = substr($prefixedDefaultColumn, strpos($prefixedDefaultColumn, '.') + 1);

        if (!class_exists($class)) {
            throw new LogicException("Class '$class' not found.");
        }

        $column = $request->get($prefix.'order-by', $defaultColumn);
        if (!property_exists($class, $column)) {
            $column = $defaultColumn;
        }

        $direction = strtoupper($request->get($prefix.'order-by-direction', $defaultDirection));
        if ($direction !== 'ASC' && $direction !== 'DESC') {
            $direction = $defaultDirection;
        }

        $qb->orderBy($qbPrefix.'.'.$column, $direction);

        return [
            'prefix'    => $prefix,
            'column'    => explode(' ', $column)[0],
            'direction' => $direction,
        ];
    }

    public function getPager($data, $prefix = '', $hasJoins = false) : Pagerfanta
    {
        return $this->get(PaginationManager::class)->getPager($data, $prefix, $hasJoins);
    }

    /**
     * @param string     $name
     * @param string     $type
     * @param array|null $data
     * @param array      $options
     *
     * @return FormBuilderInterface
     */
    public function createNamedFormBuilder($name, $type = FormType::class, $data = null, array $options = [])
    {
        return $this->container->get('form.factory')->createNamedBuilder($name, $type, $data, $options);
    }

    public function validateCsrfOrThrowNotFoundException(string $id, ?string $token) : void
    {
        if (!$token || !is_scalar($token) || !$this->isCsrfTokenValid($id, $token)) {
            throw $this->createNotFoundException();
        }
    }

    public function info($message, array $parameters = [])
    {
        $this->addFlash('info', $this->trans($message, $parameters));
    }

    public function alert($message, array $parameters = [])
    {
        $this->addFlash('alert', $this->trans($message, $parameters));
    }

    public function danger($message, array $parameters = [])
    {
        $this->addFlash('danger', $this->trans($message, $parameters));
    }

    public function success($message, array $parameters = [])
    {
        $this->addFlash('success', $this->trans($message, $parameters));
    }

    protected function trans($property, array $parameters = [])
    {
        return $this->container->get('translator')->trans($property, $parameters);
    }
}