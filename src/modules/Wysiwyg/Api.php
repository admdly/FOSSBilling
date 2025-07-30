<?php

declare(strict_types=1);
/**
 * Copyright 2022-2025 FOSSBilling
 * Copyright 2011-2021 BoxBilling, Inc.
 * SPDX-License-Identifier: Apache-2.0.
 *
 * @copyright FOSSBilling (https://www.fossbilling.org)
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 */

namespace Box\Mod\Wysiwyg;

class Api extends \Api_Abstract
{
    /**
     * Returns the configured WYSIWYG editor.
     *
     * @admin
     *
     * @return string
     */
    public function editor()
    {
        $this->assertAdmin();
        $mod = $this->di['mod']('wysiwyg');
        $config = $mod->getConfig();
        return $config['editor'] ?? 'ckeditor';
    }

    /**
     * Returns available WYSIWYG editors.
     *
     * @admin
     *
     * @return array
     */
    public function editors()
    {
        $this->assertAdmin();
        return [
            'ckeditor' => 'CKEditor',
        ];
    }
}
