<?php

namespace Webburza\Sylius\LocationBundle\EventListener;

use Sylius\Bundle\WebBundle\Event\MenuBuilderEvent;
use Symfony\Component\Translation\DataCollectorTranslator;
use Symfony\Component\Translation\Translator;

class MenuBuilderListener
{
    /**
     * @var Translator
     */
    protected $translator;

    /**
     * MenuBuilderListener constructor.
     *
     * @param DataCollectorTranslator $translator
     */
    public function __construct(DataCollectorTranslator $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param MenuBuilderEvent $event
     */
    public function addBackendMenuItems(MenuBuilderEvent $event)
    {
        $menu = $event->getMenu();

        $menu['content']
            ->addChild('webburza_sylius_locations', array(
                'route' => 'webburza_location_location_index',
                'labelAttributes' => array('icon' => 'glyphicon glyphicon-flag'),
            ))
            ->setLabel($this->translator->trans('webburza.sylius.location.backend.locations'));

        $menu['content']
            ->addChild('webburza_sylius_location_types', array(
                'route' => 'webburza_location_location_type_index',
                'labelAttributes' => array('icon' => 'glyphicon glyphicon-cog'),
            ))
            ->setLabel($this->translator->trans('webburza.sylius.location.backend.location_types'));
    }
}