<?php

namespace App\Tests\Controller\Component;

use App\Tests\CustomTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use App\Controller\Component\ConfigManagerController;

/**
 * Class ConfigManagerControllerTest
 *
 * Test cases for config manager component
 *
 * @package App\Tests\Controller\Component
 */
#[CoversClass(ConfigManagerController::class)]
class ConfigManagerControllerTest extends CustomTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        // simulate user authentication
        $this->simulateLogin($this->client);

        // unlink config file before test (prevent overwriting)
        if (file_exists('blocked-usernames.json')) {
            unlink('blocked-usernames.json');
        }

        // unlink config file before test (prevent overwriting)
        if (!file_exists('terminal-blocked-commands.json')) {
            file_put_contents('terminal-blocked-commands.json', '{}');
        }

        // create feature flag config file
        if (!file_exists('feature-flags.json')) {
            file_put_contents('feature-flags.json', '{"test-feature": false}');
        }
    }

    protected function tearDown(): void
    {
        // unlink config file after test
        if (file_exists('blocked-usernames.json')) {
            unlink('blocked-usernames.json');
        }

        // unlink config file after test
        if (file_exists('terminal-blocked-commands.json')) {
            unlink('terminal-blocked-commands.json');
        }

        // unlink config file after test
        if (file_exists('feature-flags.json')) {
            unlink('feature-flags.json');
        }

        parent::tearDown();
    }

    /**
     * Test load settings selector page
     *
     * @return void
     */
    public function testLoadSettingsSelectorPage(): void
    {
        $this->client->request('GET', '/settings');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertAnySelectorTextContains('p', 'Select settings category');
        $this->assertSelectorExists('button[id="menu-toggle"]');
        $this->assertSelectorExists('a[title="Logout user"]');
        $this->assertSelectorExists('a[href="/settings"]');
        $this->assertSelectorExists('a[href="/logout"]');
        $this->assertSelectorExists('aside[id="sidebar"]');
        $this->assertSelectorExists('img[alt="profile picture"]');
        $this->assertSelectorExists('h3[id="username"]');
        $this->assertSelectorExists('span[id="role"]');
        $this->assertSelectorExists('a[href="/dashboard"]');
        $this->assertSelectorExists('a[href="/manager/logs"]');
        $this->assertSelectorExists('a[href="/manager/users"]');
        $this->assertSelectorExists('main[id="main-content"]');
        $this->assertSelectorTextContains('body', 'Settings');
        $this->assertSelectorTextContains('body', 'Manage your account preferences and security');
        $this->assertSelectorExists('a[href="/account/settings"]');
        $this->assertSelectorTextContains('body', 'Manage main admin-suite configuration files');
        $this->assertSelectorExists('a[href="/settings/suite"]');
        $this->assertSelectorTextContains('body', 'Manage feature flags');
        $this->assertSelectorExists('a[href="/settings/feature-flags"]');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test load suite configurations list page
     *
     * @return void
     */
    public function testLoadSuiteConfigIndexPage(): void
    {
        $this->client->request('GET', '/settings/suite');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertAnySelectorTextContains('p', 'Manage suite-wide configuration files');
        $this->assertSelectorExists('button[id="menu-toggle"]');
        $this->assertSelectorTextContains('body', 'Suite Configuration');
        $this->assertSelectorTextContains('body', 'Manage suite-wide configuration files');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test load suite configuration show page
     *
     * @return void
     */
    public function testLoadSuiteConfigShowPage(): void
    {
        $this->client->request('GET', '/settings/suite/show?filename=terminal-aliases.json');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorExists('button[id="menu-toggle"]');
        $this->assertSelectorTextContains('body', 'View Configuration');
        $this->assertSelectorTextContains('body', 'Config: terminal-aliases.json');
        $this->assertSelectorExists('a[href="/settings/suite/create?filename=terminal-aliases.json"]');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test create custom suite configuration file when filename is not set
     *
     * @return void
     */
    public function testCreateCustomSuiteConfigFileWhenFilenameIsNotSet(): void
    {
        $this->client->request('GET', '/settings/suite/create');

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    /**
     * Test create custom suite configuration file
     *
     * @return void
     */
    public function testCreateCustomSuiteConfigFile(): void
    {
        $this->client->request('GET', '/settings/suite/create', [
            'filename' => 'blocked-usernames.json'
        ]);

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }

    /**
     * Test delete suite configuration file when filename is not set
     *
     * @return void
     */
    public function testDeleteSuiteConfigFileWhenFilenameIsNotSet(): void
    {
        $this->client->request('GET', '/settings/suite/delete');

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    /**
     * Test delete suite configuration file
     *
     * @return void
     */
    public function testDeleteSuiteConfigFile(): void
    {
        $this->client->request('GET', '/settings/suite/delete', [
            'filename' => 'terminal-blocked-commands.json'
        ]);

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }

    /**
     * Test load feature flags list page
     *
     * @return void
     */
    public function testLoadFeatureFlagsListPage(): void
    {
        $this->client->request('GET', '/settings/feature-flags');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertAnySelectorTextContains('p', 'Manage feature flags');
        $this->assertSelectorExists('button[id="menu-toggle"]');
        $this->assertSelectorTextContains('body', 'Feature Flags');
        $this->assertSelectorTextContains('body', 'Manage feature flags');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test update feature flag value
     *
     * @return void
     */
    public function testUpdateFeatureFlagValue(): void
    {
        $this->client->request('GET', '/settings/feature-flags/update', [
            'feature' => 'test-feature',
            'value' => 'enable'
        ]);

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }
}
