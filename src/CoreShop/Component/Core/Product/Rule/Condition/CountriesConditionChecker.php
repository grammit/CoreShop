<?php
/**
 * CoreShop.
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2015-2017 Dominik Pfaffenbauer (https://www.pfaffenbauer.at)
 * @license    https://www.coreshop.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace CoreShop\Component\Core\Product\Rule\Condition;

use CoreShop\Component\Address\Context\CountryContextInterface;
use CoreShop\Component\Core\Model\CountryInterface;
use CoreShop\Component\Resource\Model\ResourceInterface;
use CoreShop\Component\Rule\Condition\ConditionCheckerInterface;
use CoreShop\Component\Rule\Model\RuleInterface;

final class CountriesConditionChecker implements ConditionCheckerInterface
{
    /**
     * @var CountryContextInterface
     */
    private $countryContext;

    /**
     * @param CountryContextInterface $countryContext
     */
    public function __construct(CountryContextInterface $countryContext)
    {
        $this->countryContext = $countryContext;
    }

    /**
     * {@inheritdoc}
     */
    public function isValid(ResourceInterface $subject, RuleInterface $rule, array $configuration, $params = [])
    {
        $country = $this->countryContext->getCountry();

        if (!$country instanceof CountryInterface) {
            return false;
        }

        return in_array($country->getId(), $configuration['countries']);
    }
}
