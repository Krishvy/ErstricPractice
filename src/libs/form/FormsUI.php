<?php

declare(strict_types=1);

namespace Krishvy\libs\form;

use pocketmine\plugin\PluginBase;

class FormsUI extends PluginBase
{

    /**
     * @param callable|null $function
     * @return CustomForm
     * @deprecated
     *
     */
    public function createCustomForm(?callable $function = null): CustomForm
    {
        return new CustomForm($function);
    }

    /**
     * @param callable|null $function
     * @return SimpleForm
     * @deprecated
     *
     */
    public function createSimpleForm(?callable $function = null): SimpleForm
    {
        return new SimpleForm($function);
    }

    /**
     * @param callable|null $function
     * @return ModalForm
     * @deprecated
     *
     */
    public function createModalForm(?callable $function = null): ModalForm
    {
        return new ModalForm($function);
    }
}