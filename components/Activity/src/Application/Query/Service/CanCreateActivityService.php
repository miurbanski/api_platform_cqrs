<?php

declare(strict_types=1);

namespace App\Activity\Application\Query\Service;

use App\Application\CurrentUserInterface;
use App\Entity\Users;
use App\Repository\PermissionRepository;

class CanCreateActivityService
{
    private CurrentUserInterface $currentUser;
    private PermissionRepository $permissionRepository;

    public function __construct(CurrentUserInterface $currentUser, PermissionRepository $permissionRepository)
    {
        $this->currentUser = $currentUser;
        $this->permissionRepository = $permissionRepository;
    }

    public function can(): bool
    {
        $roles = $this->currentUser->getRoles();
        $permissions = $this->permissionRepository->findBy(['can_create_tasks' => true]);
        $rolesWithPermissions = array_map(fn ($permission) => $permission->getRoleName(), $permissions);
        $rolesWithPermissions[] = Users::ROLE_ADMIN;
        $rolesWithPermissions[] = Users::ROLE_ROOT;
        $diff = array_intersect($rolesWithPermissions, $roles);

        return count($diff) > 0;
    }
}
