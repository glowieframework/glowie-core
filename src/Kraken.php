<?php
    namespace Glowie;

    /**
     * Model core for Glowie application.
     * @category Model
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 0.2-alpha
     */
    class Kraken{

        /**
         * Current MysqliDb object.
         * @var \MysqliDb
         */
        public $db;

        /**
         * Current table.
         * @var string
         */
        public $table;

        /**
         * Connects to a database table.
         * @param array $database (Optional) Connection settings. Use an empty array to connect to the globally defined database (in **Config.php**).
         * @param string $table (Optional) Table name.
         */
        public function __construct(array $database = [], string $table = 'app'){
            $this->setDatabase($database);
            $this->setTable($table);
        }

        /**
         * Sets the current table.
         * @param string $table Table name.
         */
        public function setTable(string $table){
            if (empty($table) || trim($table) == '') trigger_error('setTable: Table name should not be empty');
            $this->table = $table;
        }

        /**
         * Sets the current database connection.
         * @param array $database (Optional) Connection settings. Use an empty array to connect to the globally defined database (in **Config.php**).
         */
        public function setDatabase(array $database = []){
            if (empty($database)) $database = $GLOBALS['glowieConfig']['database'];
            if (!is_array($database)) trigger_error('setDatabase: Database connection settings must be an array');
            if (empty($database['host'])) trigger_error('setDatabase:  Database host not defined');
            if (empty($database['username'])) trigger_error('setDatabase:  Database username not defined');
            if (empty($database['password'])) $database['password'] = '';
            if (empty($database['port'])) $database['port'] = 3306;
            if (empty($database['db'])) trigger_error('setDatabase: Database name not defined');
            $this->db = new \MysqliDb($database['host'], $database['username'], $database['password'], $database['db'], $database['port']);
        }

        /**
         * Inserts data into the table.
         * @param array $data Data to be inserted. Must be an associative array with keys related to each column.
         * @param bool $replace (Optional) Set **true** if you want to replace data in the table if a primary/unique key already exists.
         * @return mixed|bool Returns the last inserted ID on success or false on error.
         */
        public function insertData(array $data, bool $replace = false){
            if(empty($data)) trigger_error('insertData: Data cannot be empty');
            if(!is_array($data)) trigger_error('insertData: Data must be an array');
            if($replace){
                $id = $this->db->replace($this->table, $data);
            }else{
                $id = $this->db->insert($this->table, $data);
            }
            if($id && $this->db->getLastErrno() === 0){
                return $id;
            }else{
                throw new \Exception('insertData: ' . $this->db->getLastError());
                return false;
            }
        }

        /**
         * Inserts multiple data into the table.
         * @param array $data Data to be inserted. Must be an array of associative arrays with keys related to each column.
         * @return array|bool Returns an array with the last inserted IDs on success or false on error.
         */
        public function insertMultipleData(array $data){
            if (!is_array($data)) trigger_error('insertMultipleData: Data must be an array');
            if (empty($data)) trigger_error('insertMultipleData: Data cannot be empty');
            $ids = $this->db->insertMulti($this->table, $data);
            if($ids && $this->db->getLastErrno() === 0){
                return $ids;
            }else{
                throw new \Exception('insertMultipleData: ' . $this->db->getLastError());
                return false;
            }
        }

        /**
         * Updates data in the table.
         * @param array $data Data to be updated. Must be an associative array with keys related to each column.
         * @param array $filters (Optional) Associative array with WHERE filters (see docs).
         * @param int $limit (Optional) Maximum number of records to update. Use 0 for unlimited.
         * @return int|bool Returns the amount of updated records on success or false on error.
         */
        public function updateData(array $data, array $filters = [], int $limit = 0){
            if (!is_array($data)) trigger_error('updateData: Data must be an array');
            if (!is_array($filters)) trigger_error('updateData: Filters must be an array');
            if (empty($data)) trigger_error('updateData: Data cannot be empty');
            if (!empty($filters)) $this->setFilters($filters);
            if($this->db->update($this->table, $data, $limit == 0 ? null : $limit) && $this->db->getLastErrno() === 0){
                return intval($this->db->count);
            }else{
                throw new \Exception('updateData: ' . $this->db->getLastError());
                return false;
            }
        }

        /**
         * Gets data from the table.
         * @param array $filters (Optional) Associative array with **WHERE** filters (see docs).
         * @param int $limit (Optional) Maximum number of records to get. Use **0** for unlimited.
         * @param bool $order (Optional) Order data by a column value.
         * @param string $orderBy (Optional) If **order** is set to **true**, the column name to use while ordering data.
         * @param string $orderAs (Optional) If **order** is set to **true**, the sorting method to use while ordering data.\
         * Use **ASC** for ascending or **DESC** for descending.
         * @param bool $pagination (Optional) Split records in pages.
         * @param int $currentPage (Optional) If **pagination** is set to **true**, the current page to fetch records.
         * @param int $itemsPerPage (Optional) If **pagination** is set to **true**, the maximum number of records to fetch per page.
         * @return array|null Returns an array with the fetched records as objects. If nothing is found, returns an empty array (or null on single item).\
         * If **pagination** is set to **true**, returns an array with **data** key being an array of records and **pages** key being the total amount of pages.
         */
        public function getData(array $filters = [], int $limit = 0, bool $order = false, string $orderBy = '', string $orderAs = 'ASC', bool $pagination = false, int $currentPage = 1, int $itemsPerPage = 10){
            if (!is_array($filters)) trigger_error('getData: Filters must be an array');
            if(!empty($filters)) $this->setFilters($filters);
            if($order) $this->db->orderBy($orderBy, $orderAs);
            if($pagination){
                $this->db->pageLimit = $itemsPerPage;
                $data = $this->db->arraybuilder()->paginate($this->table, $currentPage);
                $result = ['data' => [], 'pages' => $this->db->totalPages];
                if(!empty($data)) foreach($data as $value) $result['data'][] = new \Objectify($value);
                return new \Objectify($result);
            }else{
                if($limit == 1){
                    $data = $this->db->getOne($this->table);
                    $result = null;
                    if(!empty($data)) $result = new \Objectify($data);
                    return $result;
                }else{
                    $data = $this->db->get($this->table, $limit == 0 ? null : $limit);
                    $result = [];
                    if(!empty($data)) foreach ($data as $value) $result[] = new \Objectify($value);
                    return $result;
                }
            }
        }

        /**
         * Deletes data from the table.
         * @param array $filters (Optional) Associative array with WHERE filters (see docs).
         * @param int $limit (Optional) Maximum number of records to delete. Use 0 for unlimited.
         * @return int|bool Returns the amount of deleted records on success or false on error.
         */
        public function deleteData(array $filters = [], int $limit = 0){
            if (!is_array($filters)) trigger_error('deleteData: Filters must be an array');
            if (!empty($filters)) $this->setFilters($filters);
            if($this->db->delete($this->table, $limit == 0 ? null : $limit) && $this->db->getLastErrno() === 0){
                return intval($this->db->count);
            }else{
                throw new \Exception('deleteData: ' . $this->db->getLastError());
                return false;
            }
        }

        /**
         * Sets the filters for a database query.
         * @param array $filters (Optional) Associative array with **WHERE** filters (see docs).
         */
        private function setFilters(array $filters){
            foreach ($filters as $key => $value) {
                if (\Util::startsWith($key, '#')) {
                    if (strpos($value, '%') !== false) {
                        $this->db->orWhere(substr($key, 1), $value, 'like');
                    } else {
                        $this->db->orWhere(substr($key, 1), $value);
                    }
                } else {
                    if (strpos($value, '%') !== false) {
                        $this->db->where($key, $value, 'like');
                    } else {
                        $this->db->where($key, $value);
                    }
                }
            }
        }
    }

?>