<?php

namespace App\Controller\Component;

use App\Util\AppUtil;
use App\Manager\AuthManager;
use App\Manager\ErrorManager;
use App\Manager\DatabaseManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class DatabaseBrowserController
 *
 * This controller is responsible for rendering the database browser page
 *
 * @package App\Controller\Component
 */
class DatabaseBrowserController extends AbstractController
{
    private AppUtil $appUtil;
    private AuthManager $authManager;
    private ErrorManager $errorManager;
    private DatabaseManager $databaseManager;

    public function __construct(
        AppUtil $appUtil,
        AuthManager $authManager,
        ErrorManager $errorManager,
        DatabaseManager $databaseManager
    ) {
        $this->appUtil = $appUtil;
        $this->authManager = $authManager;
        $this->errorManager = $errorManager;
        $this->databaseManager = $databaseManager;
    }

    /**
     * Renders the database browser page
     *
     * @param Request $request The request object
     *
     * @return Response The rendered database browser page
     */
    #[Route('/manager/database', methods:['GET'], name: 'app_manager_database')]
    public function databaseBrowser(Request $request): Response
    {
        // check if user have admin permissions
        if (!$this->authManager->isLoggedInUserAdmin()) {
            return $this->render('component/no-permissions.twig', [
                'isAdmin' => $this->authManager->isLoggedInUserAdmin(),
                'userData' => $this->authManager->getLoggedUserRepository(),
            ]);
        }

        // get database name from query parameter
        $databaseName = (string) $request->query->get('database', '');

        // check if database name set
        if ($databaseName == '') {
            // get the list of databases
            $databases = $this->databaseManager->getDatabasesList();

            // disable table browsing
            $tables = null;
        } else {
            // get the list of databases
            $databases = null;

            // disable table browsing
            $tables = $this->databaseManager->getTablesList($databaseName);
        }

        // render the database browser page
        return $this->render('component/database-browser/database-browser.twig', [
            'isAdmin' => true,
            'userData' => $this->authManager->getLoggedUserRepository(),

            // database browser data
            'databaseName' => $databaseName,
            'databases' => $databases,
            'tables' => $tables
        ]);
    }

    /**
     * Renders the table data browser page
     *
     * @param Request $request The request object
     *
     * @return Response The rendered table data browser page
     */
    #[Route('/manager/database/table', methods:['GET'], name: 'app_manager_database_table_browser')]
    public function databaseTableBrowser(Request $request): Response
    {
        // check if user have admin permissions
        if (!$this->authManager->isLoggedInUserAdmin()) {
            return $this->render('component/no-permissions.twig', [
                'isAdmin' => $this->authManager->isLoggedInUserAdmin(),
                'userData' => $this->authManager->getLoggedUserRepository(),
            ]);
        }

        // get request parameters
        $page = (int) $request->query->get('page', '1');
        $tableName = (string) $request->query->get('table');
        $databaseName = (string) $request->query->get('database');

        // check if table name and database name set
        if ($tableName == '' || $databaseName == '') {
            $this->errorManager->handleError(
                message: 'table name and database name are required',
                code: Response::HTTP_BAD_REQUEST
            );
        }

        // get page limiter
        $limitPerPage = $this->appUtil->getPageLimiter();

        // get the data from the table
        $tableData = $this->databaseManager->getTableData($databaseName, $tableName, $page);

        // get the number of rows in the table
        $tableDataCount = $this->databaseManager->getTableRowCount($databaseName, $tableName);

        // render the table browser page
        return $this->render('component/database-browser/table-browser.twig', [
            'isAdmin' => true,
            'userData' => $this->authManager->getLoggedUserRepository(),

            // filter data
            'currentPage' => $page,
            'tableName' => $tableName,
            'databaseName' => $databaseName,
            'limitPerPage' => $limitPerPage,

            // table data
            'tableDataCount' => $tableDataCount,
            'tableData' => $tableData
        ]);
    }
}
