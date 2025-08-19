<?php
/**
 * Copyright 2022-2025 FOSSBilling
 * Copyright 2011-2021 BoxBilling, Inc.
 * SPDX-License-Identifier: Apache-2.0.
 *
 * @copyright FOSSBilling (https://www.fossbilling.org)
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 */

namespace Box\Mod\Currency;

class Api extends \Api_Abstract
{
    /**
     * Get list of available currencies on system.
     *
     * @return array
     */
    public function get_list($data)
    {
        [$query, $params] = $this->getService()->getSearchQuery();
        $per_page = $data['per_page'] ?? $this->di['pager']->getDefaultPerPage();
        $pager = $this->di['pager']->getPaginatedResultSet($query, $params, $per_page);
        foreach ($pager['list'] as $key => $item) {
            $currency = $this->di['db']->getExistingModelById('Currency', $item['id'], 'Currency not found');
            $pager['list'][$key] = $this->getService()->toApiArray($currency);
        }

        return $pager;
    }

    /**
     * Get code title pairs of currencies.
     *
     * @return array
     */
    public function get_pairs($data = [])
    {
        $context = $this->getContext();
        if ($context === 'admin') {
            return $this->getService()->getAvailableCurrencies();
        }

        $this->requireContext(['guest']);
        return $this->getService()->getPairs();
    }

    /**
     * Return currency details by cde.
     *
     * @return array
     *
     * @throws \FOSSBilling\Exception
     */
    public function get($data)
    {
        $context = $this->getContext();
        if ($context === 'admin') {
            $required = [
                'code' => 'Currency code is missing',
            ];
            $this->di['validator']->checkRequiredParamsForArray($required, $data);

            $service = $this->getService();
            $model = $service->getByCode($data['code']);

            if (!$model instanceof \Model_Currency) {
                throw new \FOSSBilling\Exception('Currency not found');
            }

            return $service->toApiArray($model);
        }

        $this->requireContext(['guest']);
        $service = $this->getService();
        if (isset($data['code']) && !empty($data['code'])) {
            $model = $service->getByCode($data['code']);
        } else {
            $model = $service->getDefault();
        }

        if (!$model instanceof \Model_Currency) {
            throw new \FOSSBilling\Exception('Currency not found');
        }

        return $service->toApiArray($model);
    }

    /**
     * Return default system currency.
     *
     * @return array
     */
    public function get_default($data)
    {
        $service = $this->getService();
        $currency = $service->getDefault();

        return $service->toApiArray($currency);
    }

    /**
     * Add new currency to system.
     *
     * @optional string $title - custom currency title
     *
     * @return string - currency code
     *
     * @throws \FOSSBilling\Exception
     */
    public function create($data = [])
    {
        $required = [
            'code' => 'Currency code is missing',
            'format' => 'Currency format is missing',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $service = $this->getService();

        if ($service->getByCode($data['code'] ?? null)) {
            throw new \FOSSBilling\Exception('Currency already registered');
        }

        if (!array_key_exists($data['code'] ?? null, $service->getAvailableCurrencies())) {
            throw new \FOSSBilling\Exception('Currency code is invalid');
        }

        $title = $data['title'] ?? null;
        $conversionRate = $data['conversion_rate'] ?? null;

        return $service->createCurrency($data['code'] ?? null, $data['format'] ?? null, $title, $conversionRate);
    }

    /**
     * Updates system currency settings.
     *
     * @optional string $title - new currency title
     * @optional string $format - new currency format
     * @optional float $conversion_rate - new currency conversion rate
     *
     * @return bool
     *
     * @throws \FOSSBilling\Exception
     */
    public function update($data)
    {
        $required = [
            'code' => 'Currency code is missing',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $format = $data['format'] ?? null;
        $title = $data['title'] ?? null;
        $priceFormat = $data['price_format'] ?? null;
        $conversionRate = $data['conversion_rate'] ?? null;

        return $this->getService()->updateCurrency($data['code'], $format, $title, $priceFormat, $conversionRate);
    }

    /**
     * See if CRON jobs are enabled for currency rates.
     */
    public function is_cron_enabled($data): bool
    {
        return $this->getService()->isCronEnabled();
    }

    /**
     * Automatically update all currency rates.
     *
     * @return bool
     */
    public function update_rates($data)
    {
        return $this->service->updateCurrencyRates($data);
    }

    /**
     * Remove currency. Default currency cannot be removed.
     *
     * @return bool
     *
     * @throws \FOSSBilling\Exception
     */
    public function delete($data)
    {
        $required = [
            'code' => 'Currency code is missing',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        return $this->getService()->deleteCurrencyByCode($data['code']);
    }

    /**
     * Set default currency. If you have active orders or invoices
     * not recalculation on profits and refunds are made.
     *
     * @return bool
     *
     * @throws \FOSSBilling\Exception
     */
    public function set_default($data)
    {
        $required = [
            'code' => 'Currency code is missing',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $service = $this->getService();
        $model = $service->getByCode($data['code']);
        if (!$model instanceof \Model_Currency) {
            throw new \FOSSBilling\Exception('Currency not found');
        }

        return $service->setAsDefault($model);
    }

    /**
     * Gets the ISO defaults for a given currency code.
     */
    public function get_currency_defaults(array $data): array
    {
        if (!isset($data['code'])) {
            throw new \FOSSBilling\InformationException('Currency code not provided');
        }

        return $this->getService()->getCurrencyDefaults($data['code']);
    }

    /**
     * Format price by currency settings.
     *
     * @optional bool $convert - convert to default currency rate. Default - true;
     * @optional bool $without_currency - Show only number. No symbols are attached Default - false;
     * @optional float $price - Price to be formatted. Default 0
     * @optional string $code - currency code, ie: USD. Default - default currency
     *
     * @return string - formatted string
     */
    public function format($data = [])
    {
        $c = $this->get($data);

        $price = $data['price'] ?? 0;
        $convert = $data['convert'] ?? true;
        $without_currency = (bool) ($data['without_currency'] ?? false);

        $p = floatval($price);
        if ($convert) {
            $p = $price * $c['conversion_rate'];
        }

        if ($without_currency) {
            return $this->select_format($p, $c['price_format']);
        }

        // Price is negative, so we place a negative symbol at the start of the format
        if ($p < 0) {
            $c['format'] = '-' . $c['format'];
        }

        // Get the absolute value of the price so it displays normally for both positive and negative prices and then properly format it
        $p = $this->select_format(abs($p), $c['price_format']);

        return str_replace('{{price}}', $p, $c['format']);
    }

    private function select_format($p, $format)
    {
        return match (intval($format)) {
            2 => number_format($p, 2, '.', ','),
            3 => number_format($p, 2, ',', '.'),
            4 => number_format($p, 0, '', ','),
            5 => number_format($p, 0, '', ''),
            default => number_format($p, 2, '.', ''),
        };
    }
}
