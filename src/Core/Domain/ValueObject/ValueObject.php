<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\Domain\ValueObject;

use PrestaShop\PrestaShop\Core\ConstraintValidator\Factory\ConstraintValidatorFactory;
use PrestaShop\PrestaShop\Core\Domain\Exception\DomainConstraintException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class ValueObject is responsible for providing valid value.
 */
class ValueObject
{
    /**
     * @var ValidatorInterface
     */
    protected $validator;

    public function __construct()
    {
        $validatorBuilder = Validation::createValidatorBuilder();
        $validatorBuilder->setConstraintValidatorFactory(
            new ConstraintValidatorFactory()
        );
        $this->validator = $validatorBuilder->getValidator();
    }

    /**
     * @param mixed $value
     * @param Constraint[] $constraints
     *
     * @throws DomainConstraintException
     */
    protected function validate($value, array $constraints, ?string $errorMessage = null): void
    {
        $violations = $this->validator->validate($value, $constraints);

        if (0 === count($violations)) {
            return;
        }

        $violationsMessages = [];

        foreach ($violations as $violation) {
            $violationMessages[] = $violation->getMessage();
        }

        throw new DomainConstraintException(($errorMessage ? "$errorMessage :" : '') . PHP_EOL . implode(PHP_EOL, $violationsMessages));
    }
}
