<?php
/**
 * Copyright (C) 2019 Brille24 GmbH.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 *
 * @license GPL-3.0
 *
 * @author Gerrit Addiks <gerrit.addiks@brille24.de>
 */

namespace Brille24\SyliusLdapPlugin\Menu;

use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;
use Knp\Menu\ItemInterface;

final class AdminMenuListener
{
    public function addAdminMenuItems(MenuBuilderEvent $event)
    {
        /** @var ItemInterface $menu */
        $menu = $event->getMenu();

        /** @var ItemInterface $configurationSuperMenu */
        $configurationSuperMenu = $menu->getChild("configuration");

        $configurationSuperMenu->addChild("ldap", [
            'label' => 'LDAP',
            'route' => 'sylius_admin_dashboard',
        ])->setLabelAttribute('icon', 'flag');
    }

}
