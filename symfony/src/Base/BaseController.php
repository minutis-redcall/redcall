<?php

namespace App\Base;

use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\FormType;

class BaseController extends Controller
{
    const PAGER_PER_PAGE_DEFAULT = 20;

    public function orderBy(QueryBuilder $qb,
        $class,
        $prefixedDefaultColumn,
        $defaultDirection = 'ASC',
        $prefix = '')
    {
        $request = $this->get('request_stack')->getMasterRequest();

        if (strpos($prefixedDefaultColumn, '.') === false) {
            throw new \LogicException("Invalid format of the given doctrine default column: {$prefixedDefaultColumn}.");
        }

        $qbPrefix      = substr($prefixedDefaultColumn, 0, strpos($prefixedDefaultColumn, '.'));
        $defaultColumn = substr($prefixedDefaultColumn, strpos($prefixedDefaultColumn, '.') + 1);

        if (!class_exists($class)) {
            throw new \LogicException("Class '$class' not found.");
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

    public function getPager($data, $prefix = '', $hasJoins = false)
    {
        $request = $this->get('request_stack')->getMasterRequest();

        $adapter = null;
        if ($data instanceof QueryBuilder) {
            $adapter = new DoctrineORMAdapter($data, $hasJoins);
        } elseif (is_array($data)) {
            $adapter = new ArrayAdapter($data);
        } else {
            throw new \RuntimeException('This data type has no Pagerfanta adapter yet.');
        }

        $pager = new Pagerfanta($adapter);
        $pager->setNormalizeOutOfRangePages(true);

        $pager->setMaxPerPage(self::PAGER_PER_PAGE_DEFAULT);
        $pager->setCurrentPage($request->request->get($prefix.'page') ?: $request->query->get($prefix.'page', 1));

        return $pager;
    }

    public function getManager($manager = null)
    {
        $em = $this
            ->get('doctrine')
            ->getManager();

        if (!is_null($manager)) {
            return $em->getRepository($manager);
        }

        return $em;
    }

    public function createNamedFormBuilder($name, $type = FormType::class, $data = null, array $options = [])
    {
        return $this->container->get('form.factory')->createNamedBuilder($name, $type, $data, $options);
    }

    public function validateCsrfOrThrowNotFoundException(string $id, ?string $token): void
    {
        if (!$token || !is_scalar($token) || !$this->isCsrfTokenValid($id, $token)) {
            throw $this->createNotFoundException();
        }
    }

    protected function trans($property, array $parameters = [])
    {
        return $this->container->get('translator')->trans($property, $parameters);
    }
}