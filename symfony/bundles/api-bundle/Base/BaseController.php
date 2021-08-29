<?php

namespace Bundles\ApiBundle\Base;

use App\Contract\LockableInterface;
use App\Enum\Crud;
use App\Enum\Resource;
use App\Enum\ResourceOwnership;
use App\Facade\Generic\UpdateStatusFacade;
use App\Facade\Resource\ResourceReferenceCollectionFacadeInterface;
use App\Facade\Resource\ResourceReferenceFacadeInterface;
use App\Security\Helper\Security;
use Bundles\ApiBundle\Error\ViolationError;
use Bundles\ApiBundle\Exception\ApiException;
use Bundles\ApiBundle\Model\Facade\CollectionFacade;
use Doctrine\Common\Collections\Collection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class BaseController extends AbstractController
{
    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            array_values(array_map(function (Resource $resource) {
                return $resource->getManager();
            }, Resource::values())),
            [
                'validator' => ValidatorInterface::class,
                Security::class,
            ]
        );
    }

    /**
     * This method performs ADD or DELETE on resource collections.
     *
     * Some collection examples:
     * - $user->getStructures() is a collection
     * - $volunteer->getBadges(), $volunteer->getStructures(), $volunteer->getPhones() are collections
     * - $structure->getVolunteers(), $structure->getUsers()
     *
     * In the API, we can have this kind of updates using any source or target resource,
     * so instead of writing a large piece of code and copy/paste it to change the source,
     * the target and the updated property, we do that here in a generic way.
     *
     * 1) User provide a list of resources that should be added or removed from a collection
     * 2) Once these resources have been resolved we perform sanity checks
     * 3) Then, we add or remove the resource, taking care of which object contains the collection
     */
    protected function updateResourceCollection(
        Crud $action,
        Resource $knownResourceType,
        $knownResource,
        Resource $resourcesToResolveType,
        ResourceReferenceCollectionFacadeInterface $resourcesToResolve,
        string $collectionPropertyName,
        ResourceOwnership $resourceCollectionOwner,
        ResourceOwnership $doctrineOwningSide
    ) : CollectionFacade {
        $response = new CollectionFacade();
        $changes  = 0;

        foreach ($resourcesToResolve->getEntries() as $entry) {

            // $volunteer = $this->volunteerManager->findOneByExternalId($this->getPlatform(), $entry->getExternalId());
            /** @var ResourceReferenceFacadeInterface $entry */
            $resolvedResource = call_user_func([
                $this->get($resourcesToResolveType->getManager()),
                $resourcesToResolveType->getProviderMethod(),
            ], $this->getPlatform(), $entry->getExternalId());

            // if (null === $volunteer) {
            if (null === $resolvedResource) {
                $response[] = new UpdateStatusFacade(
                    $entry->getExternalId(),
                    false,
                    sprintf('%s does not exist.', $resourcesToResolveType->getDisplayName())
                );

                continue;
            }

            // if (!$this->isGranted('VOLUNTEER', $volunteer)) {
            if (!$this->isGranted($resourcesToResolveType->getVoter(), $resolvedResource)) {
                $response[] = new UpdateStatusFacade(
                    $entry->getExternalId(),
                    false,
                    sprintf('you do not have the right privileges to update that %s.', $resourcesToResolveType->getDisplayName())
                );

                continue;
            }

            // if ($volunteer->isLocked()) {
            if ($resolvedResource instanceof LockableInterface && $resolvedResource->isLocked()) {
                $response[] = new UpdateStatusFacade(
                    $entry->getExternalId(),
                    false,
                    sprintf('this %s is locked.', $resourcesToResolveType->getDisplayName())
                );

                continue;
            }

            // If property name = badge, collection getter will be getBadges()
            // We also determine adders and removers because we do not use collections by references,
            // If you look at Volunteer::getBadges(), it returns a copy of the collection.
            $suffix            = sprintf('%s%s', strtoupper(substr($collectionPropertyName, 0, 1)), substr($collectionPropertyName, 1));
            $collectionGetter  = sprintf('get%s', 'Children' === $suffix ? 'Children' : sprintf('%ss', $suffix));
            $collectionAdder   = sprintf('add%s', 'Children' === $suffix ? 'Child' : $suffix);
            $collectionRemover = sprintf('remove%s', 'Children' === $suffix ? 'Child' : $suffix);

            // If known resource is Volunteer and resource to resolve is Badge,
            // we want to add badges to the volunteer, so the collection will
            // be $knownResource->getBadges(); We add "false" because a few
            // resource getters return enabled resources by default.
            if (ResourceOwnership::KNOWN_RESOURCE()->equals($resourceCollectionOwner)) {
                $ownerResource    = $knownResource;
                $ownerType        = $knownResourceType;
                $toChangeResource = $resolvedResource;
                $toChangeType     = $resourcesToResolveType;
                $collection       = call_user_func([$knownResource, $collectionGetter], false);
            } else {
                $ownerResource    = $resolvedResource;
                $ownerType        = $resourcesToResolveType;
                $toChangeResource = $knownResource;
                $toChangeType     = $knownResourceType;
                $collection       = call_user_func([$resolvedResource, $collectionGetter], false);
            }
            if (!$collection instanceof Collection) {
                throw new \LogicException(sprintf('Collection must be an instance of %s', Collection::class));
            }

            $shouldPersist = false;
            switch ($action) {
                case Crud::CREATE():

                    // if ($volunteer->getBadges()->contains($badge)) {
                    if ($collection->contains($toChangeResource)) {
                        $response[] = new UpdateStatusFacade(
                            $entry->getExternalId(),
                            false,
                            sprintf('%s already have that %s', $ownerType->getDisplayName(), $toChangeType->getDisplayName())
                        );

                        continue 2;
                    }

                    // $volunteer->addBadge($badge);
                    call_user_func([$ownerResource, $collectionAdder], $toChangeResource);

                    $shouldPersist = true;

                    break;

                case Crud::DELETE():
                    if (!$collection->contains($toChangeResource)) {
                        $response[] = new UpdateStatusFacade(
                            $entry->getExternalId(),
                            false,
                            sprintf('%s does not have that %s', $ownerType->getDisplayName(), $toChangeType->getDisplayName())
                        );

                        continue 2;
                    }

                    // $volunteer->removeBadge($badge);
                    call_user_func([$ownerResource, $collectionRemover], $toChangeResource);

                    $shouldPersist = true;

                    break;
            }

            $violations = $this->get('validator')->validate($ownerResource);
            if ($violations->count()) {
                $response[] = new UpdateStatusFacade(
                    $entry->getExternalId(),
                    false,
                    '%s contain %d violations: %s',
                    $ownerType->getDisplayName(),
                    $violations->count(),
                    $violations
                );

                continue;
            }

            $violations = $this->get('validator')->validate($toChangeResource);
            if ($violations->count()) {
                $response[] = new UpdateStatusFacade(
                    $entry->getExternalId(),
                    false,
                    '%s contain %d violations: %s',
                    $toChangeType->getDisplayName(),
                    $violations->count(),
                    $violations
                );

                continue;
            }

            if ($shouldPersist) {
                // Persistence should be made on the owning side of the relation
                // $this->volunteerManager->save($volunteer);
                if (ResourceOwnership::KNOWN_RESOURCE()->equals($doctrineOwningSide)) {
                    call_user_func([
                        $this->get($ownerType->getManager()),
                        $ownerType->getPersisterMethod(),
                    ], $ownerResource);
                } else {
                    call_user_func([
                        $this->get($toChangeType->getManager()),
                        $toChangeType->getPersisterMethod(),
                    ], $toChangeResource);
                }
            }

            $changes++;
            $response[] = new UpdateStatusFacade($entry->getExternalId());
        }

        return $response;
    }

    protected function validate($value, array $constraints = [], array $groups = ['Default'])
    {
        // Trying to validate native object constraints
        $violations = $this->get('validator')->validate($value, null, $groups);

        // Checking given constraints
        if ($constraints) {
            $violations->addAll(
                $this->get('validator')->validate($value, $constraints, $groups)
            );
        }

        if (count($violations)) {
            throw new ApiException(
                new ViolationError($violations)
            );
        }
    }

    protected function getPlatform() : ?string
    {
        return $this->getSecurity()->getPlatform();
    }

    protected function getSecurity() : Security
    {
        return $this->get(Security::class);
    }
}