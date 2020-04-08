<?php

declare(strict_types=1);
/**
 * Copyright (C) 2019 Brille24 GmbH.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 *
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit.addiks@brille24.de>
 */

namespace Brille24\SyliusLdapPlugin\Entity;

use Symfony\Component\Ldap\LdapInterface;

interface LdapEndpointInterface
{
    public function title(): string;

    public function isActive(): bool;

    public function host(): string;

    public function port(): int;

    public function version(): string;

    public function encryption(): string;

    public function connectionString(): string;

    public function optReferrals(): bool;

    public function options(): array;

    public function distinguishedName(): string;

    public function password(): string;

    public function createLdap(): LdapInterface;
}
