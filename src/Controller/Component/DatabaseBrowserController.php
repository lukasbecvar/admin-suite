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

    /**
     * Renders the add row form for a specific table in a specific database
     *
     * @param Request $request The request object
     *
     * @return Response The rendered add row form
     */
    #[Route('/manager/database/add', methods: ['GET', 'POST'], name: 'app_manager_database_add')]
    public function databaseAddRow(Request $request): Response
    {
        // check if user have admin permissions
        if (!$this->authManager->isLoggedInUserAdmin()) {
            return $this->render('component/no-permissions.twig', [
                'isAdmin' => $this->authManager->isLoggedInUserAdmin(),
                'userData' => $this->authManager->getLoggedUserRepository(),
            ]);
        }

        // get request parameters
        $tableName = (string) $request->query->get('table');
        $databaseName = (string) $request->query->get('database');

        // check if table name and database name are set
        if (empty($tableName) || empty($databaseName)) {
            $this->errorManager->handleError(
                message: 'Table name and database name are required',
                code: Response::HTTP_BAD_REQUEST
            );
        }

        // check if table exists
        if (!$this->databaseManager->isTableExists($databaseName, $tableName)) {
            $this->errorManager->handleError(
                message: "Table '{$tableName}' not found in database '{$databaseName}'",
                code: Response::HTTP_NOT_FOUND
            );
        }

        // get columns to generate form
        $columns = $this->databaseManager->getColumnsList($databaseName, $tableName);

        // prepare form data
        $errors = [];
        $formData = [];

        // check if form is submitted with POST method
        if ($request->isMethod('POST')) {
            /** @var array<mixed> $formData */
            $formData = $request->request->all();

            // form data validation
            foreach ($columns as $column) {
                /** @var string $columnName */
                $columnName = $column['COLUMN_NAME'];

                /** @var string $isNullable */
                $isNullable = $column['IS_NULLABLE'] === 'YES';

                /** @var string $columnType */
                $columnType = $column['COLUMN_TYPE'];

                // check if value is present for non-nullable fields
                if (!$isNullable && empty($formData[$columnName])) {
                    $errors[] = 'The field ' . $columnName . ' is required and cannot be empty.';
                }

                // check if value is valid for specific column types
                if (!empty($formData[$columnName])) {
                    /** @var string $value */
                    $value = $formData[$columnName];
                    if (strpos($columnType, 'int') !== false && !is_numeric($value)) {
                        $errors[] = 'The field ' . $columnName . ' must be a number.';
                    }
                    if (strpos($columnType, 'varchar') !== false) {
                        $maxLength = (int) filter_var($columnType, FILTER_SANITIZE_NUMBER_INT);
                        if (strlen($value) > $maxLength) {
                            $errors[] = 'The field ' . $columnName . ' must not exceed {$maxLength} characters.';
                        }
                    }
                }
            }

            $rowId = 0;
            if (isset($formData['id'])) {
                /** @var int $rowId */
                $rowId = $formData['id'];
            }

            // check if record already exists
            if ($rowId != 0) {
                if ($this->databaseManager->doesRecordExist($databaseName, $tableName, $rowId)) {
                    $errors[] = 'Record with ID ' . $rowId . ' already exists.';
                }
            }

            // check errors
            if (empty($errors)) {
                // add row to table
                $this->databaseManager->addRowToTable($formData, $databaseName, $tableName);

                // get the last page number
                $lastPageNumber = $this->databaseManager->getLastPageNumber($databaseName, $tableName);

                // redirect to table browser
                return $this->redirectToRoute('app_manager_database_table_browser', [
                    'database' => $databaseName,
                    'table' => $tableName,
                    'page' => $lastPageNumber
                ], Response::HTTP_FOUND);
            }
        }

        // render the add row form
        return $this->render('component/database-browser/add-row.twig', [
            'isAdmin' => true,
            'userData' => $this->authManager->getLoggedUserRepository(),

            // filter data
            'databaseName' => $databaseName,
            'tableName' => $tableName,

            // form data
            'formData' => $formData,
            'columns' => $columns,
            'errors' => $errors
        ]);
    }

    /**
     * Renders the delete row form for a specific table in a specific database
     *
     * @param Request $request The request object
     *
     * @return Response The rendered delete row form
     */
    #[Route('/manager/database/delete', methods: ['GET', 'POST'], name: 'app_manager_database_delete')]
    public function databaseDeleteRow(Request $request): Response
    {
        // check if user have admin permissions
        if (!$this->authManager->isLoggedInUserAdmin()) {
            return $this->render('component/no-permissions.twig', [
                'isAdmin' => $this->authManager->isLoggedInUserAdmin(),
                'userData' => $this->authManager->getLoggedUserRepository(),
            ]);
        }

        // get request parameters
        $id = (int) $request->query->get('id');
        $page = (int) $request->query->get('page', '1');
        $tableName = (string) $request->query->get('table');
        $databaseName = (string) $request->query->get('database');

        // check if table name and database name are set
        if (empty($tableName) || empty($databaseName) || empty($id)) {
            $this->errorManager->handleError(
                message: 'table name/database name and row id are required',
                code: Response::HTTP_BAD_REQUEST
            );
        }

        // check if table exists
        if (!$this->databaseManager->isTableExists($databaseName, $tableName)) {
            $this->errorManager->handleError(
                message: "table '{$tableName}' not found in database '{$databaseName}'",
                code: Response::HTTP_NOT_FOUND
            );
        }

        // check if record exists
        if (!$this->databaseManager->doesRecordExist($databaseName, $tableName, $id)) {
            $this->errorManager->handleError(
                message: "record with ID '{$id}' not found in table '{$tableName}' in database '{$databaseName}'",
                code: Response::HTTP_NOT_FOUND
            );
        }

        // delete row from table
        $this->databaseManager->deleteRowById($databaseName, $tableName, $id);

        // redirect to table browser
        return $this->redirectToRoute('app_manager_database_table_browser', [
            'database' => $databaseName,
            'table' => $tableName,
            'page' => $page
        ], Response::HTTP_FOUND);
    }
}
