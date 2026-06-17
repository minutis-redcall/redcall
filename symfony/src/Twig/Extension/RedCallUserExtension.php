<?php

namespace App\Twig\Extension;

use App\Entity\User;
use App\Entity\Volunteer;
use App\Manager\RedCallUserResolver;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Exposes the "RedCall operator behind a volunteer" lookup to templates, now
 * that Volunteer no longer holds a getUser() entity link.
 *
 *   {% if redcall_user(volunteer) %} ...           {# trusted user exists #}
 *   {% if redcall_user_enabled(volunteer) %} ...    {# verified + trusted   #}
 *   {{ redcall_user(volunteer).displayName }}
 */
class RedCallUserExtension extends AbstractExtension
{
    /**
     * @var RedCallUserResolver
     */
    private $resolver;

    public function __construct(RedCallUserResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    public function getFunctions() : array
    {
        return [
            new TwigFunction('redcall_user', [$this, 'redcallUser']),
            new TwigFunction('redcall_user_enabled', [$this, 'redcallUserEnabled']),
            new TwigFunction('volunteer_of', [$this, 'volunteerOf']),
        ];
    }

    public function redcallUser(?Volunteer $volunteer) : ?User
    {
        return $this->resolver->resolve($volunteer);
    }

    public function redcallUserEnabled(?Volunteer $volunteer) : bool
    {
        return $this->resolver->isEnabled($volunteer);
    }

    public function volunteerOf(?User $user) : ?Volunteer
    {
        return $this->resolver->volunteerOf($user);
    }
}
