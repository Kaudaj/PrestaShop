#./vendor/bin/behat -c tests/Integration/Behaviour/behat.yml -s store
@restore-all-tables-before-feature
Feature: Store
  In order to be able to manage shops in Shop Parameters > Contact > Shops
  As a BO user
  I should be able to add a store and toggle its status

  Background:
    Given shop "shop1" with name "test_shop" exists
    And language "language1" with locale "fr-FR" exists
    And language with iso code "en" is the default one
    And single shop context is loaded

  Scenario: Add new store
    When I add new store "store1" with the following details:
      | country         | France                        |
      | postcode        | 75009                         |
      | city            | Paris                         |
      | latitude        | 48.88061405                   |
      | longitude       | 2.32792440                    |
      | phone           | +33(0)140183004               |
      | fax             | +33(0)140183004               |
      | email           | contact@prestashop.com        |
      | shops           | [shop1]                       |
      | name[en-US]     | PrestaShop                    |
      | address1[en-US] | 4 Jules Lefebvre St.          |
      | address1[fr-FR] | 4 Rue Jules Lefebvre          |
      | address2[en-US] | Paris 75009, France           |
      | address2[fr-FR] | 75009 Paris                   |
      | hours[en-US]    | 09:00AM - 06:30PM             |
      | hours[fr-FR]    | 9h a 18h30                    |
      | note[en-US]     | Best store of course.         |
      | note[fr-FR]     | Le meilleur magasin bien sur. |
    Then store "store1" should have the following details:
      | country         | France                        |
      | state           | 0                             |
      | postcode        | 75009                         |
      | city            | Paris                         |
      | latitude        | 48.88061405                   |
      | longitude       | 2.32792440                    |
      | phone           | +33(0)140183004               |
      | fax             | +33(0)140183004               |
      | email           | contact@prestashop.com        |
      | shops           | [shop1]                       |
      | name[en-US]     | PrestaShop                    |
      | name[fr-FR]     | PrestaShop                    |
      | address1[en-US] | 4 Jules Lefebvre St.          |
      | address1[fr-FR] | 4 Rue Jules Lefebvre          |
      | address2[en-US] | Paris 75009, France           |
      | address2[fr-FR] | 75009 Paris                   |
      | hours[en-US]    | 09:00AM - 06:30PM             |
      | hours[fr-FR]    | 9h a 18h30                    |
      | note[en-US]     | Best store of course.         |
      | note[fr-FR]     | Le meilleur magasin bien sur. |


  Scenario: Toggle existing store
    Given the store "store1" should have status enabled
    When I toggle "store1"
    Then the store "store1" should have status disabled
    When I toggle "store1"
    Then the store "store1" should have status enabled

  Scenario: Enabling and disabling multiple stores in bulk action
    When I add new store "StorePau" with the following details:
      | name[en-US]     | StorePau               |
      | enabled         | true                   |
      | address1[en-US] | 1 rue de la republique |
      | city            | Pau                    |
      | postcode        | 64000                  |
      | shops           | [shop1]                |
      | latitude        | 43.2951                |
      | longitude       | -0.370797              |
      | country         | France                 |
    And I add new store "StoreSerresCastet" with the following details:
      | name[en-US]     | StoreSerresCastet |
      | enabled         | true              |
      | address1[en-US] | 1 rue de la foire |
      | city            | Serres-Castet     |
      | postcode        | 64121             |
      | shops           | [shop1]           |
      | latitude        | 43.2951           |
      | longitude       | -0.370797         |
      | country         | France            |
    And I add new store "StoreBuros" with the following details:
      | name[en-US]     | StoreBuros          |
      | enabled         | true                |
      | address1[en-US] | 1 chemin de carrere |
      | city            | Buros               |
      | postcode        | 64160               |
      | shops           | [shop1]             |
      | latitude        | 43.2951             |
      | longitude       | -0.370797           |
      | country         | France              |
    Then stores "StorePau, StoreSerresCastet, StoreBuros" should be enabled
    When I disable multiple stores "StorePau, StoreSerresCastet" using bulk action
    Then stores "StorePau, StoreSerresCastet" should be disabled
    Then stores "StoreBuros" should be enabled
    When I enable multiple stores "StorePau, StoreSerresCastet" using bulk action
    Then stores "StorePau, StoreSerresCastet, StoreBuros" should be enabled

  Scenario: Delete stores
    When I add new store "StorePau" with the following details:
      | name[en-US]     | StorePau               |
      | enabled         | true                   |
      | address1[en-US] | 1 rue de la republique |
      | city            | Pau                    |
      | postcode        | 64000                  |
      | shops           | [shop1]                |
      | latitude        | 43.2951                |
      | longitude       | -0.370797              |
      | country         | France                 |
    And I add new store "StoreSerresCastet" with the following details:
      | name[en-US]     | StoreSerresCastet |
      | enabled         | true              |
      | address1[en-US] | 1 rue de la foire |
      | city            | Serres-Castet     |
      | postcode        | 64121             |
      | shops           | [shop1]           |
      | latitude        | 43.2951           |
      | longitude       | -0.370797         |
      | country         | France            |
    And stores "StorePau, StoreSerresCastet" should exist
    When I delete store "StorePau"
    Then stores "StorePau" should be deleted
    And stores "StoreSerresCastet" should exist

  Scenario: Delete multiple stores
    When I add new store "StorePau" with the following details:
      | name[en-US]     | StorePau               |
      | enabled         | true                   |
      | address1[en-US] | 1 rue de la republique |
      | city            | Pau                    |
      | postcode        | 64000                  |
      | shops           | [shop1]                |
      | latitude        | 43.2951                |
      | longitude       | -0.370797              |
      | country         | France                 |
    And I add new store "StoreSerresCastet" with the following details:
      | name[en-US]     | StoreSerresCastet |
      | enabled         | true              |
      | address1[en-US] | 1 rue de la foire |
      | city            | Serres-Castet     |
      | postcode        | 64121             |
      | shops           | [shop1]           |
      | latitude        | 43.2951           |
      | longitude       | -0.370797         |
      | country         | France            |
    And I add new store "StoreBuros" with the following details:
      | name[en-US]     | StoreBuros          |
      | enabled         | true                |
      | address1[en-US] | 1 chemin de carrere |
      | city            | Buros               |
      | postcode        | 64160               |
      | shops           | [shop1]             |
      | latitude        | 43.2951             |
      | longitude       | -0.370797           |
      | country         | France              |
    And stores "StorePau, StoreSerresCastet, StoreBuros" should exist
    When I delete stores "StorePau, StoreBuros" using bulk action
    Then stores "StorePau, StoreBuros" should be deleted
    And stores "StoreSerresCastet" should exist
