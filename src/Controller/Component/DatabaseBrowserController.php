<?php

namespace App\Controller\Component;

use DateTime;
use App\Util\AppUtil;
use App\Manager\ErrorManager;
use App\Manager\DatabaseManager;
use App\Annotation\Authorization;
use App\Form\Database\QueryConsoleFormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class DatabaseBrowserController
 *
 * Controller for database browser component
 *
 * @package App\Controller\Component
 */
class DatabaseBrowserController extends AbstractController
{
    private AppUtil $appUtil;
    private ErrorManager $errorManager;
    private DatabaseManager $databaseManager;

    public function __construct(AppUtil $appUtil, ErrorManager $errorManager, DatabaseManager $databaseManager)
    {
        $this->appUtil = $appUtil;
        $this->errorManager = $errorManager;
        $this->databaseManager = $databaseManager;
    }

    /**
     * Render database select page
     *
     * @param Request $request The request object
     *
     * @return Response The database select page view
     */
    #[Authorization(authorization: 'ADMIN')]
    #[Route('/manager/database', methods:['GET'], name: 'app_manager_database')]
    public function databaseBrowser(Request $request): Response
    {
        // get database name from query parameter
        $databaseName = (string) $request->query->get('database', '');

        // check if database name set
        if ($databaseName == '') {
            // get database list
            $databases = $this->databaseManager->getDatabasesList();

            // disable table browser
            $tables = null;
        } else {
            $databases = null;

            // get database tables list
            $tables = $this->databaseManager->getTablesList($databaseName);
        }

        // render render database browser page view
        return $this->render('component/database-browser/database-browser.twig', [
            'tables' => $tables,
            'databases' => $databases,
            'databaseName' => $databaseName
        ]);
    }

    /**
     * Render table data browser page
     *
     * @param Request $request The request object
     *
     * @return Response The table data browser page view
     */
    #[Authorization(authorization: 'ADMIN')]
    #[Route('/manager/database/table', methods:['GET'], name: 'app_manager_database_table_browser')]
    public function databaseTableBrowser(Request $request): Response
    {
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

        // get limit per page
        $limitPerPage = $this->appUtil->getEnvValue('LIMIT_CONTENT_PER_PAGE');

        // get data from the table
        $tableData = $this->databaseManager->getTableData($databaseName, $tableName, $page);

        // get number of rows in the table
        $tableDataCount = $this->databaseManager->getTableRowCount($databaseName, $tableName);

        // render table browser page view
        return $this->render('component/database-browser/table-browser.twig', [
            // filter data
            'currentPage' => $page,
            'tableName' => $tableName,
            'databaseName' => $databaseName,
            'limitPerPage' => $limitPerPage,

            // table data
            'tableData' => $tableData,
            'tableDataCount' => $tableDataCount
        ]);
    }

    /**
     * Render add row form page
     *
     * @param Request $request The request object
     *
     * @return Response The add row form page view
     */
    #[Authorization(authorization: 'ADMIN')]
    #[Route('/manager/database/add', methods: ['GET', 'POST'], name: 'app_manager_database_add')]
    public function databaseAddRow(Request $request): Response
    {
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

        // prepare form data variables
        $errors = [];
        $formData = [];

        // check if form is submitted with POST method
        if ($request->isMethod('POST')) {
            /** @var array<mixed> $formData */
            $formData = $request->request->all();

            // column data validation
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

            // check if record id already exists
            if ($rowId != 0) {
                if ($this->databaseManager->doesRecordExist($databaseName, $tableName, $rowId)) {
                    $errors[] = 'Record with ID ' . $rowId . ' already exists.';
                }
            }

            // check errors
            if (empty($errors)) {
                // add row to table
                $this->databaseManager->addRowToTable($formData, $databaseName, $tableName);

                // get last page number
                $lastPageNumber = $this->databaseManager->getLastPageNumber($databaseName, $tableName);

                // redirect to table browser
                return $this->redirectToRoute('app_manager_database_table_browser', [
                    'database' => $databaseName,
                    'table' => $tableName,
                    'page' => $lastPageNumber
                ], Response::HTTP_FOUND);
            }
        }

        // render add row form view
        return $this->render('component/database-browser/form/add-row.twig', [
            // filter data
            'tableName' => $tableName,
            'databaseName' => $databaseName,

            // form data
            'errors' => $errors,
            'columns' => $columns,
            'formData' => $formData
        ]);
    }

    /**
     * Render edit row form page
     *
     * @param Request $request The request object
     *
     * @return Response The edit row form page view
     */
    #[Authorization(authorization: 'ADMIN')]
    #[Route('/manager/database/edit', methods: ['GET', 'POST'], name: 'app_manager_database_edit')]
    public function databaseEditRow(Request $request): Response
    {
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
                message: 'table ' . $tableName . ' not found in database ' . $databaseName,
                code: Response::HTTP_NOT_FOUND
            );
        }

        // check if record exists
        if (!$this->databaseManager->doesRecordExist($databaseName, $tableName, $id)) {
            $this->errorManager->handleError(
                message: 'record with ID ' . $id . ' not found in table ' . $tableName . ' in database ' . $databaseName,
                code: Response::HTTP_NOT_FOUND
            );
        }

        // get columns to generate form
        $columns = $this->databaseManager->getColumnsList($databaseName, $tableName);

        // fetch existing row data for pre-filling the form
        $existingData = $this->databaseManager->getRowById($databaseName, $tableName, $id);

        // initialize form data with existing values or empty if not found
        $formData = $existingData ?: [];

        // unset id column
        if ($columns[0]['COLUMN_NAME'] == 'id') {
            unset($columns[0]);
        }

        // prepare form data
        $errors = [];

        // check if form is submitted with POST method
        if ($request->isMethod('POST')) {
            /** @var array<mixed> $formData */
            $formData = $request->request->all();

            // column data validation
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

            // set row id
            $formData['id'] = $id;

            // check errors
            if (empty($errors)) {
                // update row in table
                $this->databaseManager->updateRowById($formData, $databaseName, $tableName, $id);

                // redirect to table browser
                return $this->redirectToRoute('app_manager_database_table_browser', [
                    'database' => $databaseName,
                    'table' => $tableName,
                    'page' => $page
                ]);
            }
        }

        // render edit row form view
        return $this->render('component/database-browser/form/edit-row.twig', [
            // filter data
            'id' => $id,
            'page' => $page,
            'tableName' => $tableName,
            'databaseName' => $databaseName,

            // form data
            'errors' => $errors,
            'columns' => $columns,
            'formData' => $formData
        ]);
    }

    /**
     * Handle database record delete
     *
     * @param Request $request The request object
     *
     * @return Response The rendered delete row form
     */
    #[Authorization(authorization: 'ADMIN')]
    #[Route('/manager/database/delete', methods: ['GET'], name: 'app_manager_database_delete')]
    public function databaseDeleteRow(Request $request): Response
    {
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
                message: 'table ' . $tableName . ' not found in database ' . $databaseName,
                code: Response::HTTP_NOT_FOUND
            );
        }

        // check if record exists
        if (!$this->databaseManager->doesRecordExist($databaseName, $tableName, $id)) {
            $this->errorManager->handleError(
                message: 'record with ID ' . $id . ' not found in table ' . $tableName . ' in database ' . $databaseName,
                code: Response::HTTP_NOT_FOUND
            );
        }

        // delete record from table
        $this->databaseManager->deleteRowById($databaseName, $tableName, $id);

        // redirect to table browser page
        return $this->redirectToRoute('app_manager_database_table_browser', [
            'page' => $page,
            'table' => $tableName,
            'database' => $databaseName
        ]);
    }

    /**
     * Render truncate table form page
     *
     * @param Request $request The request object
     *
     * @return Response The truncate table form page view
     */
    #[Authorization(authorization: 'ADMIN')]
    #[Route('/manager/database/truncate', methods: ['GET'], name: 'app_manager_database_truncate')]
    public function databaseTruncateTable(Request $request): Response
    {
        // get request parameters
        $confirm = (string) $request->query->get('confirm', 'no');
        $tableName = (string) $request->query->get('table');
        $databaseName = (string) $request->query->get('database');

        // check confirmation
        if ($confirm !== 'yes') {
            return $this->render('component/database-browser/form/truncate-confirmation.twig', [
                'databaseName' => $databaseName,
                'tableName' => $tableName
            ]);
        }

        // check if table name and database name are set
        if (empty($tableName) || empty($databaseName)) {
            $this->errorManager->handleError(
                message: 'table name and database name are required',
                code: Response::HTTP_BAD_REQUEST
            );
        }

        // check if table exists
        if (!$this->databaseManager->isTableExists($databaseName, $tableName)) {
            $this->errorManager->handleError(
                message: 'table ' . $tableName . ' not found in database ' . $databaseName,
                code: Response::HTTP_NOT_FOUND
            );
        }

        // truncate table
        $this->databaseManager->tableTruncate($databaseName, $tableName);

        // redirect to table browser
        return $this->redirectToRoute('app_manager_database_table_browser', [
            'page' => 1,
            'table' => $tableName,
            'database' => $databaseName
        ]);
    }

    /**
     * Dump database to file
     *
     * @param Request $request The request object
     *
     * @return Response The database dump page view
     */
    #[Authorization(authorization: 'ADMIN')]
    #[Route('/manager/database/dump', methods: ['GET'], name: 'app_manager_database_dump')]
    public function databaseDump(Request $request): Response
    {
        // get request parameters
        $plain = (string) $request->query->get('plain', 'no');
        $select = (string) $request->query->get('select', 'yes');
        $databaseName = (string) $request->query->get('database');

        // check if dump mode is select
        if ($select === 'yes') {
            // get databases list
            $databases = $this->databaseManager->getDatabasesList();

            // render database dump page view
            return $this->render('component/database-browser/database-dump.twig', [
                'databases' => $databases,
            ]);
        }

        // check if database name and plain flag are set
        if (empty($databaseName) || empty($plain)) {
            $this->errorManager->handleError(
                message: 'database name and plain flag are required',
                code: Response::HTTP_BAD_REQUEST
            );
        }

        // check if database exists
        if (!$this->databaseManager->isDatabaseExists($databaseName)) {
            $this->errorManager->handleError(
                message: 'database ' . $databaseName . ' not found',
                code: Response::HTTP_NOT_FOUND
            );
        }

        // check if plain flag is set
        if ($plain === 'yes') {
            $plain = true;
        } else {
            $plain = false;
        }

        // get database dump
        $databaseDump = $this->databaseManager->getDatabaseDump($databaseName, $plain);

        // get current time
        $currentTime = new DateTime();

        // return database dump file
        return new Response(
            content: $databaseDump,
            status: Response::HTTP_OK,
            headers:[
                'Content-Type' => 'application/sql',
                'Content-Disposition' => 'attachment; filename="' . $databaseName . '_dump-' . $currentTime->format('Y.m.d-H.i') . '.sql' . '"'
            ]
        );
    }

    /**
     * Render database console page
     *
     * @param Request $request The request object
     *
     * @return Response The database console page view
     */
    #[Authorization(authorization: 'ADMIN')]
    #[Route('/manager/database/console', methods: ['GET', 'POST'], name: 'app_manager_database_console')]
    public function databaseConsole(Request $request): Response
    {
        // create query console form
        $queryForm = $this->createForm(QueryConsoleFormType::class);
        $queryForm->handleRequest($request);

        // default output
        $output = null;

        // check if form is submitted
        if ($queryForm->isSubmitted() && $queryForm->isValid()) {
            /** @var string $query sql query input */
            $query = $queryForm->get('query')->getData();

            // execute query
            $output = $this->databaseManager->executeQuery($query);
        }

        // render database console page view
        return $this->render('component/database-browser/database-console.twig', [
            'output' => $output,
            'queryForm' => $queryForm->createView()
        ]);
    }
}
