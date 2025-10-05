<?php
namespace App\Security;

use Symfony\Component\Ldap\LdapInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Ldap\Security\RoleFetcherInterface;
use Symfony\Component\Ldap\Entry;

final readonly class LdapRoleFetcher implements RoleFetcherInterface
{
    /**
     * @param array<string, string> $mapping
     */
    public function __construct(
        private array $mapping,
        private string $attributeName = 'ismemberof',
        private string $groupNameRegex = '/^CN=(?P<group>[^,]+),ou.*$/i',
    ) {
    }

    /**
     * @return string[]
     */
    public function fetchRoles(Entry $entry): array
    {
        if (!$entry->hasAttribute($this->attributeName)) {
            return [];
        }

        $roles = [];
        foreach ($entry->getAttribute($this->attributeName) as $group) {
            $groupName = $this->getGroupName($group);
            if (\array_key_exists($groupName, $this->mapping)) {
                $roles[] = $this->mapping[$groupName];
            }
        }

        return array_unique($roles);
    }

    private function getGroupName(string $group): string
    {
        if (preg_match($this->groupNameRegex, $group, $matches)) {
            return $matches['group'];
        }

        return $group;
    }
}