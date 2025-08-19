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

namespace Box\Mod\Cart;

class Api extends \Api_Abstract
{
    /**
     * Get list of shopping carts.
     *
     * @todo Admin only.
     *
     * @param array $data.
     *
     * @return array
     */
    public function get_list($data): array
    {
        $this->requireContext(['admin']);

        [$sql, $params] = $this->getService()->getSearchQuery($data);
        $per_page = $data['per_page'] ?? $this->di['pager']->getDefaultPerPage();
        $pager = $this->di['pager']->getPaginatedResultSet($sql, $params, $per_page);

        foreach ($pager['list'] as $key => $cartArr) {
            $cart = $this->di['db']->getExistingModelById('Cart', $cartArr['id'], 'Cart not found');
            $pager['list'][$key] = $this->getService()->toApiArray($cart);
        }

        return $pager;
    }

    /**
     * Get the contents of a shopping cart by ID.
     *
     * @todo Admin, Guest, and Client contexts.
     *
     * @param array $data Data array.
     *
     * @return array Contents of the shopping cart.
     */
    public function get($data = [])
    {
        $context = $this->getContext();

        if ($context === 'admin') {
            // Admin can get any cart by ID.
            $required = [
                'id' => 'Shopping cart id is missing',
            ];
            $this->di['validator']->checkRequiredParamsForArray($required, $data);

            $cart = $this->di['db']->getExistingModelById('Cart', $data['id'], 'Shopping cart not found');

            return $this->getService()->toApiArray($cart);
        } else {
            // Guest/Client gets their session cart
            $this->requireContext(['guest', 'client']);

            $cart = $this->getService()->getSessionCart();
            return $this->getService()->toApiArray($cart);
        }
    }

    /**
     * Remove shopping carts that are older than a week and was not ordered.
     *
     * @todo Admin only.
     *
     * @return bool
     */
    public function batch_expire($data)
    {
        $this->requireContext(['admin']);

        $this->di['logger']->info('Executed action to clear expired shopping carts from database');

        $query = 'SELECT id, created_at FROM `cart` WHERE DATEDIFF(CURDATE(), created_at) > 7;';
        $list = $this->di['db']->getAssoc($query);
        if ($list) {
            foreach ($list as $id => $created_at) {
                $this->di['db']->exec('DELETE FROM `cart_product` WHERE cart_id = :id', [':id' => $id]);
                $this->di['db']->exec('DELETE FROM `cart` WHERE id = :id', [':id' => $id]);
            }
            $this->di['logger']->info('Removed %s expired shopping carts', is_countable($list) ? count($list) : 0);
        }

        return true;
    }

    /**
     * Checkout a shopping cart that has products in it.
     *
     * @todo Client only.
     *
     * @param array $data Checkout data.
     */
    public function checkout($data)
    {
        $this->requireContext(['client']);

        $gateway_id = $data['gateway_id'] ?? null;
        $cart = $this->getService()->getSessionCart();
        $client = $this->getIdentity();

        return $this->getService()->checkoutCart($cart, $client, $gateway_id);
    }

    // === GUEST CONTEXT METHODS (also available to clients) ===

    /**
     * Completely remove shopping cart contents.
     *
     * @todo Guest/Client contexts.
     *
     * @return bool
     */
    public function reset()
    {
        $this->requireContext(['guest', 'client']);

        $cart = $this->getService()->getSessionCart();

        return $this->getService()->resetCart($cart);
    }

    /**
     * Set shopping cart currency.
     *
     * @todo Guest/Client contexts.
     *
     * @return bool
     */
    public function set_currency($data)
    {
        $this->requireContext(['guest', 'client']);

        $required = [
            'currency' => 'Currency code not passed',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $currencyService = $this->di['mod_service']('currency');
        $currency = $currencyService->getByCode($data['currency']);
        if (!$currency instanceof \Model_Currency) {
            throw new \FOSSBilling\Exception('Currency not found');
        }
        $cart = $this->getService()->getSessionCart();

        return $this->getService()->changeCartCurrency($cart, $currency);
    }

    /**
     * Retrieve information about currently selected shopping cart currency.
     *
     * @todo Guest/Client contexts.
     *
     * @return array Currency details
     */
    public function get_currency()
    {
        $this->requireContext(['guest', 'client']);

        $cart = $this->getService()->getSessionCart();

        $currencyService = $this->di['mod_service']('currency');
        $currency = $this->di['db']->load('Currency', $cart->currency_id);
        if (!$currency instanceof \Model_Currency) {
            $currency = $currencyService->getDefault();
        }

        return $currencyService->toApiArray($currency);
    }

    /**
     * Apply Promo code to shopping cart.
     *
     * @todo Guest/Client contexts.
     *
     * @return bool
     */
    public function apply_promo($data)
    {
        $this->requireContext(['guest', 'client']);

        $required = [
            'promocode' => 'Promo code not passed',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $promo = $this->getService()->findActivePromoByCode($data['promocode']);
        if (!$promo instanceof \Model_Promo) {
            throw new \FOSSBilling\InformationException('The promo code has expired or does not exist');
        }

        if (!$this->getService()->isPromoAvailableForClientGroup($promo)) {
            throw new \FOSSBilling\InformationException('Promo code cannot be applied to your account');
        }

        if (!$this->getService()->promoCanBeApplied($promo)) {
            throw new \FOSSBilling\InformationException('The promo code has expired or does not exist');
        }

        $cart = $this->getService()->getSessionCart();

        return $this->getService()->applyPromo($cart, $promo);
    }

    /**
     * Removes promo from shopping cart and resets discounted prices if any.
     *
     * @todo Guest/Client contexts.
     *
     * @return bool
     */
    public function remove_promo()
    {
        $this->requireContext(['guest', 'client']);

        $cart = $this->getService()->getSessionCart();

        return $this->getService()->removePromo($cart);
    }

    /**
     * Removes product from shopping cart.
     *
     * @todo Guest/Client contexts.
     *
     * @return bool
     */
    public function remove_item($data)
    {
        $this->requireContext(['guest', 'client']);

        $required = [
            'id' => 'Cart item id not passed',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $cart = $this->getService()->getSessionCart();

        return $this->getService()->removeProduct($cart, $data['id'], true);
    }

    /**
     * Add a product to the shopping cart.
     *
     * @todo Guest/Client contexts.
     *
     * @param array $data Product data
     * @return bool
     */
    public function add_item($data)
    {
        $this->requireContext(['guest', 'client']);

        $required = [
            'id' => 'Product id not passed',
        ];

        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $cart = $this->getService()->getSessionCart();

        $product = $this->di['db']->getExistingModelById('Product', $data['id'], 'Product not found');

        if ($product->is_addon) {
            throw new \FOSSBilling\InformationException('Addon products cannot be added separately.');
        }

        if (is_array($data['addons'] ?? '')) {
            $validAddons = json_decode($product->addons ?? '');
            if (empty($validAddons)) {
                $validAddons = [];
            }

            foreach ($data['addons'] as $addon => $properties) {
                if ($properties['selected']) {
                    $addonModel = $this->di['db']->getExistingModelById('Product', $addon, 'Addon not found');

                    if ($addonModel->status !== 'enabled' || !in_array($addon, $validAddons)) {
                        throw new \FOSSBilling\InformationException('One or more of your selected add-ons are invalid for the associated product.');
                    }
                }
            }
        }

        // reset cart by default
        if (!isset($data['multiple']) || !$data['multiple']) {
            $this->reset();
        }

        return $this->getService()->addItem($cart, $product, $data);
    }
}
