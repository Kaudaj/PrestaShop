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
 * @author    PrestaShop SA and Contributors <store@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

namespace Tests\Integration\Behaviour\Features\Context\Domain;

use Behat\Gherkin\Node\TableNode;
use Country;
use PHPUnit\Framework\Assert as Assert;
use PrestaShop\PrestaShop\Core\Domain\Store\Command\AddStoreCommand;
use PrestaShop\PrestaShop\Core\Domain\Store\Command\BulkDeleteStoreCommand;
use PrestaShop\PrestaShop\Core\Domain\Store\Command\BulkUpdateStoreStatusCommand;
use PrestaShop\PrestaShop\Core\Domain\Store\Command\DeleteStoreCommand;
use PrestaShop\PrestaShop\Core\Domain\Store\Command\ToggleStoreStatusCommand;
use PrestaShop\PrestaShop\Core\Domain\Store\Exception\StoreException;
use PrestaShop\PrestaShop\Core\Domain\Store\Exception\StoreNotFoundException;
use PrestaShop\PrestaShop\Core\Domain\Store\Query\GetStoreForEditing;
use PrestaShop\PrestaShop\Core\Domain\Store\QueryResult\StoreForEditing;
use PrestaShop\PrestaShop\Core\Domain\Store\ValueObject\StoreId;
use RuntimeException;
use Store;
use Tests\Integration\Behaviour\Features\Context\SharedStorage;
use Tests\Integration\Behaviour\Features\Context\Util\PrimitiveUtils;

class StoreFeatureContext extends AbstractDomainFeatureContext
{
    private const DUMMY_STORE_ID = 1;

    /**
     * @When I add new store :reference with the following details:
     */
    public function addNewStoreWithTheFollowingDetails(TableNode $table, string $reference): void
    {
        $data = $this->localizeByRows($table);

        try {
            $addStoreCommand = new AddStoreCommand(
                Country::getIdByName($this->getDefaultLangId(), $data['country']),
                $data['postcode'],
                $data['city'],
                $this->getShopIdsByReferences($data['shops'])
            );

            $dataHours = $data['hours'] ?? [];
            foreach ($dataHours as $key => $dataHour) {
                $dataHours[$key] = json_encode($dataHour);
            }

            $addStoreCommand
                ->setStateId($data['state'] ?? null)
                ->setLatitude($data['latitude'] ?? null)
                ->setLongitude($data['longitude'] ?? null)
                ->setPhone($data['phone'] ?? null)
                ->setFax($data['fax'] ?? null)
                ->setEmail($data['email'] ?? null)
                ->setLocalizedNames($data['name'] ?? [])
                ->setLocalizedAddress1($data['address1'] ?? [])
                ->setLocalizedAddress2($data['address2'] ?? [])
                ->setLocalizedHours($dataHours)
                ->setLocalizedNotes($data['note'] ?? [])
            ;

            /** @var StoreId */
            $storeId = $this->getCommandBus()->handle($addStoreCommand);

            SharedStorage::getStorage()->set($reference, $storeId->getValue());
        } catch (StoreException $e) {
            $this->setLastException($e);
        }
    }

    /**
     * @Then store :reference should have the following details:
     *
     * @param string $reference
     * @param TableNode $table
     */
    public function storeShouldHaveTheFollowingDetails(TableNode $table, string $reference): void
    {
        $data = $this->localizeByRows($table);

        foreach ($data['hours'] as $key => $dataHour) {
            $data['hours'][$key] = json_encode($dataHour);
        }

        /** @var int */
        $storeId = SharedStorage::getStorage()->get($reference);

        $expectedEditableStore = $this->mapToEditableStore($storeId, $data);

        /** @var StoreForEditing $editableStore */
        $editableStore = $this->getQueryBus()->handle(new GetStoreForEditing($storeId));

        Assert::assertEquals($expectedEditableStore, $editableStore);
    }

    /**
     * @When I toggle :reference
     *
     * @param string $reference
     */
    public function disableStoreWithReference(string $reference): void
    {
        $toggleStatusCommand = new ToggleStoreStatusCommand(self::DUMMY_STORE_ID);
        $this->getCommandBus()->handle($toggleStatusCommand);
    }

    /**
     * @When /^I (enable|disable) multiple stores "(.+)" using bulk action$/
     *
     * @param string $action
     * @param string $storeReferences
     */
    public function bulkToggleStatus(string $action, string $storeReferences): void
    {
        $expectedStatus = 'enable' === $action;
        $storeIds = [];

        foreach (PrimitiveUtils::castStringArrayIntoArray($storeReferences) as $storeReference) {
            /** @var int */
            $storeId = SharedStorage::getStorage()->get($storeReference);
            $storeIds[$storeReference] = $storeId;
        }

        $bulkUpdateCommand = new BulkUpdateStoreStatusCommand($expectedStatus, $storeIds);
        $this->getCommandBus()->handle($bulkUpdateCommand);

        foreach ($storeIds as $reference => $id) {
            SharedStorage::getStorage()->set($reference, $id);
        }
    }

    /**
     * @When I delete store :storeReference
     *
     * @param string $storeReference
     */
    public function deleteStore(string $storeReference): void
    {
        /** @var int */
        $storeId = SharedStorage::getStorage()->get($storeReference);

        try {
            $this->getCommandBus()->handle(new DeleteStoreCommand($storeId));
        } catch (StoreNotFoundException $e) {
            $this->setLastException($e);
        }
    }

    /**
     * @When /^I delete stores "(.+)" using bulk action$/
     *
     * @param string $storeReferences
     */
    public function bulkDeleteStores(string $storeReferences): void
    {
        $storeIds = [];
        foreach (PrimitiveUtils::castStringArrayIntoArray($storeReferences) as $storeReference) {
            /** @var int */
            $storeId = SharedStorage::getStorage()->get($storeReference);

            $storeIds[] = $storeId;
        }

        try {
            $this->getCommandBus()->handle(new BulkDeleteStoreCommand($storeIds));
        } catch (StoreNotFoundException $e) {
            $this->setLastException($e);
        }
    }

    /**
     * @Then /^the store "(.*)" should have status (enabled|disabled)$/
     *
     * @param string $reference
     * @param string $status
     */
    public function isStoreToggledWithReference(string $reference, string $status): void
    {
        $isEnabled = $status === 'enabled';
        $storeForEditingQuery = new GetStoreForEditing(self::DUMMY_STORE_ID);
        $storeUpdated = $this->getQueryBus()->handle($storeForEditingQuery);
        Assert::assertEquals((bool) $storeUpdated->isActive(), $isEnabled);
    }

    private function mapToEditableStore(int $storeId, array $data): StoreForEditing
    {
        return new StoreForEditing(
            $storeId,
            true,
            $this->getShopIdsByReferences($data['shops']),
            Country::getIdByName($this->getDefaultLangId(), $data['country']),
            $data['postcode'],
            $data['city'],
            $data['state'] ?? null,
            $data['latitude'] ?? null,
            $data['longitude'] ?? null,
            $data['phone'] ?? null,
            $data['fax'] ?? null,
            $data['email'] ?? null,
            $data['name'] ?? [],
            $data['address1'] ?? [],
            $data['address2'] ?? [],
            $data['hours'] ?? [],
            $data['note'] ?? []
        );
    }

    /**
     * @param string $shopReferencesAsString
     *
     * @return int[]
     */
    private function getShopIdsByReferences(string $shopReferencesAsString): array
    {
        $shopReferences = PrimitiveUtils::castStringArrayIntoArray($shopReferencesAsString);
        $shopIds = [];

        foreach ($shopReferences as $shopReference) {
            $shopIds[] = $this->getSharedStorage()->get($shopReference);
        }

        return $shopIds;
    }

    /**
     * @Then /^stores "(.+)" should be (enabled|disabled)$/
     *
     * @param string $storeReferences
     * @param string $expectedStatus
     */
    public function assertMultipleStoreStatus(string $storeReferences, string $expectedStatus): void
    {
        foreach (PrimitiveUtils::castStringArrayIntoArray($storeReferences) as $storeReference) {
            $this->assertStoreStatus($storeReference, $expectedStatus);
        }
    }

    public function assertStoreStatus(string $storeReference, string $expectedStatus): void
    {
        /** @var int */
        $storeId = SharedStorage::getStorage()->get($storeReference);

        try {
            /** @var StoreForEditing */
            $store = $this->getCommandBus()->handle(new GetStoreForEditing($storeId));
        } catch (StoreNotFoundException $e) {
            throw new RuntimeException(sprintf('Unable to retrieve store "%s" to check status', $storeReference));
        }

        $isEnabled = 'enabled' === $expectedStatus;
        $actualStatus = $store->isActive();

        if ($actualStatus !== $isEnabled) {
            throw new RuntimeException(sprintf('Store "%s" is %s, but it was expected to be %s', $storeReference, $actualStatus ? 'enabled' : 'disabled', $expectedStatus));
        }
    }

    /**
     * @Then /^stores "(.+)" should (exist|be deleted)$/
     *
     * @param string $storeReferences
     * @param string $expectedPresence
     */
    public function assertMultipleStorePresence(string $storeReferences, string $expectedPresence): void
    {
        foreach (PrimitiveUtils::castStringArrayIntoArray($storeReferences) as $storeReference) {
            $this->assertStorePresence($storeReference, $expectedPresence);
        }
    }

    public function assertStorePresence(string $storeReference, string $expectedPresence): void
    {
        /** @var int */
        $storeId = SharedStorage::getStorage()->get($storeReference);

        $isToBePresent = 'exist' === $expectedPresence;
        $isToBeDeleted = 'be deleted' === $expectedPresence;
        $query = new GetStoreForEditing($storeId);
        try {
            $storeQueried = $this->getQueryBus()->handle($query);
            if ($storeQueried && $isToBeDeleted) {
                throw new RuntimeException(sprintf('Store "%s" is present, but it was expected to be deleted', $storeReference));
            }
        } catch (StoreNotFoundException $e) {
            if ($isToBePresent) {
                throw new RuntimeException(sprintf('Store "%s" is present, but it was expected to be deleted', $storeReference));
            }
            SharedStorage::getStorage()->clear($storeReference);
        }
    }
}
