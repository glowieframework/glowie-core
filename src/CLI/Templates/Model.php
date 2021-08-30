<?php
    namespace Glowie\Models;

    use Glowie\Core\Database\Model;

    /**
     * __FIREFLY_TEMPLATE_NAME__ model for Glowie application.
     * @category Model
     * @package glowieframework/glowie
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 1.0
     */
    class __FIREFLY_TEMPLATE_NAME__ extends Model{

        /**
         * Model table name.
         * @var string
         */
        protected $_table = '__FIREFLY_TEMPLATE_TABLE__';

        /**
         * Table primary key name.
         * @var string
         */
        protected $_primaryKey = '__FIREFLY_TEMPLATE_PRIMARY__';

        /**
         * Table manageable fields.
         * @var array
         */
        protected $_fields = [];

        /**
         * Handle timestamp fields.
         * @var bool
         */
        protected $_timestamps = __FIREFLY_TEMPLATE_TIMESTAMPS__;

        /**
         * **Created at** field name (if timestamps enabled).
         * @var string
         */
        protected $_createdField = '__FIREFLY_TEMPLATE_CREATED__';

        /**
         * **Updated at** field name (if timestamps enabled).
         * @var string
         */
        protected $_updatedField = '__FIREFLY_TEMPLATE_UPDATED__';

    }

?>